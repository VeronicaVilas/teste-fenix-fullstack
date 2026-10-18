<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  meta: { type: Object, required: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['change'])

const pages = computed(() => Array.from({ length: props.meta.last_page }, (_, index) => index + 1))

const firstItem = computed(() => (props.meta.current_page - 1) * props.meta.per_page + 1)

const lastItem = computed(() => Math.min(props.meta.current_page * props.meta.per_page, props.meta.total))

function goTo(page) {
  if (props.disabled || page < 1 || page > props.meta.last_page || page === props.meta.current_page) {
    return
  }
  emit('change', page)
}
</script>

<template>
  <nav v-if="meta.last_page > 1" class="pagination" aria-label="Paginação">
    <span class="pagination__info">Mostrando {{ firstItem }}–{{ lastItem }} de {{ meta.total }}</span>

    <div class="pagination__pages">
      <button
        type="button"
        class="pagination__arrow"
        aria-label="Página anterior"
        :disabled="disabled || meta.current_page <= 1"
        @click="goTo(meta.current_page - 1)"
      >
        <ChevronLeft aria-hidden="true" />
      </button>
      <button
        v-for="page in pages"
        :key="page"
        type="button"
        class="pagination__page"
        :class="{ active: page === meta.current_page }"
        :aria-current="page === meta.current_page ? 'page' : undefined"
        :disabled="disabled"
        @click="goTo(page)"
      >
        {{ page }}
      </button>
      <button
        type="button"
        class="pagination__arrow"
        aria-label="Próxima página"
        :disabled="disabled || meta.current_page >= meta.last_page"
        @click="goTo(meta.current_page + 1)"
      >
        <ChevronRight aria-hidden="true" />
      </button>
    </div>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.pagination__info {
  color: var(--color-muted);
  font-size: 0.9rem;
}

.pagination__pages {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

button {
  display: inline-grid;
  place-items: center;
  min-width: 2.4rem;
  height: 2.4rem;
  padding: 0 0.5rem;
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

button:hover:not(:disabled):not(.active) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

button.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
  font-weight: 700;
  cursor: default;
}

button:disabled:not(.active) {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination__arrow svg {
  width: 1.1rem;
  height: 1.1rem;
}
</style>
