import { beforeEach, describe, expect, it, vi } from 'vitest'

import api from '../api'
import { listAllAvailableExams, listAvailableExams, submitAttempt } from '../studentExamService'

vi.mock('../api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

function page(items, currentPage, lastPage) {
  return { items, meta: { current_page: currentPage, last_page: lastPage, per_page: 50, total: items.length } }
}

describe('studentExamService', () => {
  beforeEach(() => vi.clearAllMocks())

  it('lists a page of available exams', async () => {
    api.get.mockResolvedValue(page([], 1, 1))

    await listAvailableExams(3, 2)

    expect(api.get).toHaveBeenCalledWith('/students/3/exams', { params: { page: 2, per_page: 10 } })
  })

  it('loads every page of available exams', async () => {
    api.get
      .mockResolvedValueOnce(page([{ id: 1 }], 1, 3))
      .mockResolvedValueOnce(page([{ id: 2 }], 2, 3))
      .mockResolvedValueOnce(page([{ id: 3 }], 3, 3))

    const exams = await listAllAvailableExams(3)

    expect(exams.map((exam) => exam.id)).toEqual([1, 2, 3])
    expect(api.get).toHaveBeenNthCalledWith(1, '/students/3/exams', { params: { page: 1, per_page: 50 } })
    expect(api.get).toHaveBeenNthCalledWith(3, '/students/3/exams', { params: { page: 3, per_page: 50 } })
  })

  it('makes a single request when everything fits in one page', async () => {
    api.get.mockResolvedValue(page([{ id: 1 }], 1, 1))

    expect(await listAllAvailableExams(3)).toEqual([{ id: 1 }])
    expect(api.get).toHaveBeenCalledTimes(1)
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
