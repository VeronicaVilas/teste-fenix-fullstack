import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamListView from '../ExamListView.vue'
import { routes } from '@/router'
import { deleteExam, listExams, publishExam } from '@/services/examService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/examService', () => ({ listExams: vi.fn(), deleteExam: vi.fn(), publishExam: vi.fn() }))

const draft = {
  id: 2,
  title: 'Prova de História',
  status: 'draft',
  published_at: null,
  questions_count: 5,
  attempts_count: 0,
  created_at: '2026-10-15T12:00:00+00:00',
}

const published = {
  id: 1,
  title: 'Prova de Geografia',
  status: 'published',
  published_at: '2026-10-15T10:00:00+00:00',
  questions_count: 3,
  attempts_count: 4,
  created_at: '2026-10-15T09:00:00+00:00',
}

function page(items, meta = {}) {
  return {
    items: items.map((item) => ({ ...item })),
    meta: { current_page: 1, last_page: 1, per_page: 10, total: items.length, ...meta },
  }
}

async function mountList(path = '/teacher/exams') {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push(path)
  await router.isReady()

  const wrapper = mount(ExamListView, { global: { plugins: [router] } })
  await flushPromises()

  return wrapper
}

describe('ExamListView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  it('lists the teacher exams with status and counts', async () => {
    listExams.mockResolvedValue(page([draft, published]))

    const wrapper = await mountList()
    const rows = wrapper.findAll('tbody tr')

    expect(listExams).toHaveBeenCalledWith(1, { status: null, page: 1 })
    expect(rows).toHaveLength(2)
    expect(rows[0].text()).toContain('Prova de História')
    expect(rows[0].text()).toContain('Rascunho')
    expect(rows[1].text()).toContain('Publicada')
    expect(rows[1].findAll('td')[2].text()).toBe('3')
  })

  it('has no attempts or created at columns', async () => {
    listExams.mockResolvedValue(page([draft, published]))

    const headers = (await mountList()).findAll('th').map((header) => header.text())

    expect(headers).toEqual(['Título', 'Status', 'Questões', 'Ações'])
  })

  it('shows edit and publish only for drafts', async () => {
    listExams.mockResolvedValue(page([draft, published]))

    const [draftRow, publishedRow] = (await mountList()).findAll('tbody tr')

    expect(draftRow.find('[aria-label="Ver prova"]').exists()).toBe(true)
    expect(draftRow.find('[aria-label="Editar prova"]').exists()).toBe(true)
    expect(draftRow.find('button.publish').text()).toBe('Publicar')
    expect(publishedRow.find('[aria-label="Ver prova"]').exists()).toBe(true)
    expect(publishedRow.find('[aria-label="Editar prova"]').exists()).toBe(false)
    expect(publishedRow.find('button.publish').exists()).toBe(false)
  })

  it('shows view, edit and delete as icon-only buttons', async () => {
    listExams.mockResolvedValue(page([draft]))

    const row = (await mountList()).find('tbody tr')

    for (const label of ['Ver prova', 'Editar prova', 'Excluir prova']) {
      const action = row.find(`[aria-label="${label}"]`)
      expect(action.find('svg').exists()).toBe(true)
      expect(action.text()).toBe('')
    }
  })

  it('filters by status starting from the first page', async () => {
    listExams.mockResolvedValue(page([draft]))
    const wrapper = await mountList()

    await wrapper.findAll('.exam-list__filters button')[2].trigger('click')
    await flushPromises()

    expect(listExams).toHaveBeenLastCalledWith(1, { status: 'published', page: 1 })
  })

  it('loads another page', async () => {
    listExams.mockResolvedValue(page([draft], { last_page: 2, total: 11 }))
    const wrapper = await mountList()

    await wrapper.findAll('.pagination button')[1].trigger('click')
    await flushPromises()

    expect(listExams).toHaveBeenLastCalledWith(1, { status: null, page: 2 })
  })

  it('shows the empty state', async () => {
    listExams.mockResolvedValue(page([]))

    const wrapper = await mountList()

    expect(wrapper.text()).toContain('Nenhuma prova cadastrada. Crie a primeira!')
    expect(wrapper.find('table').exists()).toBe(false)
  })

  it('shows the API error', async () => {
    listExams.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    const wrapper = await mountList()

    expect(wrapper.text()).toContain('Não foi possível conectar ao servidor.')
  })

  it('disables delete for exams answered by students', async () => {
    listExams.mockResolvedValue(page([draft, published]))

    const [draftRow, publishedRow] = (await mountList()).findAll('tbody tr')

    expect(draftRow.find('button.danger').attributes('disabled')).toBeUndefined()
    expect(publishedRow.find('button.danger').attributes('disabled')).toBeDefined()
    expect(publishedRow.find('button.danger').attributes('title')).toBe('Prova já respondida por alunos')
  })

  it('deletes after confirmation and reloads the page', async () => {
    listExams.mockResolvedValue(page([draft, published]))
    deleteExam.mockResolvedValue(null)
    const wrapper = await mountList()

    await wrapper.findAll('tbody tr')[0].find('button.danger').trigger('click')
    expect(wrapper.find('[role="dialog"]').text()).toContain("Excluir a prova 'Prova de História'?")
    expect(deleteExam).not.toHaveBeenCalled()

    listExams.mockResolvedValue(page([published]))
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(deleteExam).toHaveBeenCalledWith(1, 2)
    expect(listExams).toHaveBeenLastCalledWith(1, { status: null, page: 1 })
    expect(wrapper.text()).toContain('Prova excluída.')
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('goes back one page when deleting the last item of a page', async () => {
    listExams.mockResolvedValue(page([draft], { current_page: 2, last_page: 2, total: 11 }))
    deleteExam.mockResolvedValue(null)
    const wrapper = await mountList()

    await wrapper.find('button.danger').trigger('click')
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(listExams).toHaveBeenLastCalledWith(1, { status: null, page: 1 })
  })

  it('does not delete when cancelled', async () => {
    listExams.mockResolvedValue(page([draft]))
    const wrapper = await mountList()

    await wrapper.find('button.danger').trigger('click')
    await wrapper.findAll('[role="dialog"] button')[0].trigger('click')

    expect(deleteExam).not.toHaveBeenCalled()
    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('shows the API error when deletion is refused', async () => {
    listExams.mockResolvedValue(page([draft]))
    deleteExam.mockRejectedValue({
      status: 409,
      message: 'Prova já respondida por alunos não pode ser excluída.',
      errors: {},
    })
    const wrapper = await mountList()

    await wrapper.find('button.danger').trigger('click')
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Prova já respondida por alunos não pode ser excluída.')
  })

  it('shows the deleted message when coming from the detail page', async () => {
    listExams.mockResolvedValue(page([published]))

    const wrapper = await mountList('/teacher/exams?deleted=1')

    expect(wrapper.text()).toContain('Prova excluída.')
  })

  it('publishes a draft after confirmation and updates the row', async () => {
    listExams.mockResolvedValue(page([draft]))
    publishExam.mockResolvedValue({ ...draft, status: 'published', published_at: '2026-10-15T14:00:00+00:00' })
    const wrapper = await mountList()

    await wrapper.find('button.publish').trigger('click')
    expect(wrapper.find('[role="dialog"]').text()).toContain('não poderá mais ser editada')
    expect(publishExam).not.toHaveBeenCalled()

    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    const row = wrapper.find('tbody tr')
    expect(publishExam).toHaveBeenCalledWith(1, 2)
    expect(row.text()).toContain('Publicada')
    expect(row.find('[aria-label="Editar prova"]').exists()).toBe(false)
    expect(row.find('button.publish').exists()).toBe(false)
    expect(wrapper.text()).toContain('Prova publicada.')
  })

  it('shows the API error when publishing fails', async () => {
    listExams.mockResolvedValue(page([draft]))
    publishExam.mockRejectedValue({ status: 409, message: 'Prova publicada não pode ser alterada.', errors: {} })
    const wrapper = await mountList()

    await wrapper.find('button.publish').trigger('click')
    await wrapper.find('.dialog__confirm').trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Prova publicada não pode ser alterada.')
  })
})
