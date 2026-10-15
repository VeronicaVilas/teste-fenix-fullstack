<script setup>
defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
  message: { type: String, required: true },
  confirmLabel: { type: String, default: 'Confirmar' },
  processing: { type: Boolean, default: false },
  danger: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="!processing && emit('cancel')">
    <div class="dialog" role="dialog" aria-modal="true" :aria-label="title">
      <h2>{{ title }}</h2>
      <p>{{ message }}</p>
      <div class="dialog__actions">
        <button type="button" :disabled="processing" @click="emit('cancel')">Cancelar</button>
        <button
          type="button"
          class="dialog__confirm"
          :class="{ 'dialog__confirm--danger': danger }"
          :disabled="processing"
          @click="emit('confirm')"
        >
          {{ processing ? 'Processando...' : confirmLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dialog-backdrop {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(15, 23, 42, 0.45);
  z-index: 10;
}

.dialog {
  width: 100%;
  max-width: 440px;
  padding: 1.5rem;
  background: var(--color-surface);
  border-radius: 8px;
}

.dialog h2 {
  margin: 0 0 0.5rem;
}

.dialog p {
  margin: 0 0 1.25rem;
  color: var(--color-muted);
}

.dialog__actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

button {
  padding: 0.5rem 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}

.dialog__confirm {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}

.dialog__confirm--danger {
  background: var(--color-danger);
  border-color: var(--color-danger);
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
