import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import App from '../App.vue'
import { routes } from '../router'
import { listAllAvailableExams } from '../services/studentExamService'
import { ROLES, useSessionStore } from '../stores/session'

vi.mock('@/services/userService', () => ({
  listTeachers: vi.fn().mockResolvedValue([]),
  listStudents: vi.fn().mockResolvedValue([]),
}))

vi.mock('@/services/studentExamService', () => ({ listAllAvailableExams: vi.fn() }))

function exam(id, title) {
  return { id, title, questions_count: 2, answered: false, attempt: null }
}

async function mountApp(path = '/') {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push(path)
  await router.isReady()

  const wrapper = mount(App, { global: { plugins: [router] }, attachTo: document.body })
  await flushPromises()

  return { wrapper, router }
}

describe('App', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('renders sidebar, topbar and the current route', async () => {
    const { wrapper } = await mountApp()

    expect(wrapper.find('.sidebar').exists()).toBe(true)
    expect(wrapper.find('.breadcrumb__current').text()).toBe('Início')
    expect(wrapper.find('.app-main').text()).toContain('Fênix — Provas Online')
    wrapper.unmount()
  })

  it('opens the menu from the topbar and closes it on the backdrop', async () => {
    const { wrapper } = await mountApp()

    await wrapper.find('.topbar__menu').trigger('click')
    expect(wrapper.find('.sidebar').classes()).toContain('sidebar--open')
    expect(wrapper.find('.app-backdrop').exists()).toBe(true)

    await wrapper.find('.app-backdrop').trigger('click')
    expect(wrapper.find('.sidebar').classes()).not.toContain('sidebar--open')
    wrapper.unmount()
  })

  it('closes the menu with the close button and the Escape key', async () => {
    const { wrapper } = await mountApp()

    await wrapper.find('.topbar__menu').trigger('click')
    await wrapper.find('.sidebar__close').trigger('click')
    expect(wrapper.find('.sidebar').classes()).not.toContain('sidebar--open')

    await wrapper.find('.topbar__menu').trigger('click')
    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await flushPromises()
    expect(wrapper.find('.sidebar').classes()).not.toContain('sidebar--open')
    wrapper.unmount()
  })

  it('closes the menu after navigating', async () => {
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })
    const { wrapper, router } = await mountApp('/teacher/exams')

    await wrapper.find('.topbar__menu').trigger('click')
    await router.push('/teacher/dashboard')
    await flushPromises()

    expect(wrapper.find('.sidebar').classes()).not.toContain('sidebar--open')
    expect(wrapper.find('.breadcrumb__current').text()).toBe('Dashboard')
    wrapper.unmount()
  })

  it('reloads the current page when switching to another user on the same route', async () => {
    const session = useSessionStore()
    session.enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    listAllAvailableExams.mockResolvedValueOnce([exam(1, 'Prova da Ana')])
    const { wrapper, router } = await mountApp('/student/exams')

    expect(wrapper.find('.student-exams__title').text()).toBe('Prova da Ana')

    listAllAvailableExams.mockResolvedValueOnce([exam(2, 'Prova do Bruno')])
    session.enterAs(ROLES.STUDENT, { id: 4, name: 'Bruno Lima' })
    await router.push('/student/exams')
    await flushPromises()

    expect(listAllAvailableExams).toHaveBeenLastCalledWith(4)
    expect(wrapper.find('.student-exams__title').text()).toBe('Prova do Bruno')
    wrapper.unmount()
  })
})
