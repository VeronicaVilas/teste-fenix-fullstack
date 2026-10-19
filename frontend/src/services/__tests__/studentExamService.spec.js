import { beforeEach, describe, expect, it, vi } from 'vitest'

import api from '../api'
import { getStudentDashboard, listAvailableExams, submitAttempt } from '../studentExamService'

vi.mock('../api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

describe('studentExamService', () => {
  beforeEach(() => vi.clearAllMocks())

  it('loads the student dashboard', async () => {
    api.get.mockResolvedValue({ answered_count: 1 })

    await getStudentDashboard(3)

    expect(api.get).toHaveBeenCalledWith('/students/3/dashboard')
  })

  it('lists a page of available exams without filters by default', async () => {
    api.get.mockResolvedValue({ items: [], meta: {} })

    await listAvailableExams(3)

    expect(api.get).toHaveBeenCalledWith('/students/3/exams', { params: { page: 1, per_page: 10 } })
  })

  it('sends the status and the trimmed search when given', async () => {
    api.get.mockResolvedValue({ items: [], meta: {} })

    await listAvailableExams(3, { page: 2, perPage: 4, status: 'pending', search: '  geo ' })

    expect(api.get).toHaveBeenCalledWith('/students/3/exams', {
      params: { page: 2, per_page: 4, status: 'pending', search: 'geo' },
    })
  })

  it('ignores a blank search', async () => {
    api.get.mockResolvedValue({ items: [], meta: {} })

    await listAvailableExams(3, { search: '   ' })

    expect(api.get).toHaveBeenCalledWith('/students/3/exams', { params: { page: 1, per_page: 10 } })
  })

  it('converts the answers map into the API payload', async () => {
    api.post.mockResolvedValue({ id: 7 })

    await submitAttempt(3, '10', { 20: 32, 21: 33 })

    expect(api.post).toHaveBeenCalledWith('/students/3/exams/10/attempt', {
      answers: [
        { question_id: 20, option_id: 32 },
        { question_id: 21, option_id: 33 },
      ],
    })
  })
})
