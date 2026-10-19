<script setup>
import { computed, onMounted, ref } from 'vue'
import { ClipboardCheck, Percent, Trophy } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import AvailableExamsPanel from '@/components/AvailableExamsPanel.vue'
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
    <header class="page-header">
      <h1>Dashboard</h1>
      <p class="page-header__subtitle">Acompanhe seu desempenho e as provas que faltam fazer.</p>
    </header>

    <AlertMessage :message="error" />

    <p v-if="loading && !summary" class="dashboard__state">Carregando dashboard...</p>

    <div v-else-if="summary" class="content">
      <div class="stats">
        <article class="stat stat--done">
          <span class="stat__icon"><ClipboardCheck aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Provas realizadas</h2>
            <p class="stat__value">{{ summary.answered_count }}</p>
          </div>
        </article>

        <article class="stat stat--accuracy">
          <span class="stat__icon"><Percent aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Média de acertos</h2>
            <p class="stat__value">{{ formatNumber(averagePercentage, '%') }}</p>
          </div>
        </article>

        <article class="stat stat--best">
          <span class="stat__icon"><Trophy aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Melhor pontuação</h2>
            <p class="stat__value">{{ formatNumber(bestGrade) }}</p>
          </div>
        </article>
      </div>

      <AvailableExamsPanel />
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

.dashboard__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 12px;
}

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

.stat--done .stat__icon,
.stat--best .stat__icon {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.stat--accuracy .stat__icon {
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
