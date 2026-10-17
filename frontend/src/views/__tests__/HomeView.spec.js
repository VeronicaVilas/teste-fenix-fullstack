import { beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import HomeView from '../HomeView.vue'
import { routes } from '@/router'
import { useSessionStore } from '@/stores/session'
import { listStudents, listTeachers } from '@/services/userService'

vi.mock('@/services/userService', () => ({
  listTeachers: vi.fn(),
  listStudents: vi.fn(),
}))

async function mountHome() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/')
  await router.isReady()

  const wrapper = mount(HomeView, { global: { plugins: [router] } })
  await flushPromises()

  return { wrapper, router }
}

describe('HomeView', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('shows the teacher and student accesses', async () => {
    const { wrapper } = await mountHome()

    expect(wrapper.text()).toContain('Professor')
    expect(wrapper.text()).toContain('Aluno')
  })

  it('enters as the first teacher', async () => {
    listTeachers.mockResolvedValue([{ id: 1, name: 'Professor Fênix', email: 'teacher@fenix.local' }])
    const { wrapper, router } = await mountHome()

    await wrapper.findAll('.access-card')[0].trigger('click')
    await flushPromises()

    expect(useSessionStore().isTeacher).toBe(true)
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('teacher-exams'), { timeout: 5000 })
  })

  it('lists the students and enters as the chosen one', async () => {
    listStudents.mockResolvedValue([
      { id: 3, name: 'Ana Souza', email: 'ana.souza@fenix.local' },
      { id: 4, name: 'Bruno Lima', email: 'bruno.lima@fenix.local' },
    ])
    const { wrapper, router } = await mountHome()

    await wrapper.findAll('.access-card')[1].trigger('click')
    await flushPromises()

    const options = wrapper.findAll('.student-option')
    expect(options.map((option) => option.text())).toEqual(['Ana Souza', 'Bruno Lima'])

    await options[1].trigger('click')
    await flushPromises()

    expect(useSessionStore().userId).toBe(4)
    await vi.waitFor(() => expect(router.currentRoute.value.name).toBe('student-exams'), { timeout: 5000 })
  })

  it('shows the API error message', async () => {
    listStudents.mockRejectedValue({ status: 0, message: 'Não foi possível conectar ao servidor.', errors: {} })
    const { wrapper } = await mountHome()

    await wrapper.findAll('.access-card')[1].trigger('click')
    await flushPromises()

    expect(wrapper.text()).toContain('Não foi possível conectar ao servidor.')
  })
})
