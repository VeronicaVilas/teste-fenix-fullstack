import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import StatusBadge from '../StatusBadge.vue'

describe('StatusBadge', () => {
  it('shows draft as Rascunho', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'draft' } })

    expect(wrapper.text()).toBe('Rascunho')
    expect(wrapper.classes()).toContain('badge--draft')
  })

  it('shows published as Publicada', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'published' } })

    expect(wrapper.text()).toBe('Publicada')
    expect(wrapper.classes()).toContain('badge--published')
  })
})
