import api from './api'

export function getDashboardSummary(teacherId, examId = null) {
  return api.get(`/teachers/${teacherId}/dashboard`, {
    params: examId ? { exam_id: examId } : {},
  })
}
