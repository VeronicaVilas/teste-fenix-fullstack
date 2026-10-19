<script setup>
import { Search } from 'lucide-vue-next'

const model = defineModel({ type: String, default: '' })

defineProps({
  placeholder: { type: String, default: 'Buscar...' },
  label: { type: String, default: 'Buscar' },
})
</script>

<template>
  <label class="search">
    <Search aria-hidden="true" />
    <input v-model="model" type="search" :placeholder="placeholder" :aria-label="label" />
  </label>
</template>

<style scoped>
.search {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 0.85rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: var(--color-muted);
}

.search:focus-within {
  border-color: var(--color-primary);
}

.search svg {
  flex-shrink: 0;
  width: 1.1rem;
  height: 1.1rem;
}

.search input {
  width: 100%;
  padding: 0.75rem 0;
  border: none;
  outline: none;
  background: none;
  color: var(--color-text);
  font: inherit;
}
</style>
