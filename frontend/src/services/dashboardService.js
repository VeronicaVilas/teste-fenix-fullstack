import api from './api'

export function getDashboardSummary(teacherId, examId = null) {
  return api.get(`/teachers/${teacherId}/dashboard`, {
    params: examId ? { exam_id: examId } : {},
  })
}

export function getDashboardRanking(teacherId, { examId = null, page = 1, perPage = 10 } = {}) {
  return api.get(`/teachers/${teacherId}/dashboard/ranking`, {
    params: { page, per_page: perPage, ...(examId ? { exam_id: examId } : {}) },
  })
}
