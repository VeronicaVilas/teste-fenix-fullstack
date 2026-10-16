import api from './api'

export function listAvailableExams(studentId, page = 1) {
  return api.get(`/students/${studentId}/exams`, { params: { page } })
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
