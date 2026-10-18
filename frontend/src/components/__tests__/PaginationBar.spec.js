import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'

import PaginationBar from '../PaginationBar.vue'

const meta = (current, last, total = last * 10) => ({ current_page: current, last_page: last, per_page: 10, total })

function previous(wrapper) {
  return wrapper.find('[aria-label="Página anterior"]')
}

function next(wrapper) {
  return wrapper.find('[aria-label="Próxima página"]')
}

describe('PaginationBar', () => {
  it('is hidden when there is a single page', () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(1, 1) } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })

  it('shows which records are visible', () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 2, 13) } })

    expect(wrapper.find('.pagination__info').text()).toBe('Mostrando 11–13 de 13')
  })

  it('numbers the pages and highlights the current one', () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 3) } })

    expect(wrapper.findAll('.pagination__page').map((button) => button.text())).toEqual(['1', '2', '3'])
    expect(wrapper.find('.pagination__page.active').text()).toBe('2')
    expect(wrapper.find('.pagination__page.active').attributes('aria-current')).toBe('page')
  })

  it('emits the next and previous pages with the arrows', async () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 3) } })

    await previous(wrapper).trigger('click')
    await next(wrapper).trigger('click')

    expect(wrapper.emitted('change')).toEqual([[1], [3]])
  })

  it('emits the clicked page but not the current one', async () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 3) } })
    const pages = wrapper.findAll('.pagination__page')

    await pages[2].trigger('click')
    await pages[1].trigger('click')

    expect(wrapper.emitted('change')).toEqual([[3]])
  })

  it('disables navigation beyond the limits', () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(1, 2) } })

    expect(previous(wrapper).attributes('disabled')).toBeDefined()
    expect(next(wrapper).attributes('disabled')).toBeUndefined()
  })

  it('does not emit while disabled', async () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 3), disabled: true } })

    await next(wrapper).trigger('click')
    await wrapper.findAll('.pagination__page')[0].trigger('click')

    expect(wrapper.emitted('change')).toBeUndefined()
  })
})
