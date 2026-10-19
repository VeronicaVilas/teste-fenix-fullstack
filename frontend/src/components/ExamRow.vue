<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { ClipboardList } from 'lucide-vue-next'

const props = defineProps({
  title: { type: String, required: true },
  caption: { type: String, default: '' },
  tone: { type: Number, default: 0 },
  to: { type: Object, default: null },
})

const tag = computed(() => (props.to ? RouterLink : 'div'))
</script>

<template>
  <component :is="tag" :to="to ?? undefined" class="exam" :class="{ 'exam--link': to }">
    <span class="exam__icon" :class="`exam__icon--${tone % 4}`">
      <ClipboardList aria-hidden="true" />
    </span>
    <span class="exam__info">
      <strong>{{ title }}</strong>
      <small v-if="caption">{{ caption }}</small>
    </span>
    <slot />
  </component>
</template>

<style scoped>
.exam {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: inherit;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.exam:hover {
  border-color: var(--color-primary);
  box-shadow: 0 6px 18px rgba(15, 30, 61, 0.08);
}

.exam__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 10px;
}

.exam__icon svg {
  width: 1.2rem;
  height: 1.2rem;
}

.exam__icon--0 {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.exam__icon--1 {
  background: #e8edf7;
  color: var(--color-navy);
}

.exam__icon--2 {
  background: #fde7d4;
  color: #c2410c;
}

.exam__icon--3 {
  background: #dfe6f3;
  color: var(--color-navy-light);
}

.exam__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.exam__info strong {
  color: var(--color-navy);
  overflow-wrap: anywhere;
}

.exam__info small {
  color: var(--color-muted);
}
</style>
