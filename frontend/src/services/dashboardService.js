import api from './api'

export function getDashboardSummary(teacherId) {
  return api.get(`/teachers/${teacherId}/dashboard`)
}

export function getExamsRanking(teacherId, { page = 1, perPage = 4 } = {}) {
  return api.get(`/teachers/${teacherId}/dashboard/exams-ranking`, {
    params: { page, per_page: perPage },
  })
}
