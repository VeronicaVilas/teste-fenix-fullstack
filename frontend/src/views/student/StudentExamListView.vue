<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { listAvailableExams } from '@/services/studentExamService'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()

const exams = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const loading = ref(false)
const error = ref('')

async function load(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const data = await listAvailableExams(session.userId, page)
    exams.value = data.items
    meta.value = data.meta
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function formatPercentage(value) {
  return `${Number(value).toLocaleString('pt-BR', { maximumFractionDigits: 2 })}%`
}

onMounted(() => load())
</script>

<template>
  <section class="student-exams">
    <h1>Minhas provas</h1>

    <AlertMessage :message="error" />

    <p v-if="loading" class="student-exams__state">Carregando provas...</p>

    <p v-else-if="!error && exams.length === 0" class="student-exams__state">
      Nenhuma prova disponível no momento.
    </p>

    <ul v-else class="cards">
      <li v-for="exam in exams" :key="exam.id" class="card" :class="{ 'card--answered': exam.answered }">
        <div class="card__body">
          <h2>{{ exam.title }}</h2>
          <p v-if="exam.description" class="card__description">{{ exam.description }}</p>
          <p class="card__meta">{{ exam.questions_count }} questões</p>
        </div>

        <div class="card__footer">
          <template v-if="exam.answered">
            <span class="card__score">
              Nota: {{ exam.attempt.score }}/{{ exam.attempt.total_questions }}
              ({{ formatPercentage(exam.attempt.percentage) }})
            </span>
            <button type="button" disabled title="Disponível em breve">Ver resultado</button>
          </template>
          <template v-else>
            <span class="card__pending">A fazer</span>
            <RouterLink :to="{ name: 'student-exam-take', params: { id: exam.id } }" class="button button--primary">
              Fazer prova
            </RouterLink>
          </template>
        </div>
      </li>
    </ul>

    <PaginationBar :meta="meta" :disabled="loading" @change="load" />
  </section>
</template>

<style scoped>
.student-exams__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 8px;
}

.cards {
  list-style: none;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
}

.card {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1rem 1.25rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-left: 4px solid var(--color-primary);
  border-radius: 8px;
}

.card--answered {
  border-left-color: var(--color-success);
}

.card h2 {
  margin: 0 0 0.25rem;
  font-size: 1.15rem;
}

.card__description,
.card__meta {
  margin: 0 0 0.5rem;
  color: var(--color-muted);
}

.card__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.card__score {
  font-weight: 600;
  color: var(--color-success);
}

.card__pending {
  color: var(--color-primary);
  font-weight: 600;
}

button,
.button {
  padding: 0.4rem 0.8rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
  color: inherit;
  text-decoration: none;
}

.button--primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
