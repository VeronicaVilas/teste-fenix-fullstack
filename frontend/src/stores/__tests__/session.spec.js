import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import { ROLES, useSessionStore } from '../session'

describe('session store', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('starts without an access', () => {
    const session = useSessionStore()

    expect(session.role).toBeNull()
    expect(session.isTeacher).toBe(false)
    expect(session.isStudent).toBe(false)
  })

  it('enters as a student and persists the access', () => {
    const session = useSessionStore()

    session.enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })

    expect(session.isStudent).toBe(true)
    expect(session.userId).toBe(3)
    expect(session.name).toBe('Ana Souza')
    expect(session.roleLabel).toBe('Aluno')
    expect(JSON.parse(localStorage.getItem('fenix.session'))).toEqual({
      role: 'student',
      userId: 3,
      name: 'Ana Souza',
    })
  })

  it('restores the access after a reload', () => {
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })

    setActivePinia(createPinia())
    const reloaded = useSessionStore()

    expect(reloaded.isTeacher).toBe(true)
    expect(reloaded.roleLabel).toBe('Professor')
    expect(reloaded.name).toBe('Professor Fênix')
  })

  it('leaves and clears the stored access', () => {
    const session = useSessionStore()
    session.enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })

    session.leave()

    expect(session.role).toBeNull()
    expect(session.userId).toBeNull()
    expect(localStorage.getItem('fenix.session')).toBeNull()
  })

  it('ignores a corrupted stored session', () => {
    localStorage.setItem('fenix.session', '{invalid')

    expect(useSessionStore().role).toBeNull()
  })
})
