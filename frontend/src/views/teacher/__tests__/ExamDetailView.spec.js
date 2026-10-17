import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamDetailView from '../ExamDetailView.vue'
import { routes } from '@/router'
import { deleteExam, getExam, publishExam } from '@/services/examService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/examService', () => ({ getExam: vi.fn(), deleteExam: vi.fn(), publishExam: vi.fn() }))

function exam(overrides = {}) {
  return {
    id: 10,
    title: 'Prova de Geografia',
    description: 'Capitais',
    status: 'draft',
    published_at: null,
    questions_count: 1,
    attempts_count: 0,
    questions: [
      {
        id: 20,
        position: 1,
        statement: 'Qual é a capital do Brasil?',
        options: [
          { id: 31, position: 1, text: 'São Paulo', is_correct: false },
          { id: 32, position: 2, text: 'Brasília', is_correct: true },
        ],
      },
    ],
    ...overrides,
  }
}

async function mountDetail() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/teacher/exams/10')
  await router.isReady()

  const wrapper = mount(ExamDetailView, { props: { id: '10' }, global: { plugins: [router] } })
  await flushPromises()

  wrapper.router = router
  return wrapper
}

describe('ExamDetailView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  it('loads the exam of the current teacher', async () => {
    getExam.mockResolvedValue(exam())

    const wrapper = await mountDetail()

    expect(getExam).toHaveBeenCalledWith(1, '10')
    expect(wrapper.find('h1').text()).toBe('Prova de Geografia')
    expect(wrapper.text()).toContain('Rascunho')
    expect(wrapper.text()).toContain('Qual é a capital do Brasil?')
  })

  it('highlights only the correct option', async () => {
    getExam.mockResolvedValue(exam())

    const options = (await mountDetail()).findAll('.option')

    expect(options[0].classes()).not.toContain('option--correct')
    expect(options[1].classes()).toContain('option--correct')
    expect(options[1].text()).toContain('B')
    expect(options[1].text()).toContain('✓ Correta')
  })

  it('shows edit and publish actions only for drafts', async () => {
    getExam.mockResolvedValue(exam())
    const draft = await mountDetail()
    expect(draft.find('.exam-detail__actions [aria-label="Editar prova"]').exists()).toBe(true)
    expect(draft.find('.exam-detail__actions .publish').text()).toBe('Publicar')

    getExam.mockResolvedValue(exam({ status: 'published', published_at: '2026-10-15T10:00:00+00:00' }))
    const published = await mountDetail()

    expect(published.find('.exam-detail__actions [aria-label="Editar prova"]').exists()).toBe(false)
    expect(published.find('.exam-detail__actions').text()).not.toContain('Publicar')
    expect(published.text()).toContain('Prova publicada: não pode mais ser editada.')
  })

  it('uses icon-only buttons to edit and delete', async () => {
    getExam.mockResolvedValue(exam())
    const actions = (await mountDetail()).find('.exam-detail__actions')

    for (const label of ['Editar prova', 'Excluir prova']) {
      const action = actions.find(`[aria-label="${label}"]`)
      expect(action.find('svg').exists()).toBe(true)
      expect(action.text()).toBe('')
    }
    expect(actions.find('.button-secondary').attributes('href')).toBe('/teacher/exams')
  })

  it('shows the exam counters as stat cards', async () => {
    getExam.mockResolvedValue(exam({ status: 'published', published_at: '2026-10-15T10:00:00+00:00', attempts_count: 4 }))

    const stats = (await mountDetail()).findAll('.stat')

    expect(stats.map((stat) => stat.find('h2').text())).toEqual(['Status', 'Questões', 'Tentativas', 'Publicada em'])
    expect(stats[0].find('.stat__value').text()).toBe('Publicada')
    expect(stats[1].find('.stat__value').text()).toBe('1')
    expect(stats[2].find('.stat__value').text()).toBe('4')
    expect(stats[3].find('.stat__value').text()).toBe('15/10/2026')
  })

  it('shows a not found message on 404', async () => {
    getExam.mockRejectedValue({ status: 404, message: 'Recurso não encontrado.', errors: {} })

    const wrapper = await mountDetail()

    expect(wrapper.text()).toContain('Prova não encontrada.')
    expect(wrapper.text()).toContain('Ir para a lista de provas')
  })

  it('shows other API errors', async () => {
    getExam.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    const wrapper = await mountDetail()

    expect(wrapper.text()).toContain('Não foi possível conectar ao servidor.')
  })

  it('deletes after confirmation and returns to the list', async () => {
    getExam.mockResolvedValue(exam())
    deleteExam.mockResolvedValue(null)
    const wrapper = await mountDetail()

    await wrapper.find('.exam-detail__actions button.danger').trigger('click')
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(deleteExam).toHaveBeenCalledWith(1, '10')
    await vi.waitFor(() => expect(wrapper.router.currentRoute.value.name).toBe('teacher-exams'), { timeout: 5000 })
    expect(wrapper.router.currentRoute.value.query.deleted).toBe('1')
  })

  it('disables delete when the exam has attempts', async () => {
    getExam.mockResolvedValue(exam({ status: 'published', attempts_count: 2 }))

    const wrapper = await mountDetail()

    expect(wrapper.find('.exam-detail__actions button.danger').attributes('disabled')).toBeDefined()
  })

  it('keeps the exam visible and shows the error when deletion fails', async () => {
    getExam.mockResolvedValue(exam())
    deleteExam.mockRejectedValue({
      status: 409,
      message: 'Prova já respondida por alunos não pode ser excluída.',
      errors: {},
    })
    const wrapper = await mountDetail()

    await wrapper.find('.exam-detail__actions button.danger').trigger('click')
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Prova já respondida por alunos não pode ser excluída.')
    expect(wrapper.find('h1').text()).toBe('Prova de Geografia')
  })

  it('publishes after confirmation and freezes the exam', async () => {
    getExam.mockResolvedValue(exam())
    publishExam.mockResolvedValue(exam({ status: 'published', published_at: '2026-10-15T14:00:00+00:00' }))
    const wrapper = await mountDetail()

    await wrapper.find('.exam-detail__actions .publish').trigger('click')
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(publishExam).toHaveBeenCalledWith(1, '10')
    expect(wrapper.text()).toContain('Prova publicada.')
    expect(wrapper.text()).toContain('Publicada')
    expect(wrapper.text()).toContain('Prova publicada: não pode mais ser editada.')
    expect(wrapper.find('.exam-detail__actions [aria-label="Editar prova"]').exists()).toBe(false)
    expect(wrapper.find('.exam-detail__actions').text()).not.toContain('Publicar')
  })
})
