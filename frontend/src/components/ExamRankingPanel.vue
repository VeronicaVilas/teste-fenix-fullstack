<script setup>
import { onMounted, ref } from 'vue'

import BasePanel from '@/components/BasePanel.vue'
import PanelPagination from '@/components/PanelPagination.vue'
import { getExamsRanking } from '@/services/dashboardService'
import { useSessionStore } from '@/stores/session'

const PER_PAGE = 4

const session = useSessionStore()

const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: PER_PAGE, total: 0 })
const loading = ref(false)
const error = ref('')

async function load(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const data = await getExamsRanking(session.userId, { page, perPage: PER_PAGE })
    items.value = data.items
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

function studentsLabel(count) {
  return `${count} ${count === 1 ? 'aluno' : 'alunos'}`
}

onMounted(() => load())
</script>

<template>
  <BasePanel title="Ranking das médias das provas" subtitle="Confira as provas com melhor média de desempenho">
    <template #actions>
      <span class="panel__page">Página {{ meta.current_page }}</span>
    </template>

    <p v-if="error" class="panel__empty panel__empty--error">{{ error }}</p>

    <p v-else-if="!loading && items.length === 0" class="panel__empty">Nenhuma prova respondida ainda.</p>

    <ol v-else class="grid" :class="{ 'list-loading': loading }">
      <li v-for="item in items" :key="item.exam_id" class="rank-card">
        <span class="rank-card__position" :class="`rank-card__position--${Math.min(item.position, 4)}`">
          {{ item.position }}
        </span>
        <div class="rank-card__body">
          <div class="rank-card__top">
            <div class="rank-card__info">
              <strong :title="item.title">{{ item.title }}</strong>
              <small>{{ studentsLabel(item.students_count) }}</small>
            </div>
            <div class="rank-card__grade">
              <strong>{{ formatGrade(item.average_percentage) }}</strong>
              <small>média</small>
            </div>
          </div>
          <div class="rank-card__bar" role="progressbar" :aria-valuenow="Number(item.average_percentage)" aria-valuemin="0" aria-valuemax="100">
            <span :style="{ width: `${Number(item.average_percentage)}%` }" />
          </div>
          <p class="rank-card__best">Melhor aluno: <strong>{{ item.best_student_name }}</strong></p>
        </div>
      </li>
    </ol>

    <PanelPagination
      :current-page="meta.current_page"
      :last-page="meta.last_page"
      :disabled="loading"
      label="Páginas do ranking"
      @change="load"
    />
  </BasePanel>
</template>

<style scoped>




.panel__page {
  padding: 0.5rem 0.9rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-muted);
  white-space: nowrap;
}



.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(240px, 100%), 1fr));
  gap: 0.85rem;
  margin: 0;
  padding: 0;
  list-style: none;
}


.rank-card {
  min-width: 0;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
}

.rank-card__position {
  width: 1.25rem;
  margin-top: 0.1rem;
  font-size: 1.15rem;
  font-weight: 800;
  text-align: center;
}

.rank-card__position--1 {
  color: var(--color-primary);
}

.rank-card__position--2 {
  color: #8a94a8;
}

.rank-card__position--3 {
  color: #b4682e;
}

.rank-card__position--4 {
  color: var(--color-navy-border);
}

.rank-card__body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 0.6rem;
  min-width: 0;
}

.rank-card__top {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

.rank-card__info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.rank-card__info strong {
  color: var(--color-navy);
  overflow-wrap: anywhere;
}

.rank-card__info small,
.rank-card__grade small {
  color: var(--color-muted);
}

.rank-card__grade {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.rank-card__grade strong {
  font-size: 1.15rem;
  color: var(--color-navy);
}

.rank-card__bar {
  height: 6px;
  background: var(--color-primary-soft);
  border-radius: 999px;
  overflow: hidden;
}

.rank-card__bar span {
  display: block;
  height: 100%;
  background: linear-gradient(90deg, var(--color-primary), #fdba74);
  border-radius: 999px;
}

.rank-card__best {
  overflow: hidden;
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-muted);
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rank-card__best strong {
  color: var(--color-navy);
}




</style>
