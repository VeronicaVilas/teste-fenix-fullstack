<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, required: true },
})

const labels = {
  draft: 'Rascunho',
  published: 'Publicada',
  pending: 'A fazer',
  answered: 'Realizada',
}

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
  white-space: nowrap;
}

.badge--draft {
  background: #e9ecef;
  color: var(--color-muted);
}

.badge--published,
.badge--answered {
  background: #ebfbee;
  color: var(--color-success);
}

.badge--pending {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}
</style>
