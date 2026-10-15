import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamDetailView from '../ExamDetailView.vue'
import { routes } from '@/router'
import { deleteExam, getExam } from '@/services/examService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/examService', () => ({ getExam: vi.fn(), deleteExam: vi.fn() }))

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
    expect(draft.find('.exam-detail__actions').text()).toContain('Editar')

    getExam.mockResolvedValue(exam({ status: 'published', published_at: '2026-10-15T10:00:00+00:00' }))
    const published = await mountDetail()

    expect(published.find('.exam-detail__actions').text()).not.toContain('Editar')
    expect(published.find('.exam-detail__actions').text()).not.toContain('Publicar')
    expect(published.text()).toContain('Prova publicada: não pode mais ser editada.')
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
    await vi.waitFor(() => expect(wrapper.router.currentRoute.value.name).toBe('teacher-exams'))
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
})
