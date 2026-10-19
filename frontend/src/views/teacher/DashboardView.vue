<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ClipboardList, GraduationCap, Plus, Trophy, Users } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import ExamAveragesPanel from '@/components/ExamAveragesPanel.vue'
import ExamRankingPanel from '@/components/ExamRankingPanel.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatCard from '@/components/StatCard.vue'
import { getDashboardSummary } from '@/services/dashboardService'
import { listExams } from '@/services/examService'
import { listStudents } from '@/services/userService'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()

const summary = ref(null)
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

onMounted(() => {
  loadSummary()
  loadCounters()
})
</script>

<template>
  <section class="dashboard">
    <PageHeader title="Dashboard" subtitle="Acompanhe suas provas e o desempenho da turma.">
      <template #actions>
        <RouterLink :to="{ name: 'teacher-exam-create' }" class="button-primary">
          <Plus aria-hidden="true" />
          Criar nova prova
        </RouterLink>
      </template>
    </PageHeader>

    <AlertMessage :message="error" />

    <p v-if="loading && !summary" class="empty-state">Carregando dashboard...</p>

    <template v-else-if="summary">
      <div class="stats">
        <StatCard label="Provas criadas" :icon="ClipboardList" :value="createdExams ?? '—'" />
        <StatCard label="Alunos" :icon="Users" tone="navy" :value="studentsCount ?? '—'" />
        <StatCard label="Média da turma" :icon="GraduationCap" tone="navy" :value="formatNumber(averageGrade)" />
        <StatCard label="Top 1 geral" :icon="Trophy" :value="summary.top_student?.student_name ?? '—'" compact />
      </div>

      <p v-if="summary.total_attempts === 0" class="empty-state dashboard__notice">
        Ainda não há tentativas. Publique uma prova e peça aos alunos para respondê-la.
      </p>

      <div class="panels">
        <ExamAveragesPanel />
        <ExamRankingPanel />
      </div>
    </template>
  </section>
</template>

<style scoped>
.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.dashboard__notice {
  margin-bottom: 1.5rem;
}

.panels {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
  align-items: start;
}

@media (max-width: 1100px) {
  .panels {
    grid-template-columns: 1fr;
  }
}
</style>
