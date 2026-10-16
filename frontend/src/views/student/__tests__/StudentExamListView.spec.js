import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import StudentExamListView from '../StudentExamListView.vue'
import { routes } from '@/router'
import { listAvailableExams } from '@/services/studentExamService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/studentExamService', () => ({ listAvailableExams: vi.fn() }))

const answered = {
  id: 3,
  title: 'Prova de Geografia',
  description: 'Capitais',
  questions_count: 5,
  published_at: '2026-10-15T12:00:00+00:00',
  answered: true,
  attempt: { score: 4, total_questions: 5, percentage: '80.00', submitted_at: '2026-10-15T13:00:00+00:00' },
}

const pending = {
  id: 4,
  title: 'Prova de História',
  description: null,
  questions_count: 8,
  published_at: '2026-10-15T10:00:00+00:00',
  answered: false,
  attempt: null,
}

function page(items, meta = {}) {
  return { items, meta: { current_page: 1, last_page: 1, per_page: 10, total: items.length, ...meta } }
}

async function mountList() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/student/exams')
  await router.isReady()

  const wrapper = mount(StudentExamListView, { global: { plugins: [router] } })
  await flushPromises()
  return wrapper
}

describe('StudentExamListView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    vi.clearAllMocks()
  })

  it('lists available exams for the current student', async () => {
    listAvailableExams.mockResolvedValue(page([answered, pending]))

    const cards = (await mountList()).findAll('.card')

    expect(listAvailableExams).toHaveBeenCalledWith(3, 1)
    expect(cards).toHaveLength(2)
    expect(cards[0].text()).toContain('Prova de Geografia')
    expect(cards[1].text()).toContain('8 questões')
  })

  it('shows the score of answered exams', async () => {
    listAvailableExams.mockResolvedValue(page([answered]))

    const card = (await mountList()).find('.card')

    expect(card.classes()).toContain('card--answered')
    expect(card.text()).toContain('Nota: 4/5 (80%)')
    expect(card.find('a').text()).toBe('Ver resultado')
    expect(card.find('a').attributes('href')).toBe('/student/exams/3/result')
    expect(card.text()).not.toContain('Fazer prova')
  })

  it('offers pending exams to be taken', async () => {
    listAvailableExams.mockResolvedValue(page([pending]))

    const card = (await mountList()).find('.card')

    expect(card.text()).toContain('A fazer')
    expect(card.find('a').text()).toBe('Fazer prova')
    expect(card.find('a').attributes('href')).toBe('/student/exams/4')
  })

  it('loads another page', async () => {
    listAvailableExams.mockResolvedValue(page([pending], { last_page: 2, total: 11 }))
    const wrapper = await mountList()

    await wrapper.findAll('.pagination button')[1].trigger('click')
    await flushPromises()

    expect(listAvailableExams).toHaveBeenLastCalledWith(3, 2)
  })

  it('shows the empty state', async () => {
    listAvailableExams.mockResolvedValue(page([]))

    expect((await mountList()).text()).toContain('Nenhuma prova disponível no momento.')
  })

  it('shows the API error', async () => {
    listAvailableExams.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })

    expect((await mountList()).text()).toContain('Não foi possível conectar ao servidor.')
  })
})
