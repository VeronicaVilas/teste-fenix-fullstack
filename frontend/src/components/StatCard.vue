<script setup>
defineProps({
  label: { type: String, required: true },
  icon: { type: [Object, Function], required: true },
  value: { type: [String, Number], default: '—' },
  tone: {
    type: String,
    default: 'orange',
    validator: (tone) => ['orange', 'navy', 'green', 'red'].includes(tone),
  },
  compact: { type: Boolean, default: false },
})
</script>

<template>
  <article class="stat" :class="`stat--${tone}`">
    <span class="stat__icon">
      <component :is="icon" aria-hidden="true" />
    </span>
    <div class="stat__body">
      <h2>{{ label }}</h2>
      <p class="stat__value" :class="{ 'stat__value--compact': compact }">
        <slot>{{ value }}</slot>
      </p>
    </div>
  </article>
</template>

<style scoped>
.stat {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  min-width: 0;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.stat__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 3rem;
  height: 3rem;
  border-radius: 12px;
}

.stat__icon svg {
  width: 1.4rem;
  height: 1.4rem;
}

.stat--orange .stat__icon {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.stat--navy .stat__icon {
  background: #e8edf7;
  color: var(--color-navy);
}

.stat--green .stat__icon {
  background: #ebfbee;
  color: var(--color-success);
}

.stat--red .stat__icon {
  background: #fff5f5;
  color: var(--color-danger);
}

.stat__body {
  min-width: 0;
}

.stat h2 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--color-muted);
}

.stat__value {
  margin: 0.35rem 0 0;
  font-size: 1.9rem;
  font-weight: 700;
  line-height: 1.1;
  color: var(--color-navy);
}

.stat__value--compact {
  overflow: hidden;
  font-size: 1.35rem;
  line-height: 1.3;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
