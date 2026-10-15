<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const labels = { draft: 'Rascunho', published: 'Publicada' }

const label = computed(() => labels[props.status] ?? props.status)
</script>

<template>
  <span class="badge" :class="`badge--${status}`">{{ label }}</span>
</template>

<style scoped>
.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge--draft {
  background: #e9ecef;
  color: var(--color-muted);
}

.badge--published {
  background: #ebfbee;
  color: var(--color-success);
}
</style>
