<script setup>
import { useId } from 'vue'
import { Plus, Trash2, X } from 'lucide-vue-next'

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
    <legend class="question__title">Questão {{ number }}</legend>

    <button
      v-if="removable"
      type="button"
      class="danger question__remove"
      title="Remover questão"
      aria-label="Remover questão"
      @click="emit('remove')"
    >
      <Trash2 aria-hidden="true" />
    </button>

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
      <li v-for="(option, index) in modelValue.options" :key="index" class="option" :class="{ 'option--correct': option.is_correct }">
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
          :title="`Remover alternativa ${letters[index]}`"
          :aria-label="`Remover alternativa ${letters[index]}`"
          @click="removeOption(index)"
        >
          <X aria-hidden="true" />
        </button>
      </li>
    </ul>
    <small v-if="errorFor('options')" class="field__error">{{ errorFor('options') }}</small>

    <div class="question__actions">
      <button type="button" class="question__add" :disabled="modelValue.options.length >= MAX_OPTIONS" @click="addOption">
        <Plus aria-hidden="true" />
        Alternativa
      </button>
    </div>
  </fieldset>
</template>

<style scoped>
.question {
  position: relative;
  min-width: 0;
  margin: 0;
  padding: 1.25rem;
  background: #f8fafd;
  border: 1px solid var(--color-border);
  border-left: 4px solid var(--color-primary);
  border-radius: 12px;
}

.question__title {
  float: left;
  width: 100%;
  margin-bottom: 0.9rem;
  padding: 0;
  color: var(--color-navy);
  font-size: 1.05rem;
  font-weight: 700;
}

.question__title + * {
  clear: both;
}

.question__remove {
  position: absolute;
  top: 0.9rem;
  right: 0.9rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  clear: both;
}

.field span {
  color: var(--color-navy);
  font-weight: 600;
}

.field textarea,
.option input[type='text'] {
  width: 100%;
  box-sizing: border-box;
  padding: 0.65rem 0.85rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-text);
  font: inherit;
  resize: vertical;
  transition: border-color 0.2s ease;
}

.field textarea:focus,
.option input[type='text']:focus {
  border-color: var(--color-primary);
  outline: none;
}

.field__error {
  color: var(--color-danger);
}

.question__hint {
  margin: 1rem 0 0.5rem;
  color: var(--color-muted);
  font-size: 0.9rem;
}

.options {
  display: grid;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.option {
  display: flex;
  align-items: flex-start;
  gap: 0.6rem;
}

.option input[type='radio'] {
  width: 1.1rem;
  height: 1.1rem;
  margin-top: 0.75rem;
  accent-color: var(--color-primary);
  cursor: pointer;
}

.option__letter {
  margin-top: 0.3rem;
}

.option--correct .option__letter {
  background: var(--color-primary);
  color: #fff;
}

.option--correct input[type='text'] {
  border-color: var(--color-primary);
}

.option__input {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.option__remove,
.question__remove {
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-muted);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease;
}

.option__remove {
  margin-top: 0.2rem;
}

.option__remove svg,
.question__remove svg {
  width: 1.05rem;
  height: 1.05rem;
}

.option__remove:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

button.danger {
  color: var(--color-danger);
}

button.danger:hover {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.question__actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.9rem;
}

.question__add {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.9rem;
  background: none;
  border: 1px dashed var(--color-primary);
  border-radius: 10px;
  color: var(--color-primary-dark);
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.question__add:hover:not(:disabled) {
  background: var(--color-primary-soft);
}

.question__add svg {
  width: 1rem;
  height: 1rem;
}

button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
</style>
