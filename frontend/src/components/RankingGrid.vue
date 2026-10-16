<script setup>
import { onMounted, ref, watch } from 'vue'

import AlertMessage from '@/components/AlertMessage.vue'
import PaginationBar from '@/components/PaginationBar.vue'
import { getDashboardRanking } from '@/services/dashboardService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  examId: { type: Number, default: null },
})

const session = useSessionStore()

const pageSizes = [10, 25, 50]
const medals = { 1: '🥇', 2: '🥈', 3: '🥉' }

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const perPage = ref(10)
const loading = ref(false)
const error = ref('')

async function load(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const data = await getDashboardRanking(session.userId, { examId: props.examId, page, perPage: perPage.value })
    items.value = data.items
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

function formatDate(value) {
  return new Date(value).toLocaleString('pt-BR')
}

watch([() => props.examId, perPage], () => load(1))

onMounted(() => load())
</script>

<template>
  <section class="ranking">
    <header class="ranking__header">
      <h2>Ranking</h2>
      <label>
        Itens por página
        <select v-model="perPage" :disabled="loading">
          <option v-for="size in pageSizes" :key="size" :value="size">{{ size }}</option>
        </select>
      </label>
    </header>

    <AlertMessage :message="error" />

    <p v-if="loading && items.length === 0" class="ranking__state">Carregando ranking...</p>

    <p v-else-if="!error && items.length === 0" class="ranking__state">Nenhuma tentativa para exibir.</p>

    <table v-else-if="items.length > 0" :class="{ 'ranking--loading': loading }">
      <thead>
        <tr>
          <th>#</th>
          <th>Aluno</th>
          <th>Prova</th>
          <th>Acertos</th>
          <th>%</th>
          <th>Enviada em</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.attempt_id" :class="{ podium: item.position <= 3 }">
          <td class="ranking__position">{{ medals[item.position] ?? item.position }}</td>
          <td>{{ item.student_name }}</td>
          <td>{{ item.exam_title }}</td>
          <td>{{ item.score }}/{{ item.total_questions }}</td>
          <td>
            <div class="ranking__percentage">
              <span class="ranking__bar"><span :style="{ width: `${Number(item.percentage)}%` }" /></span>
              {{ formatPercentage(item.percentage) }}
            </div>
          </td>
          <td>{{ formatDate(item.submitted_at) }}</td>
        </tr>
      </tbody>
    </table>

    <PaginationBar :meta="meta" :disabled="loading" @change="load" />
  </section>
</template>

<style scoped>
.ranking {
  margin-top: 2rem;
}

.ranking__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.ranking__header select {
  margin-left: 0.4rem;
  padding: 0.3rem 0.5rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font: inherit;
}

.ranking__state {
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
}

.ranking--loading {
  opacity: 0.6;
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

tr.podium {
  font-weight: 600;
}

.ranking__position {
  width: 3rem;
  font-size: 1.1rem;
}

.ranking__percentage {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.ranking__bar {
  width: 80px;
  height: 8px;
  background: var(--color-border);
  border-radius: 999px;
  overflow: hidden;
}

.ranking__bar span {
  display: block;
  height: 100%;
  background: var(--color-success);
}
</style>
