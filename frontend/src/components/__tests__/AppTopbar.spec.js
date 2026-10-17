import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { createMemoryHistory, createRouter } from 'vue-router'

import AppTopbar from '../AppTopbar.vue'
import { routes } from '@/router'
import { ROLES, useSessionStore } from '@/stores/session'

async function mountTopbar(path) {
  const router = createRouter({ history: createMemoryHistory(), routes })
  router.push(path)
  await router.isReady()

  return mount(AppTopbar, { global: { plugins: [router] } })
}

describe('AppTopbar', () => {
  it('shows the current page in the breadcrumb', async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    useSessionStore().enterAs(ROLES.TEACHER, { id: 1, name: 'Professor Fênix' })

    const wrapper = await mountTopbar('/teacher/dashboard')

    expect(wrapper.find('.breadcrumb__root').text()).toBe('Fênix')
    expect(wrapper.find('.breadcrumb__current').text()).toBe('Dashboard')
  })

  it('names the home page', async () => {
    setActivePinia(createPinia())
    localStorage.clear()

    expect((await mountTopbar('/')).find('.breadcrumb__current').text()).toBe('Início')
  })

  it('asks to open the menu', async () => {
    setActivePinia(createPinia())
    localStorage.clear()
    const wrapper = await mountTopbar('/')

    await wrapper.find('.topbar__menu').trigger('click')

    expect(wrapper.emitted('open-menu')).toHaveLength(1)
  })
})
