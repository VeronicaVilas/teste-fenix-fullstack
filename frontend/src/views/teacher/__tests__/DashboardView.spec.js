import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import DashboardView from '../DashboardView.vue'
import { routes } from '@/router'
import { getDashboardSummary } from '@/services/dashboardService'
import { listExams } from '@/services/examService'
import { listStudents } from '@/services/userService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/dashboardService', () => ({
  getDashboardSummary: vi.fn(),
  getExamsRanking: vi.fn().mockResolvedValue({
    items: [],
    meta: { current_page: 1, last_page: 1, per_page: 4, total: 0 },
  }),
}))
vi.mock('@/services/examService', () => ({ listExams: vi.fn() }))
vi.mock('@/services/userService', () => ({ listStudents: vi.fn() }))

const exams = [
  { id: 10, title: 'Prova de Geografia', attempts_count: 3, average_percentage: '66.67', best_percentage: '100.00', worst_percentage: '33.33' },
  { id: 11, title: 'Prova de História', attempts_count: 0, average_percentage: null, best_percentage: null, worst_percentage: null },
]

const summary = {
  exam_id: null,
  total_attempts: 3,
  total_students: 3,
  average_percentage: '71.25',
  best: { student_name: 'Ana Souza', exam_title: 'Prova de Geografia', score: 3, total_questions: 3, percentage: '100.00' },
  worst: { student_name: 'Bruno Lima', exam_title: 'Prova de Geografia', score: 1, total_questions: 3, percentage: '33.33' },
  top_student: { student_name: 'Ana Souza', exams_count: 2, total_grade: '18.50' },
  exams,
}

async function mountDashboard() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/teacher/dashboard')
  await router.isReady()

  const wrapper = mount(DashboardView, { global: { plugins: [router] } })
  await flushPromises()

  return wrapper
}

function stat(wrapper, modifier) {
  return wrapper.find(`.stat--${modifier}`)
}

describe('DashboardView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
    getDashboardSummary.mockResolvedValue(summary)
    listExams.mockResolvedValue({ items: [], meta: { current_page: 1, last_page: 1, per_page: 10, total: 5 } })
    listStudents.mockResolvedValue(Array.from({ length: 6 }, (_, index) => ({ id: index + 2, name: `Aluno ${index}` })))
  })

  it('shows the page header with the create exam button', async () => {
    const wrapper = await mountDashboard()

    expect(wrapper.find('h1').text()).toBe('Dashboard')
    expect(wrapper.find('.page-header__subtitle').text()).toBe('Acompanhe suas provas e o desempenho da turma.')
    expect(wrapper.find('.button-primary').text()).toBe('Criar nova prova')
    expect(wrapper.find('.button-primary').attributes('href')).toBe('/teacher/exams/new')
  })

  it('loads the summary for all exams', async () => {
    await mountDashboard()

    expect(getDashboardSummary).toHaveBeenCalledWith(1)
  })

  it('shows created exams and students counts', async () => {
    const wrapper = await mountDashboard()

    expect(listExams).toHaveBeenCalledWith(1)
    expect(stat(wrapper, 'exams').find('.stat__value').text()).toBe('5')
    expect(stat(wrapper, 'students').find('.stat__value').text()).toBe('6')
  })

  it('shows the class average as a grade from 0 to 10', async () => {
    const wrapper = await mountDashboard()

    expect(stat(wrapper, 'average').find('.stat__value').text()).toBe('7,1')
  })

  it('shows the global top student', async () => {
    const wrapper = await mountDashboard()

    expect(stat(wrapper, 'top').find('.stat__value').text()).toBe('Ana Souza')
  })

  it('shows the exam averages and the exams ranking panels', async () => {
    const wrapper = await mountDashboard()

    expect(wrapper.findAll('.panel h2').map((title) => title.text())).toEqual([
      'Média das provas',
      'Ranking das médias das provas',
    ])
    expect(wrapper.findAll('.exam')).toHaveLength(2)
    expect(wrapper.find('.exam__grade strong').text()).toBe('6,7')
  })

  it('shows empty metrics without attempts', async () => {
    getDashboardSummary.mockResolvedValue({
      ...summary,
      total_attempts: 0,
      total_students: 0,
      average_percentage: null,
      best: null,
      worst: null,
      top_student: null,
    })

    const wrapper = await mountDashboard()

    expect(wrapper.text()).toContain('Ainda não há tentativas.')
    expect(stat(wrapper, 'average').find('.stat__value').text()).toBe('—')
    expect(stat(wrapper, 'top').find('.stat__value').text()).toBe('—')
  })

  it('shows the API error', async () => {
    getDashboardSummary.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountDashboard()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
