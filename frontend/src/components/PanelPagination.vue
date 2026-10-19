<script setup>
import { computed } from 'vue'

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: 'Páginas' },
})

const emit = defineEmits(['change'])

const pages = computed(() => Array.from({ length: props.lastPage }, (_, index) => index + 1))

function goTo(page) {
  if (!props.disabled && page !== props.currentPage) {
    emit('change', page)
  }
}
</script>

<template>
  <nav v-if="lastPage > 1" class="pagination" :aria-label="label">
    <button
      v-for="page in pages"
      :key="page"
      type="button"
      :class="{ active: page === currentPage }"
      :aria-current="page === currentPage ? 'page' : undefined"
      :disabled="disabled"
      @click="goTo(page)"
    >
      {{ page }}
    </button>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

button {
  min-width: 2.4rem;
  height: 2.4rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-muted);
  font: inherit;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease;
}

button:hover:not(.active):not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

button.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
  font-weight: 700;
}

button:disabled:not(.active) {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
