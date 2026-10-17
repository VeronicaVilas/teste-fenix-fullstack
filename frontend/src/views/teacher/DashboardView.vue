<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ClipboardList, GraduationCap, Plus, Trophy, Users } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import RankingGrid from '@/components/RankingGrid.vue'
import { getDashboardSummary } from '@/services/dashboardService'
import { listExams } from '@/services/examService'
import { listStudents } from '@/services/userService'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()

const summary = ref(null)
const exams = ref([])
const createdExams = ref(null)
const studentsCount = ref(null)
const loading = ref(false)
const error = ref('')

const averageGrade = computed(() => toGrade(summary.value?.average_percentage))

async function loadSummary() {
  loading.value = true
  error.value = ''

  try {
    summary.value = await getDashboardSummary(session.userId)
    exams.value = summary.value.exams
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

async function loadCounters() {
  try {
    const [teacherExams, students] = await Promise.all([listExams(session.userId), listStudents()])
    createdExams.value = teacherExams.meta.total
    studentsCount.value = students.length
  } catch (failure) {
    error.value = failure.message
  }
}

function toGrade(percentage) {
  return percentage === null || percentage === undefined ? null : Number(percentage) / 10
}

function formatNumber(value, digits = 1) {
  return value === null || value === undefined
    ? '—'
    : Number(value).toLocaleString('pt-BR', { minimumFractionDigits: digits, maximumFractionDigits: digits })
}

function formatPercentage(value) {
  return value === null ? '—' : `${Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%`
}

onMounted(() => {
  loadSummary()
  loadCounters()
})
</script>

<template>
  <section class="dashboard">
    <header class="page-header">
      <div>
        <h1>Dashboard</h1>
        <p class="page-header__subtitle">Acompanhe suas provas e o desempenho da turma.</p>
      </div>

      <RouterLink :to="{ name: 'teacher-exam-create' }" class="button-primary">
        <Plus aria-hidden="true" />
        Criar nova prova
      </RouterLink>
    </header>

    <AlertMessage :message="error" />

    <p v-if="loading && !summary" class="dashboard__state">Carregando dashboard...</p>

    <template v-else-if="summary">
      <div class="stats">
        <article class="stat stat--exams">
          <span class="stat__icon"><ClipboardList aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Provas criadas</h2>
            <p class="stat__value">{{ createdExams ?? '—' }}</p>
          </div>
        </article>

        <article class="stat stat--students">
          <span class="stat__icon"><Users aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Alunos</h2>
            <p class="stat__value">{{ studentsCount ?? '—' }}</p>
          </div>
        </article>

        <article class="stat stat--average">
          <span class="stat__icon"><GraduationCap aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Média da turma</h2>
            <p class="stat__value">{{ formatNumber(averageGrade) }}</p>
          </div>
        </article>

        <article class="stat stat--top">
          <span class="stat__icon"><Trophy aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Top 1 geral</h2>
            <p class="stat__value stat__value--name">{{ summary.top_student?.student_name ?? '—' }}</p>
          </div>
        </article>
      </div>

      <p v-if="summary.total_attempts === 0" class="dashboard__state">
        Ainda não há tentativas. Publique uma prova e peça aos alunos para respondê-la.
      </p>

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
          <tr v-for="exam in exams" :key="exam.id">
            <td>{{ exam.title }}</td>
            <td>{{ exam.attempts_count }}</td>
            <td>{{ formatPercentage(exam.average_percentage) }}</td>
            <td>{{ formatPercentage(exam.best_percentage) }}</td>
            <td>{{ formatPercentage(exam.worst_percentage) }}</td>
          </tr>
        </tbody>
      </table>

      <RankingGrid />
    </template>
  </section>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.75rem;
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

.button-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: var(--color-primary);
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  transition:
    background 0.2s ease,
    transform 0.2s ease;
}

.button-primary:hover {
  background: var(--color-primary-dark);
  transform: translateY(-1px);
}

.button-primary svg {
  width: 1.1rem;
  height: 1.1rem;
}

.dashboard__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 12px;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.stat__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 3rem;
  height: 3rem;
  border-radius: 12px;
}

.stat__icon svg {
  width: 1.4rem;
  height: 1.4rem;
}

.stat--exams .stat__icon,
.stat--top .stat__icon {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.stat--students .stat__icon,
.stat--average .stat__icon {
  background: #e8edf7;
  color: var(--color-navy);
}

.stat__body {
  min-width: 0;
}

.stat h2 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--color-muted);
}

.stat__value {
  margin: 0.35rem 0 0;
  font-size: 1.9rem;
  font-weight: 700;
  line-height: 1.1;
  color: var(--color-navy);
}

.stat__value--name {
  overflow: hidden;
  font-size: 1.35rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dashboard__section {
  margin-top: 2rem;
  color: var(--color-navy);
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
</style>
