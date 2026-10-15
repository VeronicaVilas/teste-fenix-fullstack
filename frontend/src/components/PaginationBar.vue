<script setup>
const props = defineProps({
  meta: { type: Object, required: true },
  disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['change'])

function goTo(page) {
  if (props.disabled || page < 1 || page > props.meta.last_page || page === props.meta.current_page) {
    return
  }
  emit('change', page)
}
</script>

<template>
  <nav v-if="meta.last_page > 1" class="pagination" aria-label="Paginação">
    <button type="button" :disabled="disabled || meta.current_page <= 1" @click="goTo(meta.current_page - 1)">
      Anterior
    </button>
    <span>Página {{ meta.current_page }} de {{ meta.last_page }} · {{ meta.total }} registros</span>
    <button
      type="button"
      :disabled="disabled || meta.current_page >= meta.last_page"
      @click="goTo(meta.current_page + 1)"
    >
      Próxima
    </button>
  </nav>
</template>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  color: var(--color-muted);
}
</style>
