<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowLeft, Send } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import { getAvailableExam, submitAttempt } from '@/services/studentExamService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  id: { type: String, required: true },
})

const router = useRouter()
const session = useSessionStore()

const exam = ref(null)
const answers = reactive({})
const loading = ref(true)
const notFound = ref(false)
const error = ref('')
const confirmingSubmit = ref(false)
const submitting = ref(false)
const submitError = ref('')

const letters = 'ABCDE'

const answeredCount = computed(() => Object.keys(answers).length)
const totalQuestions = computed(() => exam.value?.questions.length ?? 0)
const allAnswered = computed(() => totalQuestions.value > 0 && answeredCount.value === totalQuestions.value)
const progress = computed(() => (totalQuestions.value === 0 ? 0 : (answeredCount.value / totalQuestions.value) * 100))

function goToResult() {
  router.push({ name: 'student-exam-result', params: { id: props.id } })
}

async function confirmSubmit() {
  submitting.value = true
  submitError.value = ''

  try {
    await submitAttempt(session.userId, props.id, answers)
    goToResult()
  } catch (failure) {
    if (failure.status === 409) {
      goToResult()
    } else {
      submitError.value = failure.message
    }
  } finally {
    submitting.value = false
    confirmingSubmit.value = false
  }
}

onMounted(async () => {
  try {
    exam.value = await getAvailableExam(session.userId, props.id)

    if (exam.value.answered) {
      router.replace({ name: 'student-exam-result', params: { id: props.id } })
    }
  } catch (failure) {
    if (failure.status === 404) {
      notFound.value = true
    } else {
      error.value = failure.message
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="take-exam">
    <p v-if="loading" class="take-exam__state">Carregando prova...</p>

    <div v-else-if="notFound" class="take-exam__state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'student-exams' }">Ir para minhas provas</RouterLink>
    </div>

    <AlertMessage v-else-if="error" :message="error" />

    <template v-else-if="!exam.answered">
      <header class="page-header">
        <div class="page-header__text">
          <h1>{{ exam.title }}</h1>
          <p v-if="exam.description" class="page-header__subtitle">{{ exam.description }}</p>
        </div>

        <RouterLink :to="{ name: 'student-exams' }" class="button-secondary">
          <ArrowLeft aria-hidden="true" />
          Voltar
        </RouterLink>
      </header>

      <AlertMessage type="info" message="Você só pode enviar esta prova uma vez." />

      <ol class="questions">
        <li v-for="(question, questionIndex) in exam.questions" :key="question.id" class="question">
          <fieldset>
            <legend>
              <span class="question__number">Questão {{ questionIndex + 1 }}</span>
              <span class="question__statement">{{ question.statement }}</span>
            </legend>
            <label
              v-for="(option, index) in question.options"
              :key="option.id"
              class="option"
              :class="{ 'option--selected': answers[question.id] === option.id }"
            >
              <input v-model="answers[question.id]" type="radio" :name="`question-${question.id}`" :value="option.id" />
              <span class="option__letter">{{ letters[index] }}</span>
              <span class="option__text">{{ option.text }}</span>
            </label>
          </fieldset>
        </li>
      </ol>

      <AlertMessage :message="submitError" />

      <footer class="take-exam__footer">
        <div class="take-exam__progress">
          <span>Respondidas: {{ answeredCount }} de {{ totalQuestions }}</span>
          <div class="progress" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
            <span :style="{ width: `${progress}%` }" />
          </div>
        </div>
        <button
          type="button"
          class="primary"
          :disabled="!allAnswered || submitting"
          :title="allAnswered ? 'Enviar respostas' : 'Responda todas as questões para enviar'"
          @click="confirmingSubmit = true"
        >
          <Send aria-hidden="true" />
          {{ submitting ? 'Corrigindo...' : 'Enviar prova' }}
        </button>
      </footer>

      <ConfirmDialog
        :open="confirmingSubmit"
        title="Enviar respostas"
        message="Enviar respostas? Não será possível alterar depois."
        confirm-label="Enviar"
        :processing="submitting"
        @confirm="confirmSubmit"
        @cancel="confirmingSubmit = false"
      />
    </template>
  </section>
</template>

<style scoped>
.take-exam__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 12px;
}

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
}

.page-header__subtitle {
  margin: 0.35rem 0 0;
  color: var(--color-muted);
}

.button-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: var(--color-navy);
  font-weight: 600;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    color 0.2s ease;
}

.button-secondary:hover {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.button-secondary svg,
button svg {
  width: 1.1rem;
  height: 1.1rem;
}

.questions {
  display: grid;
  gap: 1rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.question fieldset {
  margin: 0;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-left: 4px solid var(--color-primary);
  border-radius: 16px;
}

.question legend {
  float: left;
  width: 100%;
  margin-bottom: 1rem;
  padding: 0;
}

.question legend + * {
  clear: both;
}

.question__number {
  display: block;
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.question__statement {
  display: block;
  margin-top: 0.35rem;
  color: var(--color-navy);
  font-size: 1.05rem;
  font-weight: 600;
}

.option {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.65rem 0.85rem;
  margin-bottom: 0.5rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background 0.2s ease;
}

.option:last-child {
  margin-bottom: 0;
}

.option:hover {
  border-color: var(--color-primary);
}

.option input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.option__letter {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  background: #e8edf7;
  border-radius: 8px;
  color: var(--color-navy);
  font-weight: 700;
  transition:
    background 0.2s ease,
    color 0.2s ease;
}

.option__text {
  flex: 1;
  min-width: 0;
}

.option--selected {
  background: var(--color-primary-soft);
  border-color: var(--color-primary);
}

.option--selected .option__letter {
  background: var(--color-primary);
  color: #fff;
}

.option:focus-within {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.take-exam__footer {
  position: sticky;
  bottom: 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  padding: 1rem 1.5rem;
  margin-top: 1.25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(15, 30, 61, 0.12);
}

.take-exam__progress {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 180px;
  max-width: 360px;
  color: var(--color-navy);
  font-weight: 600;
}

.progress {
  height: 6px;
  background: var(--color-primary-soft);
  border-radius: 999px;
  overflow: hidden;
}

.progress span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary), #fdba74);
  border-radius: 999px;
  transition: width 0.3s ease;
}

button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 12px;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

button.primary {
  background: var(--color-primary);
  box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
  color: #fff;
  transition: background 0.2s ease;
}

button.primary:hover:not(:disabled) {
  background: var(--color-primary-dark);
}

button:disabled {
  opacity: 0.5;
  box-shadow: none;
  cursor: not-allowed;
}
</style>
