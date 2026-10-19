import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createMemoryHistory, createRouter } from 'vue-router'
import { Trophy } from 'lucide-vue-next'

import BasePanel from '../BasePanel.vue'
import ExamRow from '../ExamRow.vue'
import PageHeader from '../PageHeader.vue'
import PanelPagination from '../PanelPagination.vue'
import SearchField from '../SearchField.vue'
import StatCard from '../StatCard.vue'

describe('PageHeader', () => {
  it('shows the title, the subtitle and the actions', () => {
    const wrapper = mount(PageHeader, {
      props: { title: 'Dashboard', subtitle: 'Resumo da turma' },
      slots: { actions: '<button type="button">Nova prova</button>' },
    })

    expect(wrapper.find('h1').text()).toBe('Dashboard')
    expect(wrapper.find('.page-header__subtitle').text()).toBe('Resumo da turma')
    expect(wrapper.find('.page-header__actions button').text()).toBe('Nova prova')
  })

  it('omits the subtitle and the actions when not given', () => {
    const wrapper = mount(PageHeader, { props: { title: 'Dashboard' } })

    expect(wrapper.find('.page-header__subtitle').exists()).toBe(false)
    expect(wrapper.find('.page-header__actions').exists()).toBe(false)
  })
})

describe('BasePanel', () => {
  it('renders the header with title, subtitle, actions and the content', () => {
    const wrapper = mount(BasePanel, {
      props: { title: 'Questões', subtitle: 'Uma correta por questão' },
      slots: { actions: '<span class="count">3</span>', default: '<p class="content">Lista</p>' },
    })

    expect(wrapper.element.tagName).toBe('SECTION')
    expect(wrapper.find('.panel__header h2').text()).toBe('Questões')
    expect(wrapper.find('.panel__subtitle').text()).toBe('Uma correta por questão')
    expect(wrapper.find('.panel__actions .count').text()).toBe('3')
    expect(wrapper.find('.content').text()).toBe('Lista')
  })

  it('accepts a custom subtitle and tag', () => {
    const wrapper = mount(BasePanel, {
      props: { title: 'Revisão', tag: 'div' },
      slots: { subtitle: '<p class="score">2 de 3</p>' },
    })

    expect(wrapper.element.tagName).toBe('DIV')
    expect(wrapper.find('.score').text()).toBe('2 de 3')
    expect(wrapper.find('.panel__subtitle').exists()).toBe(false)
  })

  it('has no header without title or actions', () => {
    const wrapper = mount(BasePanel, { slots: { default: '<table />' } })

    expect(wrapper.find('.panel__header').exists()).toBe(false)
  })
})

describe('StatCard', () => {
  it('shows the label, the value and the tone', () => {
    const wrapper = mount(StatCard, { props: { label: 'Top 1 geral', icon: Trophy, value: 'Ana Souza', tone: 'navy', compact: true } })

    expect(wrapper.classes()).toContain('stat--navy')
    expect(wrapper.find('h2').text()).toBe('Top 1 geral')
    expect(wrapper.find('.stat__value').text()).toBe('Ana Souza')
    expect(wrapper.find('.stat__value').classes()).toContain('stat__value--compact')
    expect(wrapper.find('.stat__icon svg').exists()).toBe(true)
  })

  it('uses orange by default and accepts custom content', () => {
    const wrapper = mount(StatCard, {
      props: { label: 'Status', icon: Trophy },
      slots: { default: '<span class="badge">Publicada</span>' },
    })

    expect(wrapper.classes()).toContain('stat--orange')
    expect(wrapper.find('.stat__value .badge').text()).toBe('Publicada')
  })
})

describe('PanelPagination', () => {
  it('numbers the pages and emits only other pages', async () => {
    const wrapper = mount(PanelPagination, { props: { currentPage: 2, lastPage: 3 } })
    const buttons = wrapper.findAll('button')

    expect(buttons.map((button) => button.text())).toEqual(['1', '2', '3'])
    expect(wrapper.find('button.active').attributes('aria-current')).toBe('page')

    await buttons[1].trigger('click')
    await buttons[2].trigger('click')

    expect(wrapper.emitted('change')).toEqual([[3]])
  })

  it('is hidden with a single page and does not emit while disabled', async () => {
    expect(mount(PanelPagination, { props: { currentPage: 1, lastPage: 1 } }).find('nav').exists()).toBe(false)

    const wrapper = mount(PanelPagination, { props: { currentPage: 1, lastPage: 2, disabled: true } })
    await wrapper.findAll('button')[1].trigger('click')

    expect(wrapper.emitted('change')).toBeUndefined()
  })
})

describe('SearchField', () => {
  it('binds the typed text', async () => {
    const wrapper = mount(SearchField, { props: { modelValue: '', label: 'Buscar prova', 'onUpdate:modelValue': (value) => wrapper.setProps({ modelValue: value }) } })

    await wrapper.find('input').setValue('geo')

    expect(wrapper.props('modelValue')).toBe('geo')
    expect(wrapper.find('input').attributes('aria-label')).toBe('Buscar prova')
  })
})

describe('ExamRow', () => {
  function mountRow(props) {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [{ path: '/teacher/exams/:id', name: 'teacher-exam-detail', component: { template: '<div />' } }],
    })

    return mount(ExamRow, { props, slots: { default: '<span class="aside">8,0</span>' }, global: { plugins: [router] } })
  }

  it('renders a link when given a route', () => {
    const wrapper = mountRow({ title: 'Prova de Geografia', caption: '3 tentativas', tone: 5, to: { name: 'teacher-exam-detail', params: { id: 10 } } })

    expect(wrapper.element.tagName).toBe('A')
    expect(wrapper.attributes('href')).toBe('/teacher/exams/10')
    expect(wrapper.find('.exam__icon').classes()).toContain('exam__icon--1')
    expect(wrapper.find('.exam__info strong').text()).toBe('Prova de Geografia')
    expect(wrapper.find('.exam__info small').text()).toBe('3 tentativas')
    expect(wrapper.find('.aside').text()).toBe('8,0')
  })

  it('renders a plain block without a route', () => {
    const wrapper = mountRow({ title: 'Prova de História' })

    expect(wrapper.element.tagName).toBe('DIV')
    expect(wrapper.find('.exam__info small').exists()).toBe(false)
  })
})
