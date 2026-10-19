<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, Play } from 'lucide-vue-next'

import BasePanel from '@/components/BasePanel.vue'
import ExamRow from '@/components/ExamRow.vue'
import PanelPagination from '@/components/PanelPagination.vue'
import SearchField from '@/components/SearchField.vue'
import { listAvailableExams } from '@/services/studentExamService'
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
    const data = await listAvailableExams(session.userId, {
      page,
      perPage: PER_PAGE,
      status: 'pending',
      search: search.value,
    })
    exams.value = data.items
    meta.value = data.meta
    loaded.value = true
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function questionsLabel(count) {
  return `${count} ${count === 1 ? 'questão' : 'questões'}`
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => load(1), SEARCH_DELAY)
})

onMounted(() => load())

onBeforeUnmount(() => clearTimeout(searchTimer))
</script>

<template>
  <BasePanel title="Provas disponíveis" subtitle="Provas publicadas que você ainda não fez">
    <template #actions>
      <RouterLink :to="{ name: 'student-exams' }" class="panel__link">
        Ver todas
        <ArrowRight aria-hidden="true" />
      </RouterLink>
    </template>

    <SearchField v-model="search" placeholder="Buscar prova..." label="Buscar prova" />

    <p v-if="error" class="panel__empty panel__empty--error">{{ error }}</p>

    <p v-else-if="!loaded" class="panel__empty">Carregando provas...</p>

    <p v-else-if="exams.length === 0" class="panel__empty">
      {{ searching ? 'Nenhuma prova encontrada.' : 'Nenhuma prova para fazer no momento.' }}
    </p>

    <ul v-else class="exams" :class="{ 'list-loading': loading }">
      <li v-for="(exam, index) in exams" :key="exam.id">
        <ExamRow :title="exam.title" :caption="questionsLabel(exam.questions_count)" :tone="index">
          <RouterLink
            :to="{ name: 'student-exam-take', params: { id: exam.id } }"
            class="button-primary button-primary--small exam__start"
          >
            <Play aria-hidden="true" />
            Iniciar
          </RouterLink>
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
</style>
