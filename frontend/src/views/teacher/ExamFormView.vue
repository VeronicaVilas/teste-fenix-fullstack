<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowLeft, Plus, Save } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import BasePanel from '@/components/BasePanel.vue'
import PageHeader from '@/components/PageHeader.vue'
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
    <PageHeader
      :title="isEditing ? 'Editar prova' : 'Criar prova'"
      :subtitle="isEditing ? 'Ajuste o rascunho antes de publicar.' : 'Monte as questões e marque a alternativa correta de cada uma.'"
    >
      <template #actions>
        <RouterLink
          :to="isEditing ? { name: 'teacher-exam-detail', params: { id } } : { name: 'teacher-exams' }"
          class="button-secondary"
        >
          <ArrowLeft aria-hidden="true" />
          Voltar
        </RouterLink>
      </template>
    </PageHeader>

    <AlertMessage :message="message" />

    <p v-if="loading" class="empty-state">Carregando prova...</p>

    <div v-else-if="notFound" class="empty-state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'teacher-exams' }">Ir para a lista de provas</RouterLink>
    </div>

    <form v-else novalidate @submit.prevent="submit">
      <BasePanel tag="div" title="Informações da prova" subtitle="Título e uma descrição opcional para os alunos.">

        <label class="field">
          <span>Título</span>
          <input v-model="form.title" type="text" maxlength="255" placeholder="Ex.: Prova de Geografia" />
          <small v-if="errorFor('title')" class="field__error">{{ errorFor('title') }}</small>
        </label>

        <label class="field">
          <span>Descrição (opcional)</span>
          <textarea v-model="form.description" rows="3" maxlength="2000" placeholder="Instruções ou assuntos cobrados" />
          <small v-if="errorFor('description')" class="field__error">{{ errorFor('description') }}</small>
        </label>
      </BasePanel>

      <BasePanel tag="div" title="Questões" subtitle="De 2 a 5 alternativas por questão, com apenas uma correta.">
        <template #actions>
          <span class="panel__count">{{ form.questions.length }} {{ form.questions.length === 1 ? 'questão' : 'questões' }}</span>
        </template>

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
      </BasePanel>

      <div class="exam-form__actions">
        <button type="button" class="button-outline" @click="addQuestion">
          <Plus aria-hidden="true" />
          Questão
        </button>
        <button type="submit" class="button-primary" :disabled="saving">
          <Save aria-hidden="true" />
          {{ saving ? 'Salvando...' : 'Salvar prova' }}
        </button>
      </div>
    </form>
  </section>
</template>

<style scoped>
form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.panel__count {
  padding: 0.4rem 0.85rem;
  background: var(--color-primary-soft);
  border-radius: 999px;
  color: var(--color-primary-dark);
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.field span {
  color: var(--color-navy);
  font-weight: 600;
}

.field input,
.field textarea {
  padding: 0.75rem 0.85rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: var(--color-text);
  font: inherit;
  resize: vertical;
  transition: border-color 0.2s ease;
}

.field input:focus,
.field textarea:focus {
  border-color: var(--color-primary);
  outline: none;
}

.field__error {
  color: var(--color-danger);
}

.exam-form__actions {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
}
</style>
