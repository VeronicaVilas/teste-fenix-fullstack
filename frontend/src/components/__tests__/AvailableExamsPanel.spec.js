import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'

import AvailableExamsPanel from '../AvailableExamsPanel.vue'

const exams = [
  { id: 10, title: 'Prova de Geografia', questions_count: 3 },
  { id: 11, title: 'Prova de História', questions_count: 1 },
]

function manyExams(count) {
  return Array.from({ length: count }, (_, index) => ({ id: index + 1, title: `Prova ${index + 1}`, questions_count: 2 }))
}

function mountPanel(list = exams) {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/student/exams', name: 'student-exams', component: { template: '<div />' } },
      { path: '/student/exams/:id', name: 'student-exam-take', component: { template: '<div />' } },
    ],
  })

  return mount(AvailableExamsPanel, { props: { exams: list }, global: { plugins: [router] } })
}

describe('AvailableExamsPanel', () => {
  it('lists each exam with a start button', () => {
    const wrapper = mountPanel()
    const rows = wrapper.findAll('.exam')

    expect(rows).toHaveLength(2)
    expect(rows[0].find('.exam__info strong').text()).toBe('Prova de Geografia')
    expect(rows[0].find('.exam__info small').text()).toBe('3 questões')
    expect(rows[1].find('.exam__info small').text()).toBe('1 questão')
    expect(rows[0].find('.exam__start').text()).toBe('Iniciar')
    expect(rows[0].find('.exam__start').attributes('href')).toBe('/student/exams/10')
    expect(wrapper.find('.panel__link').attributes('href')).toBe('/student/exams')
  })

  it('searches by title', async () => {
    const wrapper = mountPanel()

    await wrapper.find('input[type="search"]').setValue('histó')

    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova de História'])
  })

  it('shows four exams per page and changes page', async () => {
    const wrapper = mountPanel(manyExams(6))

    expect(wrapper.findAll('.exam')).toHaveLength(4)

    await wrapper.findAll('.pagination button')[1].trigger('click')

    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova 5', 'Prova 6'])
  })

  it('goes back to the first page when searching', async () => {
    const wrapper = mountPanel(manyExams(6))

    await wrapper.findAll('.pagination button')[1].trigger('click')
    await wrapper.find('input[type="search"]').setValue('Prova 1')

    expect(wrapper.findAll('.exam').map((row) => row.find('strong').text())).toEqual(['Prova 1'])
  })

  it('shows the empty states', async () => {
    expect(mountPanel([]).text()).toContain('Nenhuma prova para fazer no momento.')

    const wrapper = mountPanel()
    await wrapper.find('input[type="search"]').setValue('química')

    expect(wrapper.text()).toContain('Nenhuma prova encontrada.')
  })
})
