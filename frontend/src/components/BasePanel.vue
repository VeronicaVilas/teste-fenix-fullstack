<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  tag: { type: String, default: 'section' },
})
</script>

<template>
  <component :is="tag" class="panel">
    <header v-if="title || $slots.actions" class="panel__header">
      <div class="panel__heading">
        <h2 v-if="title">{{ title }}</h2>
        <slot name="subtitle">
          <p v-if="subtitle" class="panel__subtitle">{{ subtitle }}</p>
        </slot>
      </div>

      <div v-if="$slots.actions" class="panel__actions">
        <slot name="actions" />
      </div>
    </header>

    <slot />
  </component>
</template>

<style scoped>
.panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  min-width: 0;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.panel__heading {
  min-width: 0;
}

.panel__header h2 {
  margin: 0;
  font-size: 1.2rem;
  color: var(--color-navy);
}

.panel__subtitle {
  margin: 0.3rem 0 0;
  color: var(--color-muted);
}

.panel__actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
}
</style>
