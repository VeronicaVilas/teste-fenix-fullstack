<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Menu } from 'lucide-vue-next'

const emit = defineEmits(['open-menu'])

const route = useRoute()

const title = computed(() => route.meta.title ?? '')
</script>

<template>
  <header class="topbar">
    <button type="button" class="topbar__menu" aria-label="Abrir menu" @click="emit('open-menu')">
      <Menu aria-hidden="true" />
    </button>

    <nav class="breadcrumb" aria-label="Você está em">
      <span class="breadcrumb__root">Fênix</span>
      <span class="breadcrumb__separator" aria-hidden="true">/</span>
      <strong class="breadcrumb__current">{{ title }}</strong>
    </nav>
  </header>
</template>

<style scoped>
.topbar {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 1rem;
  min-height: 4rem;
  padding: 0 1.5rem;
  background: var(--color-navy);
  border-bottom: 3px solid var(--color-primary);
}

.topbar__menu {
  display: none;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  padding: 0;
  background: var(--color-navy-light);
  border: 1px solid var(--color-navy-border);
  border-radius: 10px;
  color: #fff;
  cursor: pointer;
  transition: background 0.2s ease;
}

.topbar__menu:hover {
  background: var(--color-primary);
  border-color: var(--color-primary);
}

.breadcrumb {
  display: flex;
  align-items: baseline;
  gap: 0.6rem;
  min-width: 0;
  font-size: 1.05rem;
}

.breadcrumb__root {
  color: var(--color-sidebar-text);
}

.breadcrumb__separator {
  color: var(--color-primary);
  font-weight: 700;
}

.breadcrumb__current {
  overflow: hidden;
  color: #fff;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 1024px) {
  .topbar {
    padding: 0 1rem;
  }

  .topbar__menu {
    display: grid;
  }
}
</style>
