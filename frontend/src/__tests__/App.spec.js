import { beforeEach, describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import App from '../App.vue'
import { routes } from '../router'
import { ROLES, useSessionStore } from '../stores/session'

async function mountApp() {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push('/')
  await router.isReady()

  const wrapper = mount(App, { global: { plugins: [router] } })
  await flushPromises()

  return { wrapper, router }
}

describe('App', () => {
  beforeEach(() => {
    localStorage.clear()
    setActivePinia(createPinia())
  })

  it('renders the header and the home route', async () => {
    const { wrapper } = await mountApp()

    expect(wrapper.find('.app-header').text()).toContain('Fênix')
    expect(wrapper.text()).toContain('Fênix — Provas Online')
    expect(wrapper.find('.app-header__session').exists()).toBe(false)
  })

  it('shows the current access and switches back to home', async () => {
    useSessionStore().enterAs(ROLES.STUDENT, { id: 3, name: 'Ana Souza' })
    const { wrapper, router } = await mountApp()

    expect(wrapper.find('.app-header__session').text()).toContain('Acessando como: Ana Souza (Aluno)')

    await wrapper.find('.app-header__session button').trigger('click')
    await flushPromises()

    expect(useSessionStore().role).toBeNull()
    expect(router.currentRoute.value.name).toBe('home')
  })
})
