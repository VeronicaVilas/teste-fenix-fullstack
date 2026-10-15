<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { getExam } from '@/services/examService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  id: { type: String, required: true },
})

const session = useSessionStore()

const exam = ref(null)
const loading = ref(true)
const notFound = ref(false)
const error = ref('')

const letters = 'ABCDE'

const isDraft = computed(() => exam.value?.status === 'draft')

function formatDate(value) {
  return value ? new Date(value).toLocaleString('pt-BR') : '—'
}

onMounted(async () => {
  try {
    exam.value = await getExam(session.userId, props.id)
  } catch (failure) {
    if (failure.status === 404) {
      notFound.value = true
    } else {
      error.value = failure.message
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="exam-detail">
    <RouterLink :to="{ name: 'teacher-exams' }" class="exam-detail__back">← Voltar para provas</RouterLink>

    <p v-if="loading" class="exam-detail__state">Carregando prova...</p>

    <div v-else-if="notFound" class="exam-detail__state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'teacher-exams' }">Ir para a lista de provas</RouterLink>
    </div>

    <AlertMessage v-else-if="error" :message="error" />

    <template v-else-if="exam">
      <header class="exam-detail__header">
        <div>
          <h1>{{ exam.title }}</h1>
          <p v-if="exam.description" class="exam-detail__description">{{ exam.description }}</p>
          <p class="exam-detail__meta">
            <StatusBadge :status="exam.status" />
            <span>{{ exam.questions_count }} questões</span>
            <span>{{ exam.attempts_count }} tentativas</span>
            <span v-if="exam.published_at">Publicada em {{ formatDate(exam.published_at) }}</span>
          </p>
        </div>

        <div class="exam-detail__actions">
          <template v-if="isDraft">
            <RouterLink :to="{ name: 'teacher-exam-edit', params: { id: exam.id } }" class="button">Editar</RouterLink>
            <button type="button" disabled title="Disponível em breve">Publicar</button>
          </template>
          <button type="button" class="danger" disabled title="Disponível em breve">Excluir</button>
        </div>
      </header>

      <AlertMessage
        v-if="!isDraft"
        type="info"
        message="Prova publicada: não pode mais ser editada."
      />

      <ol class="questions">
        <li v-for="question in exam.questions" :key="question.id" class="question">
          <p class="question__statement">{{ question.statement }}</p>
          <ul class="options">
            <li
              v-for="(option, index) in question.options"
              :key="option.id"
              class="option"
              :class="{ 'option--correct': option.is_correct }"
            >
              <span class="option__letter">{{ letters[index] }}</span>
              <span>{{ option.text }}</span>
              <span v-if="option.is_correct" class="option__mark" aria-label="Alternativa correta">✓ Correta</span>
            </li>
          </ul>
        </li>
      </ol>
    </template>
  </section>
</template>

<style scoped>
.exam-detail__back {
  color: var(--color-muted);
  text-decoration: none;
}

.exam-detail__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
}

.exam-detail__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin: 1rem 0;
}

.exam-detail__header h1 {
  margin: 0 0 0.25rem;
}

.exam-detail__description {
  margin: 0 0 0.5rem;
  color: var(--color-muted);
}

.exam-detail__meta {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin: 0;
  color: var(--color-muted);
}

.exam-detail__actions {
  display: flex;
  gap: 0.5rem;
}

.questions {
  padding-left: 1.25rem;
  display: grid;
  gap: 1rem;
}

.question {
  padding: 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.question__statement {
  margin: 0 0 0.75rem;
  font-weight: 600;
}

.options {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 0.4rem;
}

.option {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
}

.option--correct {
  border-color: var(--color-success);
  background: #ebfbee;
}

.option__letter {
  font-weight: 600;
}

.option__mark {
  margin-left: auto;
  color: var(--color-success);
  font-weight: 600;
}

button,
.button {
  padding: 0.4rem 0.8rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
  color: inherit;
  text-decoration: none;
}

button.danger {
  color: var(--color-danger);
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
