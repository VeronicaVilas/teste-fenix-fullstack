<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'

import AppSidebar from '@/components/AppSidebar.vue'
import AppTopbar from '@/components/AppTopbar.vue'

const route = useRoute()

const menuOpen = ref(false)

function closeMenu() {
  menuOpen.value = false
}

function closeOnEscape(event) {
  if (event.key === 'Escape') {
    closeMenu()
  }
}

watch(() => route.fullPath, closeMenu)

onMounted(() => window.addEventListener('keydown', closeOnEscape))

onBeforeUnmount(() => window.removeEventListener('keydown', closeOnEscape))
</script>

<template>
  <div class="app-layout">
    <AppSidebar :open="menuOpen" @close="closeMenu" />

    <Transition name="fade">
      <div v-if="menuOpen" class="app-backdrop" @click="closeMenu" />
    </Transition>

    <div class="app-content">
      <AppTopbar @open-menu="menuOpen = true" />
      <main class="app-main">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style>
:root {
  --color-navy: #0f1e3d;
  --color-navy-light: #1a2c52;
  --color-navy-border: #263a66;
  --color-sidebar-text: #c7d2e6;
  --color-sidebar-muted: #7d8db0;
  --color-bg: #f4f6fb;
  --color-surface: #ffffff;
  --color-text: #1b2540;
  --color-muted: #5f6b85;
  --color-border: #dde3ee;
  --color-primary: #f97316;
  --color-primary-dark: #ea580c;
  --color-primary-soft: #fff1e6;
  --color-success: #2f9e44;
  --color-danger: #e03131;
}

* {
  box-sizing: border-box;
}

body {
  margin: 0;
  font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
  background: var(--color-bg);
  color: var(--color-text);
}

.app-layout {
  display: grid;
  grid-template-columns: 270px 1fr;
  min-height: 100vh;
}

.app-layout > .sidebar {
  position: sticky;
  top: 0;
  height: 100vh;
}

.app-content {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.app-main {
  width: 100%;
  max-width: 1080px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
}

.app-backdrop {
  display: none;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@media (max-width: 1024px) {
  .app-layout {
    grid-template-columns: 1fr;
  }

  .app-layout > .sidebar {
    position: fixed;
    height: auto;
  }

  .app-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 30;
    background: rgba(11, 22, 46, 0.5);
    backdrop-filter: blur(2px);
  }

  .app-main {
    padding: 1.25rem 1rem;
  }
}
</style>
