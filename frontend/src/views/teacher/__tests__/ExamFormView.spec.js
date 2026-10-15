import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamFormView from '../ExamFormView.vue'
import { routes } from '@/router'
import { createExam } from '@/services/examService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/examService', () => ({ createExam: vi.fn() }))

async function mountForm() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/teacher/exams/new')
  await router.isReady()

  const wrapper = mount(ExamFormView, { global: { plugins: [router] } })
  await flushPromises()

  return { wrapper, router }
}

async function fillValidExam(wrapper) {
  await wrapper.find('input[placeholder="Ex.: Prova de Geografia"]').setValue('Prova de Geografia')
  await wrapper.find('.question textarea').setValue('Qual é a capital do Brasil?')
  const options = wrapper.findAll('.option input[type="text"]')
  await options[0].setValue('São Paulo')
  await options[1].setValue('Brasília')
  await wrapper.findAll('.option input[type="radio"]')[1].trigger('change')
}

describe('ExamFormView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    vi.clearAllMocks()
  })

  it('starts with one question and two options', async () => {
    const { wrapper } = await mountForm()

    expect(wrapper.findAll('.question')).toHaveLength(1)
    expect(wrapper.findAll('.option')).toHaveLength(2)
  })

  it('adds and removes questions', async () => {
    const { wrapper } = await mountForm()

    await wrapper.find('.exam-form__actions button[type="button"]').trigger('click')
    expect(wrapper.findAll('.question')).toHaveLength(2)

    await wrapper.findAll('button.danger')[1].trigger('click')
    expect(wrapper.findAll('.question')).toHaveLength(1)
  })

  it('sends the exam for the current teacher and opens it', async () => {
    createExam.mockResolvedValue({ id: 10 })
    const { wrapper, router } = await mountForm()

    await fillValidExam(wrapper)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(createExam).toHaveBeenCalledWith(1, {
      title: 'Prova de Geografia',
      description: null,
      questions: [
        {
          statement: 'Qual é a capital do Brasil?',
          options: [
            { text: 'São Paulo', is_correct: false },
            { text: 'Brasília', is_correct: true },
          ],
        },
      ],
    })
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('teacher-exam-detail'))
    expect(router.currentRoute.value.params.id).toBe('10')
  })

  it('shows API validation errors next to each field', async () => {
    createExam.mockRejectedValue({
      status: 422,
      message: 'Os dados enviados são inválidos.',
      errors: {
        title: ['O título é obrigatório.'],
        'questions.0.options': ['Cada questão deve ter exatamente uma alternativa correta.'],
      },
    })
    const { wrapper, router } = await mountForm()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Os dados enviados são inválidos.')
    expect(wrapper.text()).toContain('O título é obrigatório.')
    expect(wrapper.find('.question').text()).toContain('Cada questão deve ter exatamente uma alternativa correta.')
    expect(router.currentRoute.value.name).toBe('teacher-exam-create')
  })
})
