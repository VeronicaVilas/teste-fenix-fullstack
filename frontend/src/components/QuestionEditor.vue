<script setup>
import { useId } from 'vue'

const MIN_OPTIONS = 2
const MAX_OPTIONS = 5

const props = defineProps({
  modelValue: { type: Object, required: true },
  number: { type: Number, required: true },
  errors: { type: Object, default: () => ({}) },
  removable: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'remove'])

const radioName = `correct-${useId()}`
const letters = 'ABCDE'

function update(changes) {
  emit('update:modelValue', { ...props.modelValue, ...changes })
}

function updateStatement(statement) {
  update({ statement })
}

function updateOptionText(index, text) {
  update({
    options: props.modelValue.options.map((option, position) =>
      position === index ? { ...option, text } : option,
    ),
  })
}

function markCorrect(index) {
  update({
    options: props.modelValue.options.map((option, position) => ({
      ...option,
      is_correct: position === index,
    })),
  })
}

function addOption() {
  if (props.modelValue.options.length >= MAX_OPTIONS) {
    return
  }
  update({ options: [...props.modelValue.options, { text: '', is_correct: false }] })
}

function removeOption(index) {
  if (props.modelValue.options.length <= MIN_OPTIONS) {
    return
  }
  update({ options: props.modelValue.options.filter((_, position) => position !== index) })
}

function errorFor(key) {
  return props.errors[key]?.[0] ?? ''
}
</script>

<template>
  <fieldset class="question">
    <legend>Questão {{ number }}</legend>

    <label class="field">
      <span>Enunciado</span>
      <textarea
        :value="modelValue.statement"
        rows="2"
        placeholder="Digite o enunciado da questão"
        @input="updateStatement($event.target.value)"
      />
      <small v-if="errorFor('statement')" class="field__error">{{ errorFor('statement') }}</small>
    </label>

    <p class="question__hint">Marque a alternativa correta:</p>

    <ul class="options">
      <li v-for="(option, index) in modelValue.options" :key="index" class="option">
        <input
          type="radio"
          :name="radioName"
          :checked="option.is_correct"
          :aria-label="`Marcar alternativa ${letters[index]} como correta`"
          @change="markCorrect(index)"
        />
        <span class="option__letter">{{ letters[index] }}</span>
        <div class="option__input">
          <input
            type="text"
            :value="option.text"
            :placeholder="`Alternativa ${letters[index]}`"
            @input="updateOptionText(index, $event.target.value)"
          />
          <small v-if="errorFor(`options.${index}.text`)" class="field__error">
            {{ errorFor(`options.${index}.text`) }}
          </small>
        </div>
        <button
          type="button"
          class="option__remove"
          :disabled="modelValue.options.length <= MIN_OPTIONS"
          :aria-label="`Remover alternativa ${letters[index]}`"
          @click="removeOption(index)"
        >
          ✕
        </button>
      </li>
    </ul>
    <small v-if="errorFor('options')" class="field__error">{{ errorFor('options') }}</small>

    <div class="question__actions">
      <button type="button" :disabled="modelValue.options.length >= MAX_OPTIONS" @click="addOption">
        + Alternativa
      </button>
      <button v-if="removable" type="button" class="danger" @click="emit('remove')">Remover questão</button>
    </div>
  </fieldset>
</template>

<style scoped>
.question {
  margin: 0 0 1rem;
  padding: 1rem 1.25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.question legend {
  font-weight: 600;
  padding: 0 0.25rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field textarea,
.option input[type='text'] {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font: inherit;
}

.field__error {
  color: var(--color-danger);
}

.question__hint {
  margin: 0.75rem 0 0.25rem;
  color: var(--color-muted);
}

.options {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.5rem;
}

.option {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
}

.option input[type='radio'] {
  margin-top: 0.7rem;
}

.option__letter {
  margin-top: 0.5rem;
  font-weight: 600;
}

.option__input {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.option__remove {
  margin-top: 0.3rem;
}

.question__actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

button {
  padding: 0.4rem 0.8rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

button.danger {
  color: var(--color-danger);
  border-color: var(--color-danger);
}
</style>
