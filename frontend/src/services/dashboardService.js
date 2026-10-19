import api from './api'

export function getDashboardSummary(teacherId) {
  return api.get(`/teachers/${teacherId}/dashboard`)
}

export function getExamAverages(teacherId, { page = 1, perPage = 4, search = '' } = {}) {
  const params = { page, per_page: perPage }
  const term = search.trim()

  if (term) {
    params.search = term
  }

  return api.get(`/teachers/${teacherId}/dashboard/exam-averages`, { params })
}

export function getExamsRanking(teacherId, { page = 1, perPage = 4 } = {}) {
  return api.get(`/teachers/${teacherId}/dashboard/exams-ranking`, {
    params: { page, per_page: perPage },
  })
}
