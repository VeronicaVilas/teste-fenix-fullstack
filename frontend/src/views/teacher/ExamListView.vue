<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { Eye, Pencil, Plus, Send, Trash2 } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { deleteExam, listExams, publishExam } from '@/services/examService'
import { useSessionStore } from '@/stores/session'

const route = useRoute()
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
const success = ref(route.query.deleted ? 'Prova excluída.' : '')
const examToDelete = ref(null)
const deleting = ref(false)
const examToPublish = ref(null)
const publishing = ref(false)

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

async function confirmPublish() {
  publishing.value = true
  error.value = ''
  success.value = ''

  try {
    const published = await publishExam(session.userId, examToPublish.value.id)
    const exam = exams.value.find((item) => item.id === published.id)
    exam.status = published.status
    exam.published_at = published.published_at
    success.value = 'Prova publicada.'
  } catch (failure) {
    error.value = failure.message
  } finally {
    publishing.value = false
    examToPublish.value = null
  }
}

async function confirmDelete() {
  deleting.value = true
  error.value = ''
  success.value = ''

  try {
    await deleteExam(session.userId, examToDelete.value.id)
    success.value = 'Prova excluída.'
    const isLastItemOfPage = exams.value.length === 1 && meta.value.current_page > 1
    await load(isLastItemOfPage ? meta.value.current_page - 1 : meta.value.current_page)
  } catch (failure) {
    error.value = failure.message
  } finally {
    deleting.value = false
    examToDelete.value = null
  }
}

watch(status, () => load(1))

onMounted(() => load())
</script>

<template>
  <section class="exam-list">
    <header class="page-header">
      <div>
        <h1>Minhas provas</h1>
        <p class="page-header__subtitle">Crie, edite e publique as provas da turma.</p>
      </div>

      <RouterLink :to="{ name: 'teacher-exam-create' }" class="button-primary">
        <Plus aria-hidden="true" />
        Nova prova
      </RouterLink>
    </header>

    <AlertMessage :message="error" />
    <AlertMessage :message="success" type="success" />

    <div class="panel">
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

      <p v-if="loading" class="exam-list__state">Carregando provas...</p>

      <p v-else-if="!error && exams.length === 0" class="exam-list__state">
        <template v-if="status">Nenhuma prova com este status.</template>
        <template v-else>Nenhuma prova cadastrada. Crie a primeira!</template>
      </p>

      <div v-else-if="exams.length > 0" class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Título</th>
              <th>Status</th>
              <th>Questões</th>
              <th class="exam-list__actions-header">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="exam in exams" :key="exam.id">
              <td class="exam-list__title">{{ exam.title }}</td>
              <td><StatusBadge :status="exam.status" /></td>
              <td>{{ exam.questions_count }}</td>
              <td>
                <div class="exam-list__actions">
                  <button v-if="exam.status === 'draft'" type="button" class="publish" @click="examToPublish = exam">
                    <Send aria-hidden="true" />
                    Publicar
                  </button>
                  <RouterLink
                    :to="{ name: 'teacher-exam-detail', params: { id: exam.id } }"
                    class="icon-button"
                    title="Ver prova"
                    aria-label="Ver prova"
                  >
                    <Eye aria-hidden="true" />
                  </RouterLink>
                  <RouterLink
                    v-if="exam.status === 'draft'"
                    :to="{ name: 'teacher-exam-edit', params: { id: exam.id } }"
                    class="icon-button"
                    title="Editar prova"
                    aria-label="Editar prova"
                  >
                    <Pencil aria-hidden="true" />
                  </RouterLink>
                  <button
                    type="button"
                    class="icon-button danger"
                    :disabled="exam.attempts_count > 0"
                    :title="exam.attempts_count > 0 ? 'Prova já respondida por alunos' : 'Excluir prova'"
                    aria-label="Excluir prova"
                    @click="examToDelete = exam"
                  >
                    <Trash2 aria-hidden="true" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <PaginationBar :meta="meta" :disabled="loading" @change="load" />
    </div>

    <ConfirmDialog
      :open="examToPublish !== null"
      title="Publicar prova"
      :message="`Publicar a prova '${examToPublish?.title}'? Depois de publicada ela ficará disponível para os alunos e não poderá mais ser editada.`"
      confirm-label="Publicar"
      :processing="publishing"
      @confirm="confirmPublish"
      @cancel="examToPublish = null"
    />

    <ConfirmDialog
      :open="examToDelete !== null"
      title="Excluir prova"
      :message="`Excluir a prova '${examToDelete?.title}'? Esta ação não pode ser desfeita.`"
      confirm-label="Excluir"
      danger
      :processing="deleting"
      @confirm="confirmDelete"
      @cancel="examToDelete = null"
    />
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

.exam-list__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.exam-list__filters button {
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

.exam-list__filters button:hover:not(.active) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.exam-list__filters button.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
  font-weight: 600;
}

.exam-list__state {
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

.exam-list__title {
  color: var(--color-navy);
  font-weight: 600;
}

.exam-list__actions-header {
  text-align: right;
}

.exam-list__actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.4rem;
}

.icon-button {
  display: inline-grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-navy);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease;
}

.icon-button svg {
  width: 1.05rem;
  height: 1.05rem;
}

.icon-button:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.icon-button.danger {
  color: var(--color-danger);
}

.icon-button.danger:hover:not(:disabled) {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.icon-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.publish {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  height: 2.25rem;
  padding: 0 0.9rem;
  background: var(--color-primary);
  border: none;
  border-radius: 10px;
  color: #fff;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.publish:hover {
  background: var(--color-primary-dark);
}

.publish svg {
  width: 1rem;
  height: 1rem;
}
</style>
