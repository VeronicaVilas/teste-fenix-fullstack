<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowLeft, CircleCheck, CircleX, GraduationCap, Percent } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import BasePanel from '@/components/BasePanel.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatCard from '@/components/StatCard.vue'
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
const wrongCount = computed(() => (result.value ? result.value.total_questions - result.value.score : 0))

function formatPercentage(value) {
  return `${Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%`
}

function formatGrade(value) {
  return (Number(value) / 10).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
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
    <p v-if="loading" class="empty-state">Carregando resultado...</p>

    <div v-else-if="notAnswered" class="empty-state">
      <p>Você ainda não respondeu esta prova.</p>
      <RouterLink :to="{ name: 'student-exam-take', params: { id } }" class="button-primary">Fazer prova</RouterLink>
    </div>

    <AlertMessage v-else-if="error" :message="error" />

    <template v-else>
      <PageHeader :title="result.exam.title" :subtitle="`Enviada em ${formatDate(result.submitted_at)}`">
        <template #actions>
          <RouterLink :to="{ name: 'student-exams' }" class="button-secondary">
            <ArrowLeft aria-hidden="true" />
            Voltar
          </RouterLink>
        </template>
      </PageHeader>

      <div class="stats">
        <StatCard label="Nota" :icon="GraduationCap" :value="formatGrade(result.percentage)" />
        <StatCard label="Aproveitamento" :icon="Percent" tone="navy" :value="formatPercentage(result.percentage)" />
        <StatCard label="Acertos" :icon="CircleCheck" tone="green" :value="result.score" />
        <StatCard label="Erros" :icon="CircleX" tone="red" :value="wrongCount" />
      </div>

      <BasePanel title="Revisão da prova">
        <template #subtitle>
          <p class="summary__score">
            {{ result.score }} de {{ result.total_questions }} acertos — {{ formatPercentage(result.percentage) }}
          </p>
        </template>

        <template #actions>
          <label class="filter" :class="{ 'filter--active': onlyWrong }">
            <input v-model="onlyWrong" type="checkbox" />
            Mostrar só as que errei
          </label>
        </template>

        <div class="summary__bar" role="progressbar" :aria-valuenow="percentage" aria-valuemin="0" aria-valuemax="100">
          <span :style="{ width: `${percentage}%` }" />
        </div>

        <p v-if="visibleQuestions.length === 0" class="empty-state">Você acertou todas as questões!</p>

        <ol class="questions">
          <li
            v-for="question in visibleQuestions"
            :key="question.id"
            class="question"
            :class="question.is_correct ? 'question--correct' : 'question--wrong'"
          >
            <div class="question__top">
              <span class="question__number">Questão {{ question.position }}</span>
              <span class="question__verdict">{{ question.is_correct ? '✓ Você acertou' : '✗ Você errou' }}</span>
            </div>
            <p class="question__statement">{{ question.statement }}</p>
            <ul class="options">
              <li
                v-for="(option, index) in question.options"
                :key="option.id"
                class="option"
                :class="optionState(question, option) && `option--${optionState(question, option)}`"
              >
                <span class="option__letter">{{ letters[index] }}</span>
                <span class="option__text">{{ option.text }}</span>
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
      </BasePanel>
    </template>
  </section>
</template>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.summary__score {
  margin: 0.3rem 0 0;
  color: var(--color-muted);
}

.summary__bar {
  height: 8px;
  background: var(--color-primary-soft);
  border-radius: 999px;
  overflow: hidden;
}

.summary__bar span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary), #fdba74);
  border-radius: 999px;
}

.filter {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  color: var(--color-muted);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease;
}

.filter:hover {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.filter input {
  accent-color: var(--color-primary);
}

.filter--active {
  background: var(--color-primary-soft);
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
  font-weight: 600;
}

.questions {
  display: grid;
  gap: 1rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.question {
  padding: 1.25rem;
  background: #f8fafd;
  border: 1px solid var(--color-border);
  border-left: 4px solid var(--color-border);
  border-radius: 12px;
}

.question--correct {
  border-left-color: var(--color-success);
}

.question--wrong {
  border-left-color: var(--color-danger);
}

.question__top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.question__number {
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.question__verdict {
  padding: 0.15rem 0.6rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
}

.question--correct .question__verdict {
  background: #ebfbee;
  color: var(--color-success);
}

.question--wrong .question__verdict {
  background: #fff5f5;
  color: var(--color-danger);
}

.question__statement {
  margin: 0.5rem 0 0.9rem;
  color: var(--color-navy);
  font-weight: 600;
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
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
}

.option__text {
  flex: 1;
  min-width: 0;
}

.option--correct {
  background: #ebfbee;
  border-color: var(--color-success);
}

.option--correct .option__letter {
  background: var(--color-success);
  color: #fff;
}

.option--wrong {
  background: #fff5f5;
  border-color: var(--color-danger);
}

.option--wrong .option__letter {
  background: var(--color-danger);
  color: #fff;
}

.option__tag {
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
}

.option--correct .option__tag {
  color: var(--color-success);
}

.option--wrong .option__tag {
  color: var(--color-danger);
}
</style>
