import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamAveragesPanel from '../ExamAveragesPanel.vue'
import { getExamAverages } from '@/services/dashboardService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/dashboardService', () => ({ getExamAverages: vi.fn() }))

const exams = [
  { id: 10, title: 'Prova de Geografia', attempts_count: 3, average_percentage: '86.00' },
  { id: 11, title: 'Prova de História', attempts_count: 1, average_percentage: '79.25' },
  { id: 12, title: 'Gramática', attempts_count: 0, average_percentage: null },
]

function page(items, meta = {}) {
  return { items, meta: { current_page: 1, last_page: 1, per_page: 4, total: items.length, ...meta } }
}

async function mountPanel() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/teacher/exams', name: 'teacher-exams', component: { template: '<div />' } },
      { path: '/teacher/exams/:id', name: 'teacher-exam-detail', component: { template: '<div />' } },
    ],
  })

  const wrapper = mount(ExamAveragesPanel, { global: { plugins: [router] } })
  await flushPromises()
  return wrapper
}

describe('ExamAveragesPanel', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('loads the first page from the API and shows each average as a grade', async () => {
    getExamAverages.mockResolvedValue(page(exams))

    const wrapper = await mountPanel()
    const rows = wrapper.findAll('.exam')

    expect(getExamAverages).toHaveBeenCalledWith(1, { page: 1, perPage: 4, search: '' })
    expect(rows).toHaveLength(3)
    expect(rows[0].find('.exam__info strong').text()).toBe('Prova de Geografia')
    expect(rows[0].find('.exam__info small').text()).toBe('3 tentativas')
    expect(rows[0].find('.exam__grade strong').text()).toBe('8,6')
    expect(rows[1].find('.exam__info small').text()).toBe('1 tentativa')
    expect(rows[1].find('.exam__grade strong').text()).toBe('7,9')
    expect(rows[2].find('.exam__grade strong').text()).toBe('—')
  })

  it('links each exam to its detail and the header to all exams', async () => {
    getExamAverages.mockResolvedValue(page(exams))

    const wrapper = await mountPanel()

    expect(wrapper.find('.exam').attributes('href')).toBe('/teacher/exams/10')
    expect(wrapper.find('.panel__link').attributes('href')).toBe('/teacher/exams')
  })

  it('changes page through the API', async () => {
    getExamAverages.mockResolvedValue(page(exams, { last_page: 2, total: 6 }))
    const wrapper = await mountPanel()

    expect(wrapper.findAll('.pagination button').map((button) => button.text())).toEqual(['1', '2'])

    getExamAverages.mockResolvedValue(page([{ id: 15, title: 'Prova 5', attempts_count: 0, average_percentage: null }], { current_page: 2, last_page: 2, total: 6 }))
    await wrapper.findAll('.pagination button')[1].trigger('click')
    await flushPromises()

    expect(getExamAverages).toHaveBeenLastCalledWith(1, { page: 2, perPage: 4, search: '' })
    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova 5'])
    expect(wrapper.find('.pagination button.active').text()).toBe('2')
  })

  it('hides pagination with a single page', async () => {
    getExamAverages.mockResolvedValue(page(exams))

    expect((await mountPanel()).find('.pagination').exists()).toBe(false)
  })

  it('searches on the API from the first page after the user stops typing', async () => {
    getExamAverages.mockResolvedValue(page(exams))
    const wrapper = await mountPanel()
    vi.useFakeTimers()

    await wrapper.find('input[type="search"]').setValue('hist')
    await wrapper.find('input[type="search"]').setValue('histó')
    expect(getExamAverages).toHaveBeenCalledTimes(1)

    getExamAverages.mockResolvedValue(page([exams[1]]))
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(getExamAverages).toHaveBeenCalledTimes(2)
    expect(getExamAverages).toHaveBeenLastCalledWith(1, { page: 1, perPage: 4, search: 'histó' })
    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova de História'])
  })

  it('shows the empty states', async () => {
    getExamAverages.mockResolvedValue(page([]))
    const wrapper = await mountPanel()

    expect(wrapper.text()).toContain('Nenhuma prova publicada ainda.')

    vi.useFakeTimers()
    await wrapper.find('input[type="search"]').setValue('química')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(wrapper.text()).toContain('Nenhuma prova encontrada.')
  })

  it('shows the API error', async () => {
    getExamAverages.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountPanel()).find('.panel__empty--error').text()).toBe('Não foi possível conectar ao servidor.')
  })
})
