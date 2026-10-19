<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, ChevronRight } from 'lucide-vue-next'

import BasePanel from '@/components/BasePanel.vue'
import ExamRow from '@/components/ExamRow.vue'
import PanelPagination from '@/components/PanelPagination.vue'
import SearchField from '@/components/SearchField.vue'
import { getExamAverages } from '@/services/dashboardService'
import { useSessionStore } from '@/stores/session'

const PER_PAGE = 4
const SEARCH_DELAY = 300

const session = useSessionStore()

const search = ref('')
const exams = ref([])
const meta = ref({ current_page: 1, last_page: 1, per_page: PER_PAGE, total: 0 })
const loading = ref(false)
const loaded = ref(false)
const error = ref('')

let searchTimer = null

const searching = computed(() => search.value.trim() !== '')

async function load(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const data = await getExamAverages(session.userId, { page, perPage: PER_PAGE, search: search.value })
    exams.value = data.items
    meta.value = data.meta
    loaded.value = true
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function formatGrade(percentage) {
  return percentage === null
    ? '—'
    : (Number(percentage) / 10).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
}

function attemptsLabel(count) {
  return `${count} ${count === 1 ? 'tentativa' : 'tentativas'}`
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), SEARCH_DELAY)
})

onMounted(() => load())

onBeforeUnmount(() => clearTimeout(searchTimer))
</script>

<template>
  <BasePanel title="Média das provas" subtitle="Acompanhe a média de cada prova publicada">
    <template #actions>
      <RouterLink :to="{ name: 'teacher-exams' }" class="panel__link">
        Ver todas
        <ArrowRight aria-hidden="true" />
      </RouterLink>
    </template>

    <SearchField v-model="search" placeholder="Buscar prova..." label="Buscar prova" />

    <p v-if="error" class="panel__empty panel__empty--error">{{ error }}</p>

    <p v-else-if="!loaded" class="panel__empty">Carregando provas...</p>

    <p v-else-if="exams.length === 0" class="panel__empty">
      {{ searching ? 'Nenhuma prova encontrada.' : 'Nenhuma prova publicada ainda.' }}
    </p>

    <ul v-else class="exams" :class="{ 'list-loading': loading }">
      <li v-for="(exam, index) in exams" :key="exam.id">
        <ExamRow
          :title="exam.title"
          :caption="attemptsLabel(exam.attempts_count)"
          :tone="index"
          :to="{ name: 'teacher-exam-detail', params: { id: exam.id } }"
        >
          <span class="exam__grade">
            <strong>{{ formatGrade(exam.average_percentage) }}</strong>
            <small>média</small>
          </span>
          <ChevronRight class="exam__chevron" aria-hidden="true" />
        </ExamRow>
      </li>
    </ul>

    <PanelPagination
      :current-page="meta.current_page"
      :last-page="meta.last_page"
      :disabled="loading"
      label="Páginas das provas"
      @change="load"
    />
  </BasePanel>
</template>

<style scoped>
.exams {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.exam__grade {
  display: flex;
  flex-shrink: 0;
  flex-direction: column;
  align-items: flex-end;
}

.exam__grade strong {
  font-size: 1.1rem;
  color: var(--color-navy);
}

.exam__grade small {
  color: var(--color-muted);
}

.exam__chevron {
  flex-shrink: 0;
  width: 1.1rem;
  height: 1.1rem;
  color: var(--color-muted);
}
</style>
