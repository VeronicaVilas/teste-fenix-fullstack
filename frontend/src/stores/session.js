import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export const ROLES = Object.freeze({ TEACHER: 'teacher', STUDENT: 'student' })

const STORAGE_KEY = 'fenix.session'

function readStoredSession() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY)) ?? {}
  } catch {
    return {}
  }
}

function storeSession(session) {
  try {
    if (session) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(session))
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }
  } catch {
    return
  }
}

export const useSessionStore = defineStore('session', () => {
  const stored = readStoredSession()

  const role = ref(stored.role ?? null)
  const userId = ref(stored.userId ?? null)
  const name = ref(stored.name ?? null)

  const isTeacher = computed(() => role.value === ROLES.TEACHER)
  const isStudent = computed(() => role.value === ROLES.STUDENT)
  const roleLabel = computed(() => ({ teacher: 'Professor', student: 'Aluno' })[role.value] ?? '')

  function enterAs(newRole, user) {
    role.value = newRole
    userId.value = user.id
    name.value = user.name
    storeSession({ role: newRole, userId: user.id, name: user.name })
  }

  function leave() {
    role.value = null
    userId.value = null
    name.value = null
    storeSession(null)
  }

  return { role, userId, name, isTeacher, isStudent, roleLabel, enterAs, leave }
})
