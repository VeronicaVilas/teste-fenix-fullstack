import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import ExamRankingPanel from '../ExamRankingPanel.vue'
import { getExamsRanking } from '@/services/dashboardService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/dashboardService', () => ({ getExamsRanking: vi.fn() }))

function item(position, title, average, best, students = 4) {
  return { position, exam_id: position + 10, title, students_count: students, average_percentage: average, best_student_name: best }
}

function page(items, meta = {}) {
  return { items, meta: { current_page: 1, last_page: 1, per_page: 4, total: items.length, ...meta } }
}

async function mountPanel() {
  const wrapper = mount(ExamRankingPanel)
  await flushPromises()
  return wrapper
}

describe('ExamRankingPanel', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  it('shows the exams ranked by average with the best student', async () => {
    getExamsRanking.mockResolvedValue(page([
      item(1, 'Fundamentos de Matemática', '98.00', 'Ana Souza', 1),
      item(2, 'Brasil República', '94.00', 'Bruno Lima'),
    ]))

    const wrapper = await mountPanel()
    const cards = wrapper.findAll('.rank-card')

    expect(getExamsRanking).toHaveBeenCalledWith(1, { page: 1, perPage: 4 })
    expect(cards).toHaveLength(2)
    expect(cards[0].find('.rank-card__position').text()).toBe('1')
    expect(cards[0].find('.rank-card__initials').exists()).toBe(false)
    expect(cards[0].find('.rank-card__info small').text()).toBe('1 aluno')
    expect(cards[0].find('.rank-card__grade strong').text()).toBe('9,8')
    expect(cards[0].find('.rank-card__bar span').attributes('style')).toContain('width: 98%')
    expect(cards[0].find('.rank-card__best').text()).toBe('Melhor aluno: Ana Souza')
    expect(cards[1].find('.rank-card__info small').text()).toBe('4 alunos')
  })

  it('paginates the grid', async () => {
    getExamsRanking.mockResolvedValue(page([item(1, 'Prova A', '90.00', 'Ana Souza')], { last_page: 2, total: 5 }))
    const wrapper = await mountPanel()

    expect(wrapper.findAll('.pagination button').map((button) => button.text())).toEqual(['1', '2'])
    expect(wrapper.find('.panel__page').text()).toBe('Página 1')

    getExamsRanking.mockResolvedValue(page([item(5, 'Prova E', '40.00', 'Elisa Martins')], { current_page: 2, last_page: 2, total: 5 }))
    await wrapper.findAll('.pagination button')[1].trigger('click')
    await flushPromises()

    expect(getExamsRanking).toHaveBeenLastCalledWith(1, { page: 2, perPage: 4 })
    expect(wrapper.find('.panel__page').text()).toBe('Página 2')
    expect(wrapper.find('.rank-card__position').text()).toBe('5')
    expect(wrapper.find('.pagination button.active').text()).toBe('2')
  })

  it('hides pagination with a single page', async () => {
    getExamsRanking.mockResolvedValue(page([item(1, 'Prova A', '90.00', 'Ana Souza')]))

    expect((await mountPanel()).find('.pagination').exists()).toBe(false)
  })

  it('shows the empty state', async () => {
    getExamsRanking.mockResolvedValue(page([]))

    expect((await mountPanel()).text()).toContain('Nenhuma prova respondida ainda.')
  })

  it('shows the API error', async () => {
    getExamsRanking.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountPanel()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
