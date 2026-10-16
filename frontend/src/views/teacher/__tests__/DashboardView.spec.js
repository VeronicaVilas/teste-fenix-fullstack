import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import DashboardView from '../DashboardView.vue'
import { getDashboardSummary } from '@/services/dashboardService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/dashboardService', () => ({ getDashboardSummary: vi.fn() }))

const exams = [
  { id: 10, title: 'Prova de Geografia', attempts_count: 3, average_percentage: '66.67', best_percentage: '100.00', worst_percentage: '33.33' },
  { id: 11, title: 'Prova de História', attempts_count: 0, average_percentage: null, best_percentage: null, worst_percentage: null },
]

const summary = {
  exam_id: null,
  total_attempts: 3,
  total_students: 3,
  average_percentage: '66.67',
  best: { student_name: 'Ana Souza', exam_title: 'Prova de Geografia', score: 3, total_questions: 3, percentage: '100.00' },
  worst: { student_name: 'Bruno Lima', exam_title: 'Prova de Geografia', score: 1, total_questions: 3, percentage: '33.33' },
  exams,
}

async function mountDashboard() {
  const wrapper = mount(DashboardView)
  await flushPromises()
  return wrapper
}

describe('DashboardView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  it('shows the class average, best and worst scores', async () => {
    getDashboardSummary.mockResolvedValue(summary)

    const wrapper = await mountDashboard()

    expect(getDashboardSummary).toHaveBeenCalledWith(1, null)
    expect(wrapper.find('.card--average .card__value').text()).toBe('66,67%')
    expect(wrapper.find('.card--best').text()).toContain('100%')
    expect(wrapper.find('.card--best').text()).toContain('Ana Souza')
    expect(wrapper.find('.card--worst').text()).toContain('Bruno Lima')
    expect(wrapper.find('.card--worst').text()).toContain('1/3 acertos')
  })

  it('lists the average of each exam', async () => {
    getDashboardSummary.mockResolvedValue(summary)

    const rows = (await mountDashboard()).findAll('tbody tr')

    expect(rows).toHaveLength(2)
    expect(rows[0].text()).toContain('Prova de Geografia')
    expect(rows[0].text()).toContain('66,67%')
    expect(rows[1].findAll('td')[2].text()).toBe('—')
  })

  it('filters by exam using the exams of the response', async () => {
    getDashboardSummary.mockResolvedValue(summary)
    const wrapper = await mountDashboard()

    const options = wrapper.findAll('select option')
    expect(options.map((option) => option.text())).toEqual(['Todas', 'Prova de Geografia', 'Prova de História'])

    await wrapper.find('select').setValue(10)
    await flushPromises()

    expect(getDashboardSummary).toHaveBeenLastCalledWith(1, 10)
    expect(wrapper.find('tbody tr').classes()).toContain('selected')
  })

  it('shows the empty state without attempts', async () => {
    getDashboardSummary.mockResolvedValue({
      ...summary,
      total_attempts: 0,
      total_students: 0,
      average_percentage: null,
      best: null,
      worst: null,
    })

    const wrapper = await mountDashboard()

    expect(wrapper.text()).toContain('Ainda não há tentativas.')
    expect(wrapper.find('.cards').exists()).toBe(false)
  })

  it('shows the API error', async () => {
    getDashboardSummary.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountDashboard()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
