<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
})
</script>

<template>
  <header class="page-header">
    <div class="page-header__text">
      <h1>{{ title }}</h1>
      <p v-if="subtitle" class="page-header__subtitle">{{ subtitle }}</p>
    </div>

    <div v-if="$slots.actions" class="page-header__actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.75rem;
}

.page-header__text {
  min-width: 0;
}

.page-header h1 {
  margin: 0;
  font-size: 2rem;
  color: var(--color-navy);
  overflow-wrap: anywhere;
}

.page-header__subtitle {
  margin: 0.35rem 0 0;
  color: var(--color-muted);
}

.page-header__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
</style>
