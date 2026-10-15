import { beforeEach, describe, expect, it } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import { roleGuard } from '../index'
import { ROLES, useSessionStore } from '@/stores/session'

describe('roleGuard', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('allows routes without a required role', () => {
    expect(roleGuard({ meta: {} })).toBeUndefined()
  })

  it('redirects to home when there is no access', () => {
    expect(roleGuard({ meta: { role: ROLES.TEACHER } })).toEqual({ name: 'home' })
  })

  it('redirects a student away from teacher routes', () => {
    useSessionStore().enterAs(ROLES.STUDENT, { id: 2, name: 'Aluno Fênix' })

    expect(roleGuard({ meta: { role: ROLES.TEACHER } })).toEqual({ name: 'home' })
  })

  it('allows the matching role', () => {
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })

    expect(roleGuard({ meta: { role: ROLES.TEACHER } })).toBeUndefined()
  })
})
