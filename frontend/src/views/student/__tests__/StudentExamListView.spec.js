import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import StudentExamListView from '../StudentExamListView.vue'
import { routes } from '@/router'
import { listAvailableExams } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ listAvailableExams: vi.fn() }))

const answered = {
  id: 3,
  title: 'Prova de Geografia',
  description: 'Capitais',
  questions_count: 5,
  published_at: '2026-10-15T12:00:00+00:00',
  answered: true,
  attempt: { score: 4, total_questions: 5, percentage: '80.00', submitted_at: '2026-10-15T13:00:00+00:00' },
}

const pending = {
  id: 4,
  title: 'Prova de História',
  description: null,
  questions_count: 8,
  published_at: '2026-10-15T10:00:00+00:00',
  answered: false,
  attempt: null,
}

function page(items, meta = {}) {
  return { items, meta: { current_page: 1, last_page: 1, per_page: 10, total: items.length, ...meta } }
}

async function mountList() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/student/exams')
  await router.isReady()

  const wrapper = mount(StudentExamListView, { global: { plugins: [router] } })
  await flushPromises()
  return wrapper
}

describe('StudentExamListView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    vi.clearAllMocks()
  })

  it('lists every exam of the current student', async () => {
    listAvailableExams.mockResolvedValue(page([answered, pending]))

    const wrapper = await mountList()
    const rows = wrapper.findAll('tbody tr')

    expect(listAvailableExams).toHaveBeenCalledWith(3, { page: 1, perPage: 10, status: null })
    expect(wrapper.findAll('th').map((header) => header.text())).toEqual([
      'Título',
      'Questões',
      'Situação',
      'Nota',
      'Ações',
    ])
    expect(rows).toHaveLength(2)
    expect(rows[0].text()).toContain('Prova de Geografia')
    expect(rows[1].findAll('td')[1].text()).toBe('8')
  })

  it('shows the grade and a review icon for answered exams', async () => {
    listAvailableExams.mockResolvedValue(page([answered]))

    const row = (await mountList()).find('tbody tr')
    const review = row.find('[aria-label="Revisar prova"]')

    expect(row.find('.badge--answered').text()).toBe('Realizada')
    expect(row.find('.student-exams__grade strong').text()).toBe('8,0')
    expect(row.find('.student-exams__grade small').text()).toBe('4/5 acertos')
    expect(review.attributes('href')).toBe('/student/exams/3/result')
    expect(review.find('svg').exists()).toBe(true)
    expect(review.text()).toBe('')
    expect(row.find('.start').exists()).toBe(false)
  })

  it('offers pending exams to be started', async () => {
    listAvailableExams.mockResolvedValue(page([pending]))

    const row = (await mountList()).find('tbody tr')

    expect(row.find('.badge--pending').text()).toBe('A fazer')
    expect(row.find('.student-exams__grade').text()).toBe('—')
    expect(row.find('.start').text()).toBe('Iniciar')
    expect(row.find('.start').attributes('href')).toBe('/student/exams/4')
  })

  it('filters by situation on the API starting from the first page', async () => {
    listAvailableExams.mockResolvedValue(page([answered, pending]))
    const wrapper = await mountList()
    const buttons = wrapper.findAll('.student-exams__filters button')

    listAvailableExams.mockResolvedValue(page([pending]))
    await buttons[1].trigger('click')
    await flushPromises()

    expect(listAvailableExams).toHaveBeenLastCalledWith(3, { page: 1, perPage: 10, status: 'pending' })
    expect(wrapper.findAll('tbody tr').map((row) => row.find('td').text())).toEqual(['Prova de História'])

    listAvailableExams.mockResolvedValue(page([answered]))
    await buttons[2].trigger('click')
    await flushPromises()

    expect(listAvailableExams).toHaveBeenLastCalledWith(3, { page: 1, perPage: 10, status: 'answered' })
    expect(wrapper.findAll('tbody tr').map((row) => row.find('td').text())).toEqual(['Prova de Geografia'])
    expect(buttons[2].classes()).toContain('active')
  })

  it('shows the empty state of a filter', async () => {
    listAvailableExams.mockResolvedValue(page([pending]))
    const wrapper = await mountList()

    listAvailableExams.mockResolvedValue(page([]))
    await wrapper.findAll('.student-exams__filters button')[2].trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Nenhuma prova nesta situação.')
  })

  it('loads another page from the API', async () => {
    listAvailableExams.mockResolvedValue(page([pending], { last_page: 2, total: 11 }))
    const wrapper = await mountList()

    expect(wrapper.find('.pagination__info').text()).toBe('Mostrando 1–10 de 11')

    listAvailableExams.mockResolvedValue(page([answered], { current_page: 2, last_page: 2, total: 11 }))
    await wrapper.find('[aria-label="Próxima página"]').trigger('click')
    await flushPromises()

    expect(listAvailableExams).toHaveBeenLastCalledWith(3, { page: 2, perPage: 10, status: null })
    expect(wrapper.find('.pagination__page.active').text()).toBe('2')
    expect(wrapper.find('tbody tr td').text()).toBe('Prova de Geografia')
  })

  it('shows the empty state', async () => {
    listAvailableExams.mockResolvedValue(page([]))

    expect((await mountList()).text()).toContain('Nenhuma prova disponível no momento.')
  })

  it('shows the API error', async () => {
    listAvailableExams.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountList()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
