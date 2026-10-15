<script setup>
defineProps({
  message: { type: String, default: '' },
  type: {
    type: String,
    default: 'error',
    validator: (value) => ['error', 'success', 'info'].includes(value),
  },
})
</script>

<template>
  <p v-if="message" class="alert" :class="`alert--${type}`" role="alert">{{ message }}</p>
</template>

<style scoped>
.alert {
  margin: 0 0 1rem;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  border: 1px solid transparent;
}

.alert--error {
  background: #fff5f5;
  border-color: var(--color-danger);
  color: var(--color-danger);
}

.alert--success {
  background: #ebfbee;
  border-color: var(--color-success);
  color: var(--color-success);
}

.alert--info {
  background: #f0f4f8;
  border-color: var(--color-border);
  color: var(--color-text);
}
</style>
