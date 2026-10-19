<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Eye, Play } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import BasePanel from '@/components/BasePanel.vue'
import PageHeader from '@/components/PageHeader.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { listAvailableExams } from '@/services/studentExamService'
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
const meta = ref({ current_page: 1, last_page: 1, per_page: PER_PAGE, total: 0 })
const loading = ref(false)
const error = ref('')

async function load(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const data = await listAvailableExams(session.userId, { page, perPage: PER_PAGE, status: status.value })
    exams.value = data.items
    meta.value = data.meta
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function formatGrade(percentage) {
  return (Number(percentage) / 10).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
}

watch(status, () => load(1))

onMounted(() => load())
</script>

<template>
  <section class="student-exams">
    <PageHeader title="Minhas provas" subtitle="Faça as provas disponíveis e revise as que você já respondeu." />

    <AlertMessage :message="error" />

    <BasePanel tag="div">
      <div class="filter-pills student-exams__filters" role="group" aria-label="Filtrar por situação">
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
        <template v-if="status">Nenhuma prova nesta situação.</template>
        <template v-else>Nenhuma prova disponível no momento.</template>
      </p>

      <div v-else-if="exams.length > 0" class="table-wrapper">
        <table class="data-table">
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
            <tr v-for="exam in exams" :key="exam.id">
              <td class="student-exams__title">{{ exam.title }}</td>
              <td>{{ exam.questions_count }}</td>
              <td>
                <StatusBadge :status="exam.answered ? 'answered' : 'pending'" />
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
                    class="button-primary button-primary--small start"
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

      <PaginationBar :meta="meta" :disabled="loading" @change="load" />
    </BasePanel>
  </section>
</template>

<style scoped>
.student-exams__title {
  color: var(--color-navy);
  font-weight: 600;
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
</style>
