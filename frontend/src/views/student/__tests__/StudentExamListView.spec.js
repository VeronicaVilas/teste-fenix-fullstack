import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import StudentExamListView from '../StudentExamListView.vue'
import { routes } from '@/router'
import { listAllAvailableExams } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ listAllAvailableExams: vi.fn() }))

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

function manyPending(count) {
  return Array.from({ length: count }, (_, index) => ({ ...pending, id: index + 10, title: `Prova ${index + 1}` }))
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
    listAllAvailableExams.mockResolvedValue([answered, pending])

    const wrapper = await mountList()
    const rows = wrapper.findAll('tbody tr')

    expect(listAllAvailableExams).toHaveBeenCalledWith(3)
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
    listAllAvailableExams.mockResolvedValue([answered])

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
    listAllAvailableExams.mockResolvedValue([pending])

    const row = (await mountList()).find('tbody tr')

    expect(row.find('.badge--pending').text()).toBe('A fazer')
    expect(row.find('.student-exams__grade').text()).toBe('—')
    expect(row.find('.start').text()).toBe('Iniciar')
    expect(row.find('.start').attributes('href')).toBe('/student/exams/4')
  })

  it('filters by situation', async () => {
    listAllAvailableExams.mockResolvedValue([answered, pending])
    const wrapper = await mountList()
    const buttons = wrapper.findAll('.student-exams__filters button')

    await buttons[1].trigger('click')
    expect(wrapper.findAll('tbody tr').map((row) => row.find('td').text())).toEqual(['Prova de História'])

    await buttons[2].trigger('click')
    expect(wrapper.findAll('tbody tr').map((row) => row.find('td').text())).toEqual(['Prova de Geografia'])
    expect(buttons[2].classes()).toContain('active')
  })

  it('shows the empty state of a filter', async () => {
    listAllAvailableExams.mockResolvedValue([pending])
    const wrapper = await mountList()

    await wrapper.findAll('.student-exams__filters button')[2].trigger('click')

    expect(wrapper.text()).toContain('Nenhuma prova nesta situação.')
  })

  it('paginates ten exams per page and goes back to the first page when filtering', async () => {
    listAllAvailableExams.mockResolvedValue(manyPending(12))
    const wrapper = await mountList()

    expect(wrapper.findAll('tbody tr')).toHaveLength(10)
    expect(wrapper.find('.pagination').text()).toContain('Página 1 de 2')

    await wrapper.findAll('.pagination button')[1].trigger('click')
    expect(wrapper.findAll('tbody tr')).toHaveLength(2)

    await wrapper.findAll('.student-exams__filters button')[1].trigger('click')
    expect(wrapper.find('.pagination').text()).toContain('Página 1 de 2')
  })

  it('shows the empty state', async () => {
    listAllAvailableExams.mockResolvedValue([])

    expect((await mountList()).text()).toContain('Nenhuma prova disponível no momento.')
  })

  it('shows the API error', async () => {
    listAllAvailableExams.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountList()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
