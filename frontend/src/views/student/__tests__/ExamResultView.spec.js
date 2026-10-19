import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import ExamResultView from '../ExamResultView.vue'
import { routes } from '@/router'
import { getAttemptResult } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ getAttemptResult: vi.fn() }))

const result = {
  id: 7,
  exam: { id: 10, title: 'Prova de Geografia' },
  score: 1,
  total_questions: 2,
  percentage: '50.00',
  submitted_at: '2026-10-16T10:00:00+00:00',
  questions: [
    {
      id: 1,
      position: 1,
      statement: 'Qual é a capital do Brasil?',
      selected_option_id: 11,
      correct_option_id: 12,
      is_correct: false,
      options: [
        { id: 11, position: 1, text: 'São Paulo', is_correct: false },
        { id: 12, position: 2, text: 'Brasília', is_correct: true },
      ],
    },
    {
      id: 2,
      position: 2,
      statement: 'Qual é a capital da Argentina?',
      selected_option_id: 21,
      correct_option_id: 21,
      is_correct: true,
      options: [
        { id: 21, position: 1, text: 'Buenos Aires', is_correct: true },
        { id: 22, position: 2, text: 'Lima', is_correct: false },
      ],
    },
  ],
}

async function mountResult() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/student/exams/10/result')
  await router.isReady()

  const wrapper = mount(ExamResultView, { props: { id: '10' }, global: { plugins: [router] } })
  await flushPromises()

  return wrapper
}

describe('ExamResultView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    vi.clearAllMocks()
  })

  it('shows the score summary', async () => {
    getAttemptResult.mockResolvedValue(result)

    const wrapper = await mountResult()

    expect(getAttemptResult).toHaveBeenCalledWith(3, '10')
    expect(wrapper.find('h1').text()).toBe('Prova de Geografia')
    expect(wrapper.find('.summary__score').text()).toBe('1 de 2 acertos — 50%')
    expect(wrapper.find('.summary__bar span').attributes('style')).toContain('width: 50%')
  })

  it('shows the grade, percentage, right and wrong answers as stat cards', async () => {
    getAttemptResult.mockResolvedValue(result)

    const stats = (await mountResult()).findAll('.stat')

    expect(stats.map((stat) => [stat.find('h2').text(), stat.find('.stat__value').text()])).toEqual([
      ['Nota', '5,0'],
      ['Aproveitamento', '50%'],
      ['Acertos', '1'],
      ['Erros', '1'],
    ])
  })

  it('links back to the student exams', async () => {
    getAttemptResult.mockResolvedValue(result)

    expect((await mountResult()).find('.button-secondary').attributes('href')).toBe('/student/exams')
  })

  it('highlights the wrong answer and the correct one', async () => {
    getAttemptResult.mockResolvedValue(result)

    const [wrong, right] = (await mountResult()).findAll('.question')
    const [selected, correct] = wrong.findAll('.option')

    expect(wrong.classes()).toContain('question--wrong')
    expect(wrong.text()).toContain('✗ Você errou')
    expect(selected.classes()).toContain('option--wrong')
    expect(selected.text()).toContain('Sua resposta')
    expect(correct.classes()).toContain('option--correct')
    expect(correct.text()).toContain('Resposta correta')

    expect(right.classes()).toContain('question--correct')
    expect(right.text()).toContain('✓ Você acertou')
    expect(right.findAll('.option')[0].text()).toContain('Sua resposta')
  })

  it('filters only the wrong answers', async () => {
    getAttemptResult.mockResolvedValue(result)
    const wrapper = await mountResult()

    await wrapper.find('.filter input').setValue(true)

    const questions = wrapper.findAll('.question')
    expect(questions).toHaveLength(1)
    expect(questions[0].text()).toContain('Qual é a capital do Brasil?')
  })

  it('celebrates when nothing was wrong', async () => {
    getAttemptResult.mockResolvedValue({ ...result, questions: [result.questions[1]] })
    const wrapper = await mountResult()

    await wrapper.find('.filter input').setValue(true)

    expect(wrapper.text()).toContain('Você acertou todas as questões!')
  })

  it('offers to take the exam when it was not answered yet', async () => {
    getAttemptResult.mockRejectedValue({ status: 404, message: 'Você ainda não respondeu esta prova.', errors: {} })

    const wrapper = await mountResult()

    expect(wrapper.text()).toContain('Você ainda não respondeu esta prova.')
    expect(wrapper.find('a.button-primary').attributes('href')).toBe('/student/exams/10')
  })

  it('shows other API errors', async () => {
    getAttemptResult.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountResult()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
