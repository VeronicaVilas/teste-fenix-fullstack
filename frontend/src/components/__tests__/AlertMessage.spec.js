import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'

import AlertMessage from '../AlertMessage.vue'

describe('AlertMessage', () => {
  it('renders nothing without a message', () => {
    const wrapper = mount(AlertMessage)

    expect(wrapper.find('[role="alert"]').exists()).toBe(false)
  })

  it('renders the message with its type', () => {
    const wrapper = mount(AlertMessage, { props: { message: 'Prova excluída.', type: 'success' } })

    expect(wrapper.text()).toBe('Prova excluída.')
    expect(wrapper.classes()).toContain('alert--success')
  })
})
