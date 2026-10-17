import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import AppSidebar from '../AppSidebar.vue'
import { routes } from '@/router'
import { listStudents, listTeachers } from '@/services/userService'
import { ROLES, useSessionStore } from '@/stores/session'

vi.mock('@/services/userService', () => ({
  listTeachers: vi.fn(),
  listStudents: vi.fn(),
}))

const teacher = { id: 1, name: 'Professor Fênix', email: 'teacher@fenix.local' }
const students = [
  { id: 3, name: 'Ana Souza', email: 'ana.souza@fenix.local' },
  { id: 4, name: 'Bruno Lima', email: 'bruno.lima@fenix.local' },
]

async function mountSidebar(path = '/') {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push(path)
  await router.isReady()

  const wrapper = mount(AppSidebar, { global: { plugins: [router] } })
  await flushPromises()

  return { wrapper, router }
}

describe('AppSidebar', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    vi.clearAllMocks()
    listTeachers.mockResolvedValue([teacher])
    listStudents.mockResolvedValue(students)
  })

  it('shows the brand and asks to choose an access', async () => {
    const { wrapper } = await mountSidebar()

    expect(wrapper.find('.brand').text()).toContain('Fênix')
    expect(wrapper.find('.brand').text()).toContain('Provas online')
    expect(wrapper.find('.access__current').text()).toContain('Selecione um acesso')
    expect(wrapper.find('.menu').exists()).toBe(false)
  })

  it('lists the teacher and the students as accesses', async () => {
    const { wrapper } = await mountSidebar()

    await wrapper.find('.access__current').trigger('click')

    expect(wrapper.findAll('.access__group').map((group) => group.text())).toEqual(['Professor', 'Alunos'])
    expect(wrapper.findAll('.access__option').map((option) => option.text())).toEqual([
      'PF Professor Fênix',
      'AS Ana Souza',
      'BL Bruno Lima',
    ])
  })

  it('switches to a student access and opens the student exams', async () => {
    const { wrapper, router } = await mountSidebar()

    await wrapper.find('.access__current').trigger('click')
    await wrapper.findAll('.access__option')[2].trigger('click')
    await flushPromises()

    expect(useSessionStore().userId).toBe(4)
    expect(wrapper.find('.access__menu').exists()).toBe(false)
    expect(wrapper.find('.access__current').text()).toContain('Bruno Lima')
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('student-exams'), { timeout: 5000 })
  })

  it('shows the teacher menu', async () => {
    useSessionStore().enterAs(ROLES.TEACHER, teacher)

    const { wrapper } = await mountSidebar('/teacher/dashboard')

    expect(wrapper.findAll('.menu__item').map((item) => item.text())).toEqual([
      'Dashboard',
      'Minhas provas',
      'Criar prova',
    ])
    expect(wrapper.find('.menu__item--active').text()).toBe('Dashboard')
  })

  it('shows the student menu', async () => {
    useSessionStore().enterAs(ROLES.STUDENT, students[0])

    const { wrapper } = await mountSidebar('/student/exams')

    expect(wrapper.findAll('.menu__item').map((item) => item.text())).toEqual(['Dashboard', 'Minhas provas'])
    expect(wrapper.find('.menu__item--active').text()).toBe('Minhas provas')
    expect(wrapper.find('.access__current').text()).toContain('Aluno')
  })

  it('closes the access list when clicking outside', async () => {
    const { wrapper } = await mountSidebar()

    await wrapper.find('.access__current').trigger('click')
    await wrapper.find('.access__backdrop').trigger('click')

    expect(wrapper.find('.access__menu').exists()).toBe(false)
  })

  it('keeps working when the users cannot be loaded', async () => {
    listTeachers.mockRejectedValue({ status: 0, message: 'Falha', errors: {} })

    const { wrapper } = await mountSidebar()
    await wrapper.find('.access__current').trigger('click')

    expect(wrapper.findAll('.access__option')).toHaveLength(0)
  })
})
