import api from './api'

export function listTeachers() {
  return api.get('/teachers')
}

export function listStudents() {
  return api.get('/students')
}
