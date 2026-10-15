import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import ConfirmDialog from '../ConfirmDialog.vue'

const props = { open: true, title: 'Excluir prova', message: 'Tem certeza?', confirmLabel: 'Excluir' }

describe('ConfirmDialog', () => {
  it('renders nothing when closed', () => {
    const wrapper = mount(ConfirmDialog, { props: { ...props, open: false } })

    expect(wrapper.find('[role="dialog"]').exists()).toBe(false)
  })

  it('shows title, message and confirm label', () => {
    const wrapper = mount(ConfirmDialog, { props })

    expect(wrapper.find('h2').text()).toBe('Excluir prova')
    expect(wrapper.text()).toContain('Tem certeza?')
    expect(wrapper.find('.dialog__confirm').text()).toBe('Excluir')
  })

  it('emits confirm and cancel', async () => {
    const wrapper = mount(ConfirmDialog, { props })
    const [cancel, confirm] = wrapper.findAll('button')

    await confirm.trigger('click')
    await cancel.trigger('click')

    expect(wrapper.emitted('confirm')).toHaveLength(1)
    expect(wrapper.emitted('cancel')).toHaveLength(1)
  })

  it('cancels when clicking outside the dialog', async () => {
    const wrapper = mount(ConfirmDialog, { props })

    await wrapper.find('.dialog-backdrop').trigger('click')

    expect(wrapper.emitted('cancel')).toHaveLength(1)
  })

  it('blocks actions while processing', async () => {
    const wrapper = mount(ConfirmDialog, { props: { ...props, processing: true } })

    expect(wrapper.find('.dialog__confirm').text()).toBe('Processando...')
    wrapper.findAll('button').forEach((button) => expect(button.attributes('disabled')).toBeDefined())

    await wrapper.find('.dialog-backdrop').trigger('click')
    expect(wrapper.emitted('cancel')).toBeUndefined()
  })
})
