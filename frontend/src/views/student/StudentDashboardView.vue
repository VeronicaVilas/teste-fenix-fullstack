<script setup>
import { computed, onMounted, ref } from 'vue'
import { ClipboardCheck, Percent, Trophy } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import AvailableExamsPanel from '@/components/AvailableExamsPanel.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatCard from '@/components/StatCard.vue'
import { getStudentDashboard } from '@/services/studentExamService'
import { useSessionStore } from '@/stores/session'

const session = useSessionStore()

const summary = ref(null)
const loading = ref(false)
const error = ref('')

const averagePercentage = computed(() => toNumber(summary.value?.average_percentage))

const bestGrade = computed(() => {
  const best = toNumber(summary.value?.best_percentage)

  return best === null ? null : best / 10
})

async function load() {
  loading.value = true
  error.value = ''

  try {
    summary.value = await getStudentDashboard(session.userId)
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function toNumber(value) {
  return value === null || value === undefined ? null : Number(value)
}

function formatNumber(value, suffix = '') {
  return value === null
    ? '—'
    : `${value.toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}${suffix}`
}

onMounted(load)
</script>

<template>
  <section class="dashboard">
    <PageHeader title="Dashboard" subtitle="Acompanhe seu desempenho e as provas que faltam fazer." />

    <AlertMessage :message="error" />

    <p v-if="loading && !summary" class="empty-state">Carregando dashboard...</p>

    <div v-else-if="summary" class="content">
      <div class="stats">
        <StatCard label="Provas realizadas" :icon="ClipboardCheck" :value="summary.answered_count" />
        <StatCard label="Média de acertos" :icon="Percent" tone="navy" :value="formatNumber(averagePercentage, '%')" />
        <StatCard label="Melhor pontuação" :icon="Trophy" :value="formatNumber(bestGrade)" />
      </div>

      <AvailableExamsPanel />
    </div>
  </section>
</template>

<style scoped>
.content {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.25rem;
  align-items: start;
}

.stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

@media (max-width: 1100px) {
  .content {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .stats {
    grid-template-columns: 1fr;
  }
}
</style>
