<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight, ChevronRight, ClipboardList, Search } from 'lucide-vue-next'

const props = defineProps({
  exams: { type: Array, required: true },
})

const PER_PAGE = 4

const search = ref('')
const currentPage = ref(1)

const filteredExams = computed(() => {
  const term = search.value.trim().toLowerCase()

  return props.exams.filter((exam) => exam.title.toLowerCase().includes(term))
})

const lastPage = computed(() => Math.max(1, Math.ceil(filteredExams.value.length / PER_PAGE)))

const pages = computed(() => Array.from({ length: lastPage.value }, (_, index) => index + 1))

const visibleExams = computed(() => {
  const start = (currentPage.value - 1) * PER_PAGE

  return filteredExams.value.slice(start, start + PER_PAGE)
})

watch(search, () => {
  currentPage.value = 1
})

function formatGrade(percentage) {
  return percentage === null
    ? '—'
    : (Number(percentage) / 10).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })
}

function attemptsLabel(count) {
  return `${count} ${count === 1 ? 'tentativa' : 'tentativas'}`
}
</script>

<template>
  <section class="panel">
    <header class="panel__header">
      <div>
        <h2>Média das provas</h2>
        <p>Acompanhe a média de cada prova publicada</p>
      </div>
      <RouterLink :to="{ name: 'teacher-exams' }" class="panel__link">
        Ver todas
        <ArrowRight aria-hidden="true" />
      </RouterLink>
    </header>

    <label class="search">
      <Search aria-hidden="true" />
      <input v-model="search" type="search" placeholder="Buscar prova..." aria-label="Buscar prova" />
    </label>

    <p v-if="visibleExams.length === 0" class="panel__empty">Nenhuma prova encontrada.</p>

    <ul v-else class="exams">
      <li v-for="(exam, index) in visibleExams" :key="exam.id">
        <RouterLink :to="{ name: 'teacher-exam-detail', params: { id: exam.id } }" class="exam">
          <span class="exam__icon" :class="`exam__icon--${index % 4}`">
            <ClipboardList aria-hidden="true" />
          </span>
          <span class="exam__info">
            <strong>{{ exam.title }}</strong>
            <small>{{ attemptsLabel(exam.attempts_count) }}</small>
          </span>
          <span class="exam__grade">
            <strong>{{ formatGrade(exam.average_percentage) }}</strong>
            <small>média</small>
          </span>
          <ChevronRight class="exam__chevron" aria-hidden="true" />
        </RouterLink>
      </li>
    </ul>

    <nav v-if="lastPage > 1" class="pagination" aria-label="Páginas das provas">
      <button
        v-for="page in pages"
        :key="page"
        type="button"
        :class="{ active: page === currentPage }"
        :aria-current="page === currentPage ? 'page' : undefined"
        @click="currentPage = page"
      >
        {{ page }}
      </button>
    </nav>
  </section>
</template>

<style scoped>
.panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
}

.panel__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.panel__header h2 {
  margin: 0;
  font-size: 1.2rem;
  color: var(--color-navy);
}

.panel__header p {
  margin: 0.3rem 0 0;
  color: var(--color-muted);
}

.panel__link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--color-primary-dark);
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}

.panel__link svg {
  width: 1rem;
  height: 1rem;
  transition: transform 0.2s ease;
}

.panel__link:hover svg {
  transform: translateX(3px);
}

.search {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 0.85rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: var(--color-muted);
}

.search:focus-within {
  border-color: var(--color-primary);
}

.search svg {
  flex-shrink: 0;
  width: 1.1rem;
  height: 1.1rem;
}

.search input {
  width: 100%;
  padding: 0.75rem 0;
  border: none;
  outline: none;
  background: none;
  color: var(--color-text);
  font: inherit;
}

.panel__empty {
  margin: 0;
  padding: 1.5rem;
  text-align: center;
  color: var(--color-muted);
}

.exams {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.75rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.exam {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: inherit;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.exam:hover {
  border-color: var(--color-primary);
  box-shadow: 0 6px 18px rgba(15, 30, 61, 0.08);
}

.exam__icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 10px;
}

.exam__icon svg {
  width: 1.2rem;
  height: 1.2rem;
}

.exam__icon--0 {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.exam__icon--1 {
  background: #e8edf7;
  color: var(--color-navy);
}

.exam__icon--2 {
  background: #fde7d4;
  color: #c2410c;
}

.exam__icon--3 {
  background: #dfe6f3;
  color: var(--color-navy-light);
}

.exam__info {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.exam__info strong {
  color: var(--color-navy);
  overflow-wrap: anywhere;
}

.exam__info small,
.exam__grade small {
  color: var(--color-muted);
}

.exam__grade {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.exam__grade strong {
  font-size: 1.1rem;
  color: var(--color-navy);
}

.exam__chevron {
  flex-shrink: 0;
  width: 1.1rem;
  height: 1.1rem;
  color: var(--color-muted);
}

.pagination {
  display: flex;
  justify-content: center;
  gap: 0.5rem;
}

.pagination button {
  min-width: 2.4rem;
  height: 2.4rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  color: var(--color-muted);
  font: inherit;
  cursor: pointer;
}

.pagination button:hover:not(.active) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.pagination button.active {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
  font-weight: 700;
}
</style>
