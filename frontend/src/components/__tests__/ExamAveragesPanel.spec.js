import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamAveragesPanel from '../ExamAveragesPanel.vue'

const exams = [
  { id: 10, title: 'Prova de Geografia', attempts_count: 3, average_percentage: '86.00' },
  { id: 11, title: 'Prova de História', attempts_count: 1, average_percentage: '79.25' },
  { id: 12, title: 'Gramática', attempts_count: 0, average_percentage: null },
]

function manyExams(count) {
  return Array.from({ length: count }, (_, index) => ({
    id: index + 1,
    title: `Prova ${index + 1}`,
    attempts_count: 1,
    average_percentage: '50.00',
  }))
}

function mountPanel(list = exams) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/teacher/exams', name: 'teacher-exams', component: { template: '<div />' } },
      { path: '/teacher/exams/:id', name: 'teacher-exam-detail', component: { template: '<div />' } },
    ],
  })

  return mount(ExamAveragesPanel, { props: { exams: list }, global: { plugins: [router] } })
}

describe('ExamAveragesPanel', () => {
  it('lists each exam with its average as a grade', () => {
    const rows = mountPanel().findAll('.exam')

    expect(rows).toHaveLength(3)
    expect(rows[0].find('.exam__info strong').text()).toBe('Prova de Geografia')
    expect(rows[0].find('.exam__info small').text()).toBe('3 tentativas')
    expect(rows[0].find('.exam__grade strong').text()).toBe('8,6')
    expect(rows[1].find('.exam__info small').text()).toBe('1 tentativa')
    expect(rows[1].find('.exam__grade strong').text()).toBe('7,9')
    expect(rows[2].find('.exam__grade strong').text()).toBe('—')
  })

  it('links each exam to its detail and the header to all exams', () => {
    const wrapper = mountPanel()

    expect(wrapper.find('.exam').attributes('href')).toBe('/teacher/exams/10')
    expect(wrapper.find('.panel__link').attributes('href')).toBe('/teacher/exams')
  })

  it('searches by title', async () => {
    const wrapper = mountPanel()

    await wrapper.find('input[type="search"]').setValue('histó')

    expect(wrapper.findAll('.exam')).toHaveLength(1)
    expect(wrapper.find('.exam__info strong').text()).toBe('Prova de História')
  })

  it('has no attempts filter', () => {
    expect(mountPanel().find('select').exists()).toBe(false)
  })

  it('shows four exams per page and changes page', async () => {
    const wrapper = mountPanel(manyExams(6))

    expect(wrapper.findAll('.exam')).toHaveLength(4)
    expect(wrapper.findAll('.pagination button').map((button) => button.text())).toEqual(['1', '2'])

    await wrapper.findAll('.pagination button')[1].trigger('click')

    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova 5', 'Prova 6'])
    expect(wrapper.find('.pagination button.active').text()).toBe('2')
  })

  it('goes back to the first page when searching', async () => {
    const wrapper = mountPanel(manyExams(6))

    await wrapper.findAll('.pagination button')[1].trigger('click')
    await wrapper.find('input[type="search"]').setValue('Prova 1')

    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova 1'])
    expect(wrapper.find('.pagination').exists()).toBe(false)
  })

  it('hides pagination with a single page', () => {
    expect(mountPanel().find('.pagination').exists()).toBe(false)
  })

  it('shows an empty state when nothing matches', async () => {
    const wrapper = mountPanel()

    await wrapper.find('input[type="search"]').setValue('química')

    expect(wrapper.text()).toContain('Nenhuma prova encontrada.')
  })
})
