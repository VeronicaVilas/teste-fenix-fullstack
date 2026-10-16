import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import RankingGrid from '../RankingGrid.vue'
import { getDashboardRanking } from '@/services/dashboardService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/dashboardService', () => ({ getDashboardRanking: vi.fn() }))

function item(position, student, percentage) {
  return {
    position,
    attempt_id: position + 100,
    student_name: student,
    exam_title: 'Prova de Geografia',
    score: 2,
    total_questions: 3,
    percentage,
    submitted_at: '2026-10-16T10:00:00+00:00',
  }
}

function page(items, meta = {}) {
  return { items, meta: { current_page: 1, last_page: 1, per_page: 10, total: items.length, ...meta } }
}

async function mountGrid(props = {}) {
  const wrapper = mount(RankingGrid, { props })
  await flushPromises()
  return wrapper
}

describe('RankingGrid', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  it('renders the ranking rows with medals for the podium', async () => {
    getDashboardRanking.mockResolvedValue(
      page([item(1, 'Ana Souza', '100.00'), item(2, 'Bruno Lima', '90.00'), item(3, 'Carla Mendes', '80.00'), item(4, 'Diego Rocha', '66.67')]),
    )

    const rows = (await mountGrid()).findAll('tbody tr')

    expect(getDashboardRanking).toHaveBeenCalledWith(1, { examId: null, page: 1, perPage: 10 })
    expect(rows).toHaveLength(4)
    expect(rows.map((row) => row.find('.ranking__position').text())).toEqual(['🥇', '🥈', '🥉', '4'])
    expect(rows[3].text()).toContain('Diego Rocha')
    expect(rows[3].text()).toContain('66,67%')
    expect(rows[3].find('.ranking__bar span').attributes('style')).toContain('width: 66.67%')
  })

  it('changes page', async () => {
    getDashboardRanking.mockResolvedValue(page([item(1, 'Ana Souza', '100.00')], { last_page: 2, total: 11 }))
    const wrapper = await mountGrid()

    await wrapper.findAll('.pagination button')[1].trigger('click')
    await flushPromises()

    expect(getDashboardRanking).toHaveBeenLastCalledWith(1, { examId: null, page: 2, perPage: 10 })
  })

  it('changes page size starting from the first page', async () => {
    getDashboardRanking.mockResolvedValue(page([item(1, 'Ana Souza', '100.00')]))
    const wrapper = await mountGrid()

    await wrapper.find('select').setValue(25)
    await flushPromises()

    expect(getDashboardRanking).toHaveBeenLastCalledWith(1, { examId: null, page: 1, perPage: 25 })
  })

  it('reloads from the first page when the exam filter changes', async () => {
    getDashboardRanking.mockResolvedValue(page([item(1, 'Ana Souza', '100.00')]))
    const wrapper = await mountGrid()

    await wrapper.setProps({ examId: 10 })
    await flushPromises()

    expect(getDashboardRanking).toHaveBeenLastCalledWith(1, { examId: 10, page: 1, perPage: 10 })
  })

  it('shows the empty state', async () => {
    getDashboardRanking.mockResolvedValue(page([]))

    expect((await mountGrid()).text()).toContain('Nenhuma tentativa para exibir.')
  })

  it('shows the API error', async () => {
    getDashboardRanking.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountGrid()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
