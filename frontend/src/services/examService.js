import api from './api'

export function createExam(teacherId, payload) {
  return api.post(`/teachers/${teacherId}/exams`, payload)
}
