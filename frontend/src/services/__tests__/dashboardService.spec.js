import { beforeEach, describe, expect, it, vi } from 'vitest'

import api from '../api'
import { getDashboardSummary, getExamAverages, getExamsRanking } from '../dashboardService'

vi.mock('../api', () => ({ default: { get: vi.fn() } }))

describe('dashboardService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    api.get.mockResolvedValue({})
  })

  it('loads the teacher summary', async () => {
    await getDashboardSummary(1)

    expect(api.get).toHaveBeenCalledWith('/teachers/1/dashboard')
  })

  it('loads a page of exam averages, sending the trimmed search only when given', async () => {
    await getExamAverages(1)
    await getExamAverages(1, { page: 2, perPage: 4, search: '  geo ' })
    await getExamAverages(1, { search: '   ' })

    expect(api.get).toHaveBeenNthCalledWith(1, '/teachers/1/dashboard/exam-averages', { params: { page: 1, per_page: 4 } })
    expect(api.get).toHaveBeenNthCalledWith(2, '/teachers/1/dashboard/exam-averages', { params: { page: 2, per_page: 4, search: 'geo' } })
    expect(api.get).toHaveBeenNthCalledWith(3, '/teachers/1/dashboard/exam-averages', { params: { page: 1, per_page: 4 } })
  })

  it('loads a page of the exams ranking', async () => {
    await getExamsRanking(1, { page: 3 })

    expect(api.get).toHaveBeenCalledWith('/teachers/1/dashboard/exams-ranking', { params: { page: 3, per_page: 4 } })
  })
})
