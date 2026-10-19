import api from './api'

export function getStudentDashboard(studentId) {
  return api.get(`/students/${studentId}/dashboard`)
}

export function listAvailableExams(studentId, { page = 1, perPage = 10, status = null, search = '' } = {}) {
  const params = { page, per_page: perPage }
  const term = search.trim()

  if (status) {
    params.status = status
  }

  if (term) {
    params.search = term
  }

  return api.get(`/students/${studentId}/exams`, { params })
}

export function getAvailableExam(studentId, examId) {
  return api.get(`/students/${studentId}/exams/${examId}`)
}

export function getAttemptResult(studentId, examId) {
  return api.get(`/students/${studentId}/exams/${examId}/attempt`)
}

export function submitAttempt(studentId, examId, answers) {
  return api.post(`/students/${studentId}/exams/${examId}/attempt`, {
    answers: Object.entries(answers).map(([questionId, optionId]) => ({
      question_id: Number(questionId),
      option_id: optionId,
    })),
  })
}
