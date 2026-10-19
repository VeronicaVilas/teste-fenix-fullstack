import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import StudentDashboardView from '../StudentDashboardView.vue'
import { routes } from '@/router'
import { getStudentDashboard, listAvailableExams } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ getStudentDashboard: vi.fn(), listAvailableExams: vi.fn() }))

function summary(overrides = {}) {
  return { answered_count: 2, pending_count: 1, average_percentage: '65.00', best_percentage: '80.00', ...overrides }
}

async function mountDashboard() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/student/dashboard')
  await router.isReady()

  const wrapper = mount(StudentDashboardView, { global: { plugins: [router] } })
  await flushPromises()
  return wrapper
}

function statValues(wrapper) {
  return wrapper.findAll('.stat').map((stat) => [stat.find('h2').text(), stat.find('.stat__value').text()])
}

describe('StudentDashboardView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    vi.clearAllMocks()
    listAvailableExams.mockResolvedValue({ items: [], meta: { current_page: 1, last_page: 1, per_page: 4, total: 0 } })
  })

  it('shows the counters calculated by the API', async () => {
    getStudentDashboard.mockResolvedValue(summary())

    const wrapper = await mountDashboard()

    expect(getStudentDashboard).toHaveBeenCalledWith(3)
    expect(statValues(wrapper)).toEqual([
      ['Provas realizadas', '2'],
      ['Média de acertos', '65,0%'],
      ['Melhor pontuação', '8,0'],
    ])
  })

  it('shows dashes when no exam was answered', async () => {
    getStudentDashboard.mockResolvedValue(summary({ answered_count: 0, average_percentage: null, best_percentage: null }))

    expect(statValues(await mountDashboard())).toEqual([
      ['Provas realizadas', '0'],
      ['Média de acertos', '—'],
      ['Melhor pontuação', '—'],
    ])
  })

  it('shows the pending exams panel', async () => {
    getStudentDashboard.mockResolvedValue(summary())

    const wrapper = await mountDashboard()

    expect(wrapper.find('.panel h2').text()).toBe('Provas disponíveis')
    expect(listAvailableExams).toHaveBeenCalledWith(3, expect.objectContaining({ status: 'pending' }))
  })

  it('shows the API error', async () => {
    getStudentDashboard.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    const wrapper = await mountDashboard()

    expect(wrapper.text()).toContain('Não foi possível conectar ao servidor.')
    expect(wrapper.find('.stats').exists()).toBe(false)
  })
})
