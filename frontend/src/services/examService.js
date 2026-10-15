import api from './api'

export function listExams(teacherId, { status = null, page = 1 } = {}) {
  return api.get(`/teachers/${teacherId}/exams`, {
    params: { page, ...(status ? { status } : {}) },
  })
}

export function getExam(teacherId, examId) {
  return api.get(`/teachers/${teacherId}/exams/${examId}`)
}

export function createExam(teacherId, payload) {
  return api.post(`/teachers/${teacherId}/exams`, payload)
}
