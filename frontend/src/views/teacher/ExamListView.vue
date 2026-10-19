<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { Eye, Pencil, Plus, Send, Trash2 } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import BasePanel from '@/components/BasePanel.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import PageHeader from '@/components/PageHeader.vue'
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
    <PageHeader title="Minhas provas" subtitle="Crie, edite e publique as provas da turma.">
      <template #actions>
        <RouterLink :to="{ name: 'teacher-exam-create' }" class="button-primary">
          <Plus aria-hidden="true" />
          Nova prova
        </RouterLink>
      </template>
    </PageHeader>

    <AlertMessage :message="error" />
    <AlertMessage :message="success" type="success" />

    <BasePanel tag="div">
      <div class="filter-pills exam-list__filters" role="group" aria-label="Filtrar por status">
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

      <p v-if="loading" class="empty-state">Carregando provas...</p>

      <p v-else-if="!error && exams.length === 0" class="empty-state">
        <template v-if="status">Nenhuma prova com este status.</template>
        <template v-else>Nenhuma prova cadastrada. Crie a primeira!</template>
      </p>

      <div v-else-if="exams.length > 0" class="table-wrapper">
        <table class="data-table">
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
                  <button v-if="exam.status === 'draft'" type="button" class="button-primary button-primary--small publish" @click="examToPublish = exam">
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
    </BasePanel>

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
</style>
