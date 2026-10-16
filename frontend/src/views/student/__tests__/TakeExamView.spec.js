import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import TakeExamView from '../TakeExamView.vue'
import { routes } from '@/router'
import { getAvailableExam, submitAttempt } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ getAvailableExam: vi.fn(), submitAttempt: vi.fn() }))

function exam(overrides = {}) {
  return {
    id: 10,
    title: 'Prova de Geografia',
    description: 'Capitais',
    questions_count: 2,
    answered: false,
    questions: [
      {
        id: 20,
        position: 1,
        statement: 'Qual é a capital do Brasil?',
        options: [
          { id: 31, position: 1, text: 'São Paulo' },
          { id: 32, position: 2, text: 'Brasília' },
        ],
      },
      {
        id: 21,
        position: 2,
        statement: 'Qual é a capital da Argentina?',
        options: [
          { id: 33, position: 1, text: 'Buenos Aires' },
          { id: 34, position: 2, text: 'Lima' },
        ],
      },
    ],
    ...overrides,
  }
}

async function mountExam() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/student/exams/10')
  await router.isReady()

  const wrapper = mount(TakeExamView, { props: { id: '10' }, global: { plugins: [router] } })
  await flushPromises()

  wrapper.router = router
  return wrapper
}

describe('TakeExamView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    vi.clearAllMocks()
  })

  it('loads the exam for the current student', async () => {
    getAvailableExam.mockResolvedValue(exam())

    const wrapper = await mountExam()

    expect(getAvailableExam).toHaveBeenCalledWith(3, '10')
    expect(wrapper.find('h1').text()).toBe('Prova de Geografia')
    expect(wrapper.findAll('.question')).toHaveLength(2)
    expect(wrapper.text()).toContain('Você só pode enviar esta prova uma vez.')
  })

  it('shows options with letters and no answer key', async () => {
    getAvailableExam.mockResolvedValue(exam())

    const options = (await mountExam()).findAll('.question')[0].findAll('.option')

    expect(options.map((option) => option.text())).toEqual(['ASão Paulo', 'BBrasília'])
    expect(options.some((option) => option.text().includes('Correta'))).toBe(false)
  })

  it('counts the answered questions', async () => {
    getAvailableExam.mockResolvedValue(exam())
    const wrapper = await mountExam()

    expect(wrapper.text()).toContain('Respondidas: 0 de 2')

    await wrapper.findAll('.question')[0].findAll('input[type="radio"]')[1].setValue()

    expect(wrapper.text()).toContain('Respondidas: 1 de 2')
    expect(wrapper.findAll('.question')[0].findAll('.option')[1].classes()).toContain('option--selected')
  })

  it('redirects to the result when the exam was already answered', async () => {
    getAvailableExam.mockResolvedValue(exam({ answered: true }))

    const wrapper = await mountExam()

    expect(wrapper.find('.question').exists()).toBe(false)
    await vi.waitFor(() => expect(wrapper.router.currentRoute.value.name).toBe('student-exam-result'))
  })

  it('shows not found for drafts or missing exams', async () => {
    getAvailableExam.mockRejectedValue({ status: 404, message: 'Recurso não encontrado.', errors: {} })

    expect((await mountExam()).text()).toContain('Prova não encontrada.')
  })

  it('shows other API errors', async () => {
    getAvailableExam.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountExam()).text()).toContain('Não foi possível conectar ao servidor.')
  })

  describe('submission', () => {
    async function answerAll(wrapper) {
      const questions = wrapper.findAll('.question')
      await questions[0].findAll('input[type="radio"]')[1].setValue()
      await questions[1].findAll('input[type="radio"]')[0].setValue()
    }

    function submitButton(wrapper) {
      return wrapper.find('.take-exam__footer button')
    }

    it('enables sending only after every question is answered', async () => {
      getAvailableExam.mockResolvedValue(exam())
      const wrapper = await mountExam()

      expect(submitButton(wrapper).attributes('disabled')).toBeDefined()

      await wrapper.findAll('.question')[0].findAll('input[type="radio"]')[0].setValue()
      expect(submitButton(wrapper).attributes('disabled')).toBeDefined()

      await wrapper.findAll('.question')[1].findAll('input[type="radio"]')[0].setValue()
      expect(submitButton(wrapper).attributes('disabled')).toBeUndefined()
    })

    it('asks for confirmation and opens the result', async () => {
      getAvailableExam.mockResolvedValue(exam())
      submitAttempt.mockResolvedValue({ id: 7, exam_id: 10, score: 2, total_questions: 2, percentage: '100.00' })
      const wrapper = await mountExam()

      await answerAll(wrapper)
      await submitButton(wrapper).trigger('click')
      expect(wrapper.find('[role="dialog"]').text()).toContain('Não será possível alterar depois.')
      expect(submitAttempt).not.toHaveBeenCalled()

      await wrapper.find('.dialog__confirm').trigger('click')
      await flushPromises()

      expect(submitAttempt).toHaveBeenCalledWith(3, '10', { 20: 32, 21: 33 })
      await vi.waitFor(() => expect(wrapper.router.currentRoute.value.name).toBe('student-exam-result'))
      expect(wrapper.router.currentRoute.value.params.id).toBe('10')
    })

    it('opens the result on 409', async () => {
      getAvailableExam.mockResolvedValue(exam())
      submitAttempt.mockRejectedValue({ status: 409, message: 'Você já respondeu esta prova.', errors: {} })
      const wrapper = await mountExam()

      await answerAll(wrapper)
      await submitButton(wrapper).trigger('click')
      await wrapper.find('.dialog__confirm').trigger('click')
      await flushPromises()

      await vi.waitFor(() => expect(wrapper.router.currentRoute.value.name).toBe('student-exam-result'))
    })

    it('keeps the answers and shows the message on 422', async () => {
      getAvailableExam.mockResolvedValue(exam())
      submitAttempt.mockRejectedValue({ status: 422, message: 'Responda todas as questões da prova.', errors: {} })
      const wrapper = await mountExam()

      await answerAll(wrapper)
      await submitButton(wrapper).trigger('click')
      await wrapper.find('.dialog__confirm').trigger('click')
      await flushPromises()

      expect(wrapper.text()).toContain('Responda todas as questões da prova.')
      expect(wrapper.text()).toContain('Respondidas: 2 de 2')
    })
  })
})
