<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import { getAttemptResult } from '@/services/studentExamService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  id: { type: String, required: true },
})

const session = useSessionStore()

const result = ref(null)
const loading = ref(true)
const notAnswered = ref(false)
const error = ref('')
const onlyWrong = ref(false)

const letters = 'ABCDE'

const visibleQuestions = computed(() =>
  onlyWrong.value ? result.value.questions.filter((question) => !question.is_correct) : result.value.questions,
)

const percentage = computed(() => Number(result.value?.percentage ?? 0))

function formatPercentage(value) {
  return `${Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%`
}

function formatDate(value) {
  return value ? new Date(value).toLocaleString('pt-BR') : '—'
}

function optionState(question, option) {
  if (option.id === question.correct_option_id) {
    return 'correct'
  }
  if (option.id === question.selected_option_id) {
    return 'wrong'
  }
  return null
}

onMounted(async () => {
  try {
    result.value = await getAttemptResult(session.userId, props.id)
  } catch (failure) {
    if (failure.status === 404) {
      notAnswered.value = true
    } else {
      error.value = failure.message
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="result">
    <RouterLink :to="{ name: 'student-exams' }" class="result__back">← Voltar às provas</RouterLink>

    <p v-if="loading" class="result__state">Carregando resultado...</p>

    <div v-else-if="notAnswered" class="result__state">
      <p>Você ainda não respondeu esta prova.</p>
      <RouterLink :to="{ name: 'student-exam-take', params: { id } }" class="button button--primary">Fazer prova</RouterLink>
    </div>

    <AlertMessage v-else-if="error" :message="error" />

    <template v-else>
      <header class="summary">
        <h1>{{ result.exam.title }}</h1>
        <p class="summary__score">
          {{ result.score }} de {{ result.total_questions }} acertos — {{ formatPercentage(result.percentage) }}
        </p>
        <div class="summary__bar" role="progressbar" :aria-valuenow="percentage" aria-valuemin="0" aria-valuemax="100">
          <span :style="{ width: `${percentage}%` }" />
        </div>
        <p class="summary__date">Enviada em {{ formatDate(result.submitted_at) }}</p>
      </header>

      <label class="filter">
        <input v-model="onlyWrong" type="checkbox" />
        Mostrar só as que errei
      </label>

      <p v-if="visibleQuestions.length === 0" class="result__state">Você acertou todas as questões!</p>

      <ol class="questions">
        <li
          v-for="question in visibleQuestions"
          :key="question.id"
          class="question"
          :class="question.is_correct ? 'question--correct' : 'question--wrong'"
        >
          <p class="question__statement">{{ question.position }}. {{ question.statement }}</p>
          <p class="question__verdict">{{ question.is_correct ? '✓ Você acertou' : '✗ Você errou' }}</p>
          <ul class="options">
            <li
              v-for="(option, index) in question.options"
              :key="option.id"
              class="option"
              :class="optionState(question, option) && `option--${optionState(question, option)}`"
            >
              <span class="option__letter">{{ letters[index] }}</span>
              <span>{{ option.text }}</span>
              <span v-if="option.id === question.selected_option_id && !question.is_correct" class="option__tag">
                Sua resposta
              </span>
              <span v-if="option.id === question.correct_option_id" class="option__tag">
                {{ question.is_correct ? 'Sua resposta' : 'Resposta correta' }}
              </span>
            </li>
          </ul>
        </li>
      </ol>

      <RouterLink :to="{ name: 'student-exams' }" class="button button--primary">Voltar às provas</RouterLink>
    </template>
  </section>
</template>

<style scoped>
.result__back {
  color: var(--color-muted);
  text-decoration: none;
}

.result__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
}

.summary {
  padding: 1.25rem;
  margin: 1rem 0;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.summary h1 {
  margin: 0 0 0.5rem;
}

.summary__score {
  margin: 0 0 0.75rem;
  font-size: 1.25rem;
  font-weight: 600;
}

.summary__bar {
  height: 10px;
  background: var(--color-border);
  border-radius: 999px;
  overflow: hidden;
}

.summary__bar span {
  display: block;
  height: 100%;
  background: var(--color-success);
}

.summary__date {
  margin: 0.75rem 0 0;
  color: var(--color-muted);
}

.filter {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 1rem;
  cursor: pointer;
}

.questions {
  list-style: none;
  padding: 0;
  display: grid;
  gap: 1rem;
}

.question {
  padding: 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-left-width: 4px;
  border-radius: 8px;
}

.question--correct {
  border-left-color: var(--color-success);
}

.question--wrong {
  border-left-color: var(--color-danger);
}

.question__statement {
  margin: 0 0 0.25rem;
  font-weight: 600;
}

.question__verdict {
  margin: 0 0 0.75rem;
}

.question--correct .question__verdict {
  color: var(--color-success);
}

.question--wrong .question__verdict {
  color: var(--color-danger);
}

.options {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.4rem;
}

.option {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
}

.option--correct {
  border-color: var(--color-success);
  background: #ebfbee;
}

.option--wrong {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.option__letter {
  font-weight: 600;
}

.option__tag {
  margin-left: auto;
  font-size: 0.85rem;
  font-weight: 600;
}

.option--correct .option__tag {
  color: var(--color-success);
}

.option--wrong .option__tag {
  color: var(--color-danger);
}

.button {
  display: inline-block;
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  text-decoration: none;
}

.button--primary {
  background: var(--color-primary);
  color: #fff;
}
</style>
