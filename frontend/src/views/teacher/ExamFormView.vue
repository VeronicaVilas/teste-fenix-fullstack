<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import QuestionEditor from '@/components/QuestionEditor.vue'
import { createExam, getExam, updateExam } from '@/services/examService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  id: { type: String, default: null },
})

const router = useRouter()
const session = useSessionStore()

const isEditing = computed(() => props.id !== null)

function newQuestion() {
  return {
    statement: '',
    options: [
      { text: '', is_correct: false },
      { text: '', is_correct: false },
    ],
  }
}

const form = reactive({
  title: '',
  description: '',
  questions: [newQuestion()],
})

const errors = ref({})
const message = ref('')
const saving = ref(false)
const loading = ref(false)
const notFound = ref(false)

function errorFor(key) {
  return errors.value[key]?.[0] ?? ''
}

function questionErrors(index) {
  const prefix = `questions.${index}.`

  return Object.fromEntries(
    Object.entries(errors.value)
      .filter(([key]) => key.startsWith(prefix))
      .map(([key, value]) => [key.slice(prefix.length), value]),
  )
}

function addQuestion() {
  form.questions.push(newQuestion())
}

function removeQuestion(index) {
  form.questions.splice(index, 1)
  errors.value = {}
}

function goToDetail(examId) {
  router.push({ name: 'teacher-exam-detail', params: { id: examId } })
}

function fillForm(exam) {
  form.title = exam.title
  form.description = exam.description ?? ''
  form.questions = exam.questions.map((question) => ({
    statement: question.statement,
    options: question.options.map((option) => ({ text: option.text, is_correct: option.is_correct })),
  }))
}

async function loadExam() {
  loading.value = true

  try {
    const exam = await getExam(session.userId, props.id)

    if (exam.status !== 'draft') {
      router.replace({ name: 'teacher-exam-detail', params: { id: props.id } })
      return
    }

    fillForm(exam)
  } catch (failure) {
    if (failure.status === 404) {
      notFound.value = true
    } else {
      message.value = failure.message
    }
  } finally {
    loading.value = false
  }
}

async function submit() {
  saving.value = true
  errors.value = {}
  message.value = ''

  const payload = {
    title: form.title,
    description: form.description || null,
    questions: form.questions,
  }

  try {
    const exam = isEditing.value
      ? await updateExam(session.userId, props.id, payload)
      : await createExam(session.userId, payload)
    goToDetail(exam.id)
  } catch (failure) {
    if (failure.status === 409) {
      goToDetail(props.id)
      return
    }
    errors.value = failure.errors
    message.value = failure.message
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  if (isEditing.value) {
    loadExam()
  }
})
</script>

<template>
  <section class="exam-form">
    <header class="exam-form__header">
      <h1>{{ isEditing ? 'Editar prova' : 'Nova prova' }}</h1>
      <RouterLink :to="isEditing ? { name: 'teacher-exam-detail', params: { id } } : { name: 'teacher-exams' }">
        Voltar
      </RouterLink>
    </header>

    <AlertMessage :message="message" />

    <p v-if="loading" class="exam-form__state">Carregando prova...</p>

    <div v-else-if="notFound" class="exam-form__state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'teacher-exams' }">Ir para a lista de provas</RouterLink>
    </div>

    <form v-else novalidate @submit.prevent="submit">
      <label class="field">
        <span>Título</span>
        <input v-model="form.title" type="text" maxlength="255" placeholder="Ex.: Prova de Geografia" />
        <small v-if="errorFor('title')" class="field__error">{{ errorFor('title') }}</small>
      </label>

      <label class="field">
        <span>Descrição (opcional)</span>
        <textarea v-model="form.description" rows="2" maxlength="2000" />
        <small v-if="errorFor('description')" class="field__error">{{ errorFor('description') }}</small>
      </label>

      <h2>Questões</h2>
      <small v-if="errorFor('questions')" class="field__error">{{ errorFor('questions') }}</small>

      <QuestionEditor
        v-for="(question, index) in form.questions"
        :key="index"
        v-model="form.questions[index]"
        :number="index + 1"
        :errors="questionErrors(index)"
        :removable="form.questions.length > 1"
        @remove="removeQuestion(index)"
      />

      <div class="exam-form__actions">
        <button type="button" @click="addQuestion">+ Questão</button>
        <button type="submit" class="primary" :disabled="saving">
          {{ saving ? 'Salvando...' : 'Salvar prova' }}
        </button>
      </div>
    </form>
  </section>
</template>

<style scoped>
.exam-form__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.exam-form__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  margin-bottom: 1rem;
}

.field input,
.field textarea {
  padding: 0.5rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font: inherit;
}

.field__error {
  color: var(--color-danger);
}

.exam-form__actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
}

button {
  padding: 0.5rem 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}

button.primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
