import { beforeEach, describe, expect, it, vi } from 'vitest'

import api from '../api'
import { submitAttempt } from '../studentExamService'

vi.mock('../api', () => ({ default: { get: vi.fn(), post: vi.fn() } }))

describe('studentExamService', () => {
  beforeEach(() => vi.clearAllMocks())

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
