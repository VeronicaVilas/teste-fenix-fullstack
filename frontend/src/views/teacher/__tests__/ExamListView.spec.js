import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamListView from '../ExamListView.vue'
import { routes } from '@/router'
import { listExams } from '@/services/examService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/examService', () => ({ listExams: vi.fn() }))

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
  return { items, meta: { current_page: 1, last_page: 1, per_page: 10, total: items.length, ...meta } }
}

async function mountList() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/teacher/exams')
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
    expect(rows[1].findAll('td')[3].text()).toBe('4')
  })

  it('shows edit and publish only for drafts', async () => {
    listExams.mockResolvedValue(page([draft, published]))

    const [draftRow, publishedRow] = (await mountList()).findAll('tbody tr')

    expect(draftRow.text()).toContain('Editar')
    expect(draftRow.text()).toContain('Publicar')
    expect(publishedRow.text()).not.toContain('Editar')
    expect(publishedRow.text()).not.toContain('Publicar')
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
})
