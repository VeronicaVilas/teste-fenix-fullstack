<script setup>
import { onMounted, ref, watch } from 'vue'

import AlertMessage from '@/components/AlertMessage.vue'
import RankingGrid from '@/components/RankingGrid.vue'
import { getDashboardSummary } from '@/services/dashboardService'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()

const examId = ref(null)
const summary = ref(null)
const exams = ref([])
const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''

  try {
    summary.value = await getDashboardSummary(session.userId, examId.value)
    exams.value = summary.value.exams
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function formatPercentage(value) {
  return value === null ? '—' : `${Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%`
}

watch(examId, load)

onMounted(load)
</script>

<template>
  <section class="dashboard">
    <header class="dashboard__header">
      <h1>Dashboard</h1>
      <label class="dashboard__filter">
        <span>Prova</span>
        <select v-model="examId" :disabled="loading">
          <option :value="null">Todas</option>
          <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.title }}</option>
        </select>
      </label>
    </header>

    <AlertMessage :message="error" />

    <p v-if="loading && !summary" class="dashboard__state">Carregando dashboard...</p>

    <template v-else-if="summary">
      <p v-if="summary.total_attempts === 0" class="dashboard__state">
        Ainda não há tentativas. Publique uma prova e peça aos alunos para respondê-la.
      </p>

      <div v-else class="cards">
        <article class="card card--average">
          <h2>Média da turma</h2>
          <p class="card__value">{{ formatPercentage(summary.average_percentage) }}</p>
        </article>

        <article class="card card--best">
          <h2>Melhor pontuação</h2>
          <p class="card__value">{{ formatPercentage(summary.best.percentage) }}</p>
          <p class="card__detail">{{ summary.best.student_name }} · {{ summary.best.exam_title }}</p>
          <p class="card__detail">{{ summary.best.score }}/{{ summary.best.total_questions }} acertos</p>
        </article>

        <article class="card card--worst">
          <h2>Pior pontuação</h2>
          <p class="card__value">{{ formatPercentage(summary.worst.percentage) }}</p>
          <p class="card__detail">{{ summary.worst.student_name }} · {{ summary.worst.exam_title }}</p>
          <p class="card__detail">{{ summary.worst.score }}/{{ summary.worst.total_questions }} acertos</p>
        </article>

        <article class="card">
          <h2>Tentativas / Alunos</h2>
          <p class="card__value">{{ summary.total_attempts }} / {{ summary.total_students }}</p>
          <p class="card__detail">tentativas · alunos avaliados</p>
        </article>
      </div>

      <h2 class="dashboard__section">Média por prova</h2>
      <p v-if="exams.length === 0" class="dashboard__state">Nenhuma prova publicada.</p>
      <table v-else>
        <thead>
          <tr>
            <th>Prova</th>
            <th>Tentativas</th>
            <th>Média</th>
            <th>Melhor</th>
            <th>Pior</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="exam in exams" :key="exam.id" :class="{ selected: exam.id === examId }">
            <td>{{ exam.title }}</td>
            <td>{{ exam.attempts_count }}</td>
            <td>{{ formatPercentage(exam.average_percentage) }}</td>
            <td>{{ formatPercentage(exam.best_percentage) }}</td>
            <td>{{ formatPercentage(exam.worst_percentage) }}</td>
          </tr>
        </tbody>
      </table>

      <RankingGrid :exam-id="examId" />
    </template>
  </section>
</template>

<style scoped>
.dashboard__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
}

.dashboard__filter {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.dashboard__filter select {
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font: inherit;
}

.dashboard__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 8px;
}

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.card {
  padding: 1rem 1.25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-top: 4px solid var(--color-border);
  border-radius: 8px;
}

.card--average {
  border-top-color: var(--color-primary);
}

.card--best {
  border-top-color: var(--color-success);
}

.card--worst {
  border-top-color: var(--color-danger);
}

.card h2 {
  margin: 0;
  font-size: 0.95rem;
  color: var(--color-muted);
}

.card__value {
  margin: 0.5rem 0 0.25rem;
  font-size: 1.75rem;
  font-weight: 700;
}

.card__detail {
  margin: 0;
  color: var(--color-muted);
}

.dashboard__section {
  margin-top: 2rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
}

th,
td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

th {
  color: var(--color-muted);
}

tr.selected {
  background: #fff4e6;
}
</style>
