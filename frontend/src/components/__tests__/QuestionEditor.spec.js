import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'

import QuestionEditor from '../QuestionEditor.vue'

function question(optionsCount = 2) {
  return {
    statement: 'Qual é a capital do Brasil?',
    options: Array.from({ length: optionsCount }, (_, index) => ({
      text: `Opção ${index + 1}`,
      is_correct: false,
    })),
  }
}

function mountEditor(props = {}) {
  return mount(QuestionEditor, { props: { modelValue: question(), number: 1, ...props } })
}

function lastEmitted(wrapper) {
  const events = wrapper.emitted('update:modelValue')
  return events[events.length - 1][0]
}

describe('QuestionEditor', () => {
  it('renders the statement and one input per option', () => {
    const wrapper = mountEditor({ modelValue: question(3) })

    expect(wrapper.find('legend').text()).toBe('Questão 1')
    expect(wrapper.find('textarea').element.value).toBe('Qual é a capital do Brasil?')
    expect(wrapper.findAll('input[type="text"]')).toHaveLength(3)
  })

  it('emits the updated statement', async () => {
    const wrapper = mountEditor()

    await wrapper.find('textarea').setValue('Novo enunciado')

    expect(lastEmitted(wrapper).statement).toBe('Novo enunciado')
  })

  it('emits the updated option text', async () => {
    const wrapper = mountEditor()

    await wrapper.findAll('input[type="text"]')[1].setValue('Brasília')

    expect(lastEmitted(wrapper).options[1].text).toBe('Brasília')
    expect(lastEmitted(wrapper).options[0].text).toBe('Opção 1')
  })

  it('marks only one option as correct', async () => {
    const modelValue = question(3)
    modelValue.options[0].is_correct = true
    const wrapper = mountEditor({ modelValue })

    await wrapper.findAll('input[type="radio"]')[2].trigger('change')

    expect(lastEmitted(wrapper).options.map((option) => option.is_correct)).toEqual([false, false, true])
  })

  it('adds options up to five', async () => {
    const wrapper = mountEditor()

    await wrapper.find('.question__actions button').trigger('click')
    expect(lastEmitted(wrapper).options).toHaveLength(3)

    await wrapper.setProps({ modelValue: question(5) })
    expect(wrapper.find('.question__actions button').attributes('disabled')).toBeDefined()
  })

  it('removes options down to two', async () => {
    const wrapper = mountEditor({ modelValue: question(3) })

    await wrapper.findAll('.option__remove')[0].trigger('click')
    expect(lastEmitted(wrapper).options.map((option) => option.text)).toEqual(['Opção 2', 'Opção 3'])

    await wrapper.setProps({ modelValue: question(2) })
    wrapper.findAll('.option__remove').forEach((button) => {
      expect(button.attributes('disabled')).toBeDefined()
    })
  })

  it('shows validation errors next to the fields', () => {
    const wrapper = mountEditor({
      errors: {
        statement: ['O enunciado da questão é obrigatório.'],
        options: ['Cada questão deve ter exatamente uma alternativa correta.'],
        'options.1.text': ['O texto da alternativa é obrigatório.'],
      },
    })

    expect(wrapper.text()).toContain('O enunciado da questão é obrigatório.')
    expect(wrapper.text()).toContain('Cada questão deve ter exatamente uma alternativa correta.')
    expect(wrapper.text()).toContain('O texto da alternativa é obrigatório.')
  })

  it('emits remove only when removable', async () => {
    expect(mountEditor().find('button.danger').exists()).toBe(false)

    const wrapper = mountEditor({ removable: true })
    await wrapper.find('button.danger').trigger('click')

    expect(wrapper.emitted('remove')).toHaveLength(1)
  })

  it('uses icon-only buttons to remove the question and its options', () => {
    const wrapper = mountEditor({ removable: true })
    const removeQuestion = wrapper.find('[aria-label="Remover questão"]')
    const removeOption = wrapper.find('[aria-label="Remover alternativa A"]')

    expect(removeQuestion.find('svg').exists()).toBe(true)
    expect(removeQuestion.text()).toBe('')
    expect(removeOption.find('svg').exists()).toBe(true)
    expect(removeOption.text()).toBe('')
  })

  it('highlights the correct option', () => {
    const options = mountEditor().findAll('.option')

    expect(options.map((option) => option.classes('option--correct'))).toEqual(
      mountEditor().props('modelValue').options.map((option) => option.is_correct),
    )
  })
})
