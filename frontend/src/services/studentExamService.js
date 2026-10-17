import api from './api'

const MAX_PER_PAGE = 50

export function listAvailableExams(studentId, page = 1, perPage = 10) {
  return api.get(`/students/${studentId}/exams`, { params: { page, per_page: perPage } })
}

export async function listAllAvailableExams(studentId) {
  const first = await listAvailableExams(studentId, 1, MAX_PER_PAGE)
  const remaining = await Promise.all(
    Array.from({ length: first.meta.last_page - 1 }, (_, index) =>
      listAvailableExams(studentId, index + 2, MAX_PER_PAGE),
    ),
  )

  return [first, ...remaining].flatMap((page) => page.items)
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
