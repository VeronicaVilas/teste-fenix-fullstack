import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamFormView from '../ExamFormView.vue'
import { routes } from '@/router'
import { createExam, getExam, updateExam } from '@/services/examService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/examService', () => ({ createExam: vi.fn(), getExam: vi.fn(), updateExam: vi.fn() }))

async function mountForm(id = null) {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push(id ? `/teacher/exams/${id}/edit` : '/teacher/exams/new')
  await router.isReady()

  const wrapper = mount(ExamFormView, { props: id ? { id } : {}, global: { plugins: [router] } })
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
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('teacher-exam-detail'), { timeout: 5000 })
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

  describe('edit mode', () => {
    const draft = {
      id: 10,
      title: 'Prova de Geografia',
      description: null,
      status: 'draft',
      questions: [
        {
          id: 20,
          position: 1,
          statement: 'Qual é a capital do Brasil?',
          options: [
            { id: 31, position: 1, text: 'São Paulo', is_correct: false },
            { id: 32, position: 2, text: 'Brasília', is_correct: true },
            { id: 33, position: 3, text: 'Recife', is_correct: false },
          ],
        },
      ],
    }

    it('fills the form with the loaded exam', async () => {
      getExam.mockResolvedValue(draft)

      const { wrapper } = await mountForm('10')

      expect(getExam).toHaveBeenCalledWith(1, '10')
      expect(wrapper.find('h1').text()).toBe('Editar prova')
      expect(wrapper.find('input[placeholder="Ex.: Prova de Geografia"]').element.value).toBe('Prova de Geografia')
      expect(wrapper.find('.question textarea').element.value).toBe('Qual é a capital do Brasil?')
      expect(wrapper.findAll('.option input[type="text"]').map((input) => input.element.value)).toEqual([
        'São Paulo',
        'Brasília',
        'Recife',
      ])
      expect(wrapper.findAll('.option input[type="radio"]')[1].element.checked).toBe(true)
    })

    it('updates the exam and opens its detail', async () => {
      getExam.mockResolvedValue(draft)
      updateExam.mockResolvedValue({ ...draft, title: 'Prova revisada' })
      const { wrapper, router } = await mountForm('10')

      await wrapper.find('input[placeholder="Ex.: Prova de Geografia"]').setValue('Prova revisada')
      await wrapper.find('form').trigger('submit')
      await flushPromises()

      expect(createExam).not.toHaveBeenCalled()
      expect(updateExam).toHaveBeenCalledWith(1, '10', {
        title: 'Prova revisada',
        description: null,
        questions: [
          {
            statement: 'Qual é a capital do Brasil?',
            options: [
              { text: 'São Paulo', is_correct: false },
              { text: 'Brasília', is_correct: true },
              { text: 'Recife', is_correct: false },
            ],
          },
        ],
      })
      await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('teacher-exam-detail'), { timeout: 5000 })
    })

    it('redirects published exams to the detail page', async () => {
      getExam.mockResolvedValue({ ...draft, status: 'published' })

      const { router } = await mountForm('10')

      await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('teacher-exam-detail'), { timeout: 5000 })
    })

    it('goes to the detail page when the exam was published meanwhile', async () => {
      getExam.mockResolvedValue(draft)
      updateExam.mockRejectedValue({ status: 409, message: 'Prova publicada não pode ser alterada.', errors: {} })
      const { wrapper, router } = await mountForm('10')

      await wrapper.find('form').trigger('submit')
      await flushPromises()

      await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('teacher-exam-detail'), { timeout: 5000 })
    })

    it('shows not found for unknown exams', async () => {
      getExam.mockRejectedValue({ status: 404, message: 'Recurso não encontrado.', errors: {} })

      const { wrapper } = await mountForm('99')

      expect(wrapper.text()).toContain('Prova não encontrada.')
      expect(wrapper.find('form').exists()).toBe(false)
    })
  })
})
