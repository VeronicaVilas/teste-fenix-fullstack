import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import AvailableExamsPanel from '../AvailableExamsPanel.vue'
import { listAvailableExams } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ listAvailableExams: vi.fn() }))

const exams = [
  { id: 10, title: 'Prova de Geografia', questions_count: 3 },
  { id: 11, title: 'Prova de História', questions_count: 1 },
]

function page(items, meta = {}) {
  return { items, meta: { current_page: 1, last_page: 1, per_page: 4, total: items.length, ...meta } }
}

async function mountPanel() {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/student/exams', name: 'student-exams', component: { template: '<div />' } },
      { path: '/student/exams/:id', name: 'student-exam-take', component: { template: '<div />' } },
    ],
  })

  const wrapper = mount(AvailableExamsPanel, { global: { plugins: [router] } })
  await flushPromises()
  return wrapper
}

describe('AvailableExamsPanel', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('loads the first page of pending exams from the API', async () => {
    listAvailableExams.mockResolvedValue(page(exams))

    const wrapper = await mountPanel()
    const rows = wrapper.findAll('.exam')

    expect(listAvailableExams).toHaveBeenCalledWith(3, { page: 1, perPage: 4, status: 'pending', search: '' })
    expect(rows).toHaveLength(2)
    expect(rows[0].find('.exam__info strong').text()).toBe('Prova de Geografia')
    expect(rows[0].find('.exam__info small').text()).toBe('3 questões')
    expect(rows[1].find('.exam__info small').text()).toBe('1 questão')
    expect(rows[0].find('.exam__start').text()).toBe('Iniciar')
    expect(rows[0].find('.exam__start').attributes('href')).toBe('/student/exams/10')
    expect(wrapper.find('.panel__link').attributes('href')).toBe('/student/exams')
  })

  it('changes page through the API', async () => {
    listAvailableExams.mockResolvedValue(page(exams, { last_page: 2, total: 6 }))
    const wrapper = await mountPanel()

    expect(wrapper.findAll('.pagination button').map((button) => button.text())).toEqual(['1', '2'])

    listAvailableExams.mockResolvedValue(page([{ id: 15, title: 'Prova 5', questions_count: 2 }], { current_page: 2, last_page: 2, total: 6 }))
    await wrapper.findAll('.pagination button')[1].trigger('click')
    await flushPromises()

    expect(listAvailableExams).toHaveBeenLastCalledWith(3, expect.objectContaining({ page: 2 }))
    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova 5'])
    expect(wrapper.find('.pagination button.active').text()).toBe('2')
  })

  it('searches on the API from the first page after the user stops typing', async () => {
    listAvailableExams.mockResolvedValue(page(exams))
    const wrapper = await mountPanel()
    vi.useFakeTimers()

    await wrapper.find('input[type="search"]').setValue('hist')
    await wrapper.find('input[type="search"]').setValue('histó')
    expect(listAvailableExams).toHaveBeenCalledTimes(1)

    listAvailableExams.mockResolvedValue(page([exams[1]]))
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(listAvailableExams).toHaveBeenCalledTimes(2)
    expect(listAvailableExams).toHaveBeenLastCalledWith(3, { page: 1, perPage: 4, status: 'pending', search: 'histó' })
    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova de História'])
  })

  it('shows the empty states', async () => {
    listAvailableExams.mockResolvedValue(page([]))
    const wrapper = await mountPanel()

    expect(wrapper.text()).toContain('Nenhuma prova para fazer no momento.')

    vi.useFakeTimers()
    await wrapper.find('input[type="search"]').setValue('química')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(wrapper.text()).toContain('Nenhuma prova encontrada.')
  })

  it('shows the API error', async () => {
    listAvailableExams.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountPanel()).find('.panel__empty--error').text()).toBe('Não foi possível conectar ao servidor.')
  })
})
