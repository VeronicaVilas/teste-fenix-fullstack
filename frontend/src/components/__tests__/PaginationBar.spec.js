import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'

import PaginationBar from '../PaginationBar.vue'

const meta = (current, last) => ({ current_page: current, last_page: last, per_page: 10, total: last * 10 })

describe('PaginationBar', () => {
  it('is hidden when there is a single page', () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(1, 1) } })

    expect(wrapper.find('nav').exists()).toBe(false)
  })

  it('emits the next and previous pages', async () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 3) } })
    const [previous, next] = wrapper.findAll('button')

    await previous.trigger('click')
    await next.trigger('click')

    expect(wrapper.emitted('change')).toEqual([[1], [3]])
  })

  it('disables navigation beyond the limits', () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(1, 2) } })
    const [previous, next] = wrapper.findAll('button')

    expect(previous.attributes('disabled')).toBeDefined()
    expect(next.attributes('disabled')).toBeUndefined()
  })

  it('does not emit while disabled', async () => {
    const wrapper = mount(PaginationBar, { props: { meta: meta(2, 3), disabled: true } })

    await wrapper.findAll('button')[1].trigger('click')

    expect(wrapper.emitted('change')).toBeUndefined()
  })
})
