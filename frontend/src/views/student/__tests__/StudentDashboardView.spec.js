import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import StudentDashboardView from '../StudentDashboardView.vue'
import { routes } from '@/router'
import { listAllAvailableExams } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ listAllAvailableExams: vi.fn() }))

function answered(id, percentage) {
  return {
    id,
    title: `Prova ${id}`,
    questions_count: 10,
    answered: true,
    attempt: { score: percentage / 10, total_questions: 10, percentage: percentage.toFixed(2) },
  }
}

function pending(id) {
  return { id, title: `Pendente ${id}`, questions_count: 5, answered: false, attempt: null }
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
  })

  it('shows the student counters', async () => {
    listAllAvailableExams.mockResolvedValue([answered(1, 80), answered(2, 50), pending(3)])

    const wrapper = await mountDashboard()

    expect(listAllAvailableExams).toHaveBeenCalledWith(3)
    expect(statValues(wrapper)).toEqual([
      ['Provas realizadas', '2'],
      ['Média de acertos', '65,0%'],
      ['Melhor pontuação', '8,0'],
    ])
  })

  it('shows dashes when no exam was answered', async () => {
    listAllAvailableExams.mockResolvedValue([pending(3)])

    expect(statValues(await mountDashboard())).toEqual([
      ['Provas realizadas', '0'],
      ['Média de acertos', '—'],
      ['Melhor pontuação', '—'],
    ])
  })

  it('lists only the pending exams with a start button', async () => {
    listAllAvailableExams.mockResolvedValue([answered(1, 80), pending(3), pending(4)])

    const rows = (await mountDashboard()).findAll('.exam')

    expect(rows.map((row) => row.find('strong').text())).toEqual(['Pendente 3', 'Pendente 4'])
    expect(rows[0].find('.exam__start').attributes('href')).toBe('/student/exams/3')
  })

  it('shows the API error', async () => {
    listAllAvailableExams.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    const wrapper = await mountDashboard()

    expect(wrapper.text()).toContain('Não foi possível conectar ao servidor.')
    expect(wrapper.find('.stats').exists()).toBe(false)
  })
})
