<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { listExams } from '@/services/examService'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()

const filters = [
  { value: null, label: 'Todas' },
  { value: 'draft', label: 'Rascunhos' },
  { value: 'published', label: 'Publicadas' },
]

const status = ref(null)
const exams = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const loading = ref(false)
const error = ref('')

async function load(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const data = await listExams(session.userId, { status: status.value, page })
    exams.value = data.items
    meta.value = data.meta
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('pt-BR') : '—'
}

watch(status, () => load(1))

onMounted(() => load())
</script>

<template>
  <section class="exam-list">
    <header class="exam-list__header">
      <h1>Provas</h1>
      <RouterLink :to="{ name: 'teacher-exam-create' }" class="button button--primary">Nova prova</RouterLink>
    </header>

    <div class="exam-list__filters" role="group" aria-label="Filtrar por status">
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

    <AlertMessage :message="error" />

    <p v-if="loading" class="exam-list__state">Carregando provas...</p>

    <p v-else-if="!error && exams.length === 0" class="exam-list__state">
      <template v-if="status">Nenhuma prova com este status.</template>
      <template v-else>Nenhuma prova cadastrada. Crie a primeira!</template>
    </p>

    <table v-else-if="exams.length > 0">
      <thead>
        <tr>
          <th>Título</th>
          <th>Status</th>
          <th>Questões</th>
          <th>Tentativas</th>
          <th>Criada em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="exam in exams" :key="exam.id">
          <td>{{ exam.title }}</td>
          <td><StatusBadge :status="exam.status" /></td>
          <td>{{ exam.questions_count }}</td>
          <td>{{ exam.attempts_count }}</td>
          <td>{{ formatDate(exam.created_at) }}</td>
          <td class="exam-list__actions">
            <RouterLink :to="{ name: 'teacher-exam-detail', params: { id: exam.id } }" class="button">Ver</RouterLink>
            <template v-if="exam.status === 'draft'">
              <RouterLink :to="{ name: 'teacher-exam-edit', params: { id: exam.id } }" class="button">Editar</RouterLink>
              <button type="button" disabled title="Disponível em breve">Publicar</button>
            </template>
            <button type="button" class="danger" disabled title="Disponível em breve">Excluir</button>
          </td>
        </tr>
      </tbody>
    </table>

    <PaginationBar :meta="meta" :disabled="loading" @change="load" />
  </section>
</template>

<style scoped>
.exam-list__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.exam-list__filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.exam-list__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 8px;
}

table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

th,
td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

th {
  color: var(--color-muted);
  font-weight: 600;
}

.exam-list__actions {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

button,
.button {
  padding: 0.35rem 0.75rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
  color: inherit;
  text-decoration: none;
}

button.active {
  border-color: var(--color-primary);
  color: var(--color-primary);
  font-weight: 600;
}

button.danger {
  color: var(--color-danger);
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.button--primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}
</style>
