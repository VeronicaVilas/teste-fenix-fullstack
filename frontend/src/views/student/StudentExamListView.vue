<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Eye, Play } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { listAllAvailableExams } from '@/services/studentExamService'
import { useSessionStore } from '@/stores/session'

const PER_PAGE = 10

const session = useSessionStore()

const filters = [
  { value: null, label: 'Todas' },
  { value: 'pending', label: 'A fazer' },
  { value: 'answered', label: 'Realizadas' },
]

const status = ref(null)
const exams = ref([])
const currentPage = ref(1)
const loading = ref(false)
const error = ref('')

const filteredExams = computed(() => {
  if (status.value === null) {
    return exams.value
  }

  return exams.value.filter((exam) => exam.answered === (status.value === 'answered'))
})

const meta = computed(() => ({
  current_page: currentPage.value,
  last_page: Math.max(1, Math.ceil(filteredExams.value.length / PER_PAGE)),
  per_page: PER_PAGE,
  total: filteredExams.value.length,
}))

const visibleExams = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE

  return filteredExams.value.slice(start, start + PER_PAGE)
})

async function load() {
  loading.value = true
  error.value = ''

  try {
    exams.value = await listAllAvailableExams(session.userId)
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function formatGrade(percentage) {
  return (Number(percentage) / 10).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
}

watch(status, () => {
  currentPage.value = 1
})

onMounted(load)
</script>

<template>
  <section class="student-exams">
    <header class="page-header">
      <h1>Minhas provas</h1>
      <p class="page-header__subtitle">Faça as provas disponíveis e revise as que você já respondeu.</p>
    </header>

    <AlertMessage :message="error" />

    <div class="panel">
      <div class="student-exams__filters" role="group" aria-label="Filtrar por situação">
        <button
          v-for="filter in filters"
          :key="filter.label"
          type="button"
          :class="{ active: status === filter.value }"
          @click="status = filter.value"
        >
          {{ filter.label }}
        </button>
      </div>

      <p v-if="loading" class="student-exams__state">Carregando provas...</p>

      <p v-else-if="!error && filteredExams.length === 0" class="student-exams__state">
        <template v-if="status">Nenhuma prova nesta situação.</template>
        <template v-else>Nenhuma prova disponível no momento.</template>
      </p>

      <div v-else-if="filteredExams.length > 0" class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Título</th>
              <th>Questões</th>
              <th>Situação</th>
              <th>Nota</th>
              <th class="student-exams__actions-header">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exam in visibleExams" :key="exam.id">
              <td class="student-exams__title">{{ exam.title }}</td>
              <td>{{ exam.questions_count }}</td>
              <td>
                <span class="badge" :class="exam.answered ? 'badge--answered' : 'badge--pending'">
                  {{ exam.answered ? 'Realizada' : 'A fazer' }}
                </span>
              </td>
              <td class="student-exams__grade">
                <template v-if="exam.answered">
                  <strong>{{ formatGrade(exam.attempt.percentage) }}</strong>
                  <small>{{ exam.attempt.score }}/{{ exam.attempt.total_questions }} acertos</small>
                </template>
                <template v-else>—</template>
              </td>
              <td>
                <div class="student-exams__actions">
                  <RouterLink
                    v-if="exam.answered"
                    :to="{ name: 'student-exam-result', params: { id: exam.id } }"
                    class="icon-button"
                    title="Revisar prova"
                    aria-label="Revisar prova"
                  >
                    <Eye aria-hidden="true" />
                  </RouterLink>
                  <RouterLink
                    v-else
                    :to="{ name: 'student-exam-take', params: { id: exam.id } }"
                    class="start"
                  >
                    <Play aria-hidden="true" />
                    Iniciar
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationBar :meta="meta" :disabled="loading" @change="currentPage = $event" />
    </div>
  </section>
</template>

<style scoped>
.page-header {
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

.panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  margin-top: 1rem;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.student-exams__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.student-exams__filters button {
  padding: 0.5rem 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  color: var(--color-muted);
  font: inherit;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease;
}

.student-exams__filters button:hover:not(.active) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.student-exams__filters button.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
  font-weight: 600;
}

.student-exams__state {
  margin: 0;
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  border: 1px dashed var(--color-border);
  border-radius: 12px;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 0.9rem 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

tbody tr:last-child td {
  border-bottom: none;
}

tbody tr:hover {
  background: #f8fafd;
}

th {
  color: var(--color-muted);
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.student-exams__title {
  color: var(--color-navy);
  font-weight: 600;
}

.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge--pending {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.badge--answered {
  background: #ebfbee;
  color: var(--color-success);
}

.student-exams__grade strong {
  display: block;
  color: var(--color-navy);
}

.student-exams__grade small {
  color: var(--color-muted);
}

.student-exams__actions-header {
  text-align: right;
}

.student-exams__actions {
  display: flex;
  justify-content: flex-end;
}

.icon-button {
  display: inline-grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-navy);
  transition:
    border-color 0.2s ease,
    color 0.2s ease;
}

.icon-button:hover {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.icon-button svg {
  width: 1.05rem;
  height: 1.05rem;
}

.start {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  height: 2.25rem;
  padding: 0 0.9rem;
  background: var(--color-primary);
  border-radius: 10px;
  color: #fff;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.2s ease;
}

.start:hover {
  background: var(--color-primary-dark);
}

.start svg {
  width: 1rem;
  height: 1rem;
}
</style>
