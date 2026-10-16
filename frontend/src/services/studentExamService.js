import api from './api'

export function listAvailableExams(studentId, page = 1) {
  return api.get(`/students/${studentId}/exams`, { params: { page } })
}
