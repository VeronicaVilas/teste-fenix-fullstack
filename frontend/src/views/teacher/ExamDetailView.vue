<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowLeft, CalendarCheck, Check, FileQuestion, Pencil, Send, Trash2, Users } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { deleteExam, getExam, publishExam } from '@/services/examService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  id: { type: String, required: true },
})

const router = useRouter()
const session = useSessionStore()

const exam = ref(null)
const loading = ref(true)
const notFound = ref(false)
const error = ref('')
const confirmingDelete = ref(false)
const deleting = ref(false)
const confirmingPublish = ref(false)
const publishing = ref(false)
const success = ref('')

const letters = 'ABCDE'

const isDraft = computed(() => exam.value?.status === 'draft')

async function confirmPublish() {
  publishing.value = true
  error.value = ''

  try {
    exam.value = await publishExam(session.userId, props.id)
    success.value = 'Prova publicada.'
  } catch (failure) {
    error.value = failure.message
  } finally {
    publishing.value = false
    confirmingPublish.value = false
  }
}

async function confirmDelete() {
  deleting.value = true
  error.value = ''

  try {
    await deleteExam(session.userId, props.id)
    router.push({ name: 'teacher-exams', query: { deleted: '1' } })
  } catch (failure) {
    error.value = failure.message
  } finally {
    deleting.value = false
    confirmingDelete.value = false
  }
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('pt-BR') : '—'
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
    <p v-if="loading" class="exam-detail__state">Carregando prova...</p>

    <div v-else-if="notFound" class="exam-detail__state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'teacher-exams' }">Ir para a lista de provas</RouterLink>
    </div>

    <AlertMessage v-else-if="error && !exam" :message="error" />

    <template v-if="exam">
      <header class="page-header">
        <div class="page-header__text">
          <h1>{{ exam.title }}</h1>
          <p v-if="exam.description" class="page-header__subtitle">{{ exam.description }}</p>
        </div>

        <div class="exam-detail__actions">
          <RouterLink :to="{ name: 'teacher-exams' }" class="button-secondary">
            <ArrowLeft aria-hidden="true" />
            Voltar
          </RouterLink>
          <template v-if="isDraft">
            <button type="button" class="publish" @click="confirmingPublish = true">
              <Send aria-hidden="true" />
              Publicar
            </button>
            <RouterLink
              :to="{ name: 'teacher-exam-edit', params: { id: exam.id } }"
              class="icon-button"
              title="Editar prova"
              aria-label="Editar prova"
            >
              <Pencil aria-hidden="true" />
            </RouterLink>
          </template>
          <button
            type="button"
            class="icon-button danger"
            :disabled="exam.attempts_count > 0"
            :title="exam.attempts_count > 0 ? 'Prova já respondida por alunos' : 'Excluir prova'"
            aria-label="Excluir prova"
            @click="confirmingDelete = true"
          >
            <Trash2 aria-hidden="true" />
          </button>
        </div>
      </header>

      <AlertMessage :message="error" />
      <AlertMessage :message="success" type="success" />
      <AlertMessage v-if="!isDraft" type="info" message="Prova publicada: não pode mais ser editada." />

      <div class="stats">
        <article class="stat stat--status">
          <span class="stat__icon"><Check aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Status</h2>
            <p class="stat__value"><StatusBadge :status="exam.status" /></p>
          </div>
        </article>

        <article class="stat stat--questions">
          <span class="stat__icon"><FileQuestion aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Questões</h2>
            <p class="stat__value">{{ exam.questions_count }}</p>
          </div>
        </article>

        <article class="stat stat--attempts">
          <span class="stat__icon"><Users aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Tentativas</h2>
            <p class="stat__value">{{ exam.attempts_count }}</p>
          </div>
        </article>

        <article class="stat stat--published">
          <span class="stat__icon"><CalendarCheck aria-hidden="true" /></span>
          <div class="stat__body">
            <h2>Publicada em</h2>
            <p class="stat__value">{{ formatDate(exam.published_at) }}</p>
          </div>
        </article>
      </div>

      <section class="panel">
        <header class="panel__header">
          <h2>Questões</h2>
          <p>A alternativa correta de cada questão aparece em verde.</p>
        </header>

        <ol class="questions">
          <li v-for="(question, questionIndex) in exam.questions" :key="question.id" class="question">
            <span class="question__number">Questão {{ questionIndex + 1 }}</span>
            <p class="question__statement">{{ question.statement }}</p>
            <ul class="options">
              <li
                v-for="(option, index) in question.options"
                :key="option.id"
                class="option"
                :class="{ 'option--correct': option.is_correct }"
              >
                <span class="option__letter">{{ letters[index] }}</span>
                <span class="option__text">{{ option.text }}</span>
                <span v-if="option.is_correct" class="option__mark" aria-label="Alternativa correta">✓ Correta</span>
              </li>
            </ul>
          </li>
        </ol>
      </section>
    </template>

    <ConfirmDialog
      :open="confirmingPublish"
      title="Publicar prova"
      :message="`Publicar a prova '${exam?.title}'? Depois de publicada ela ficará disponível para os alunos e não poderá mais ser editada.`"
      confirm-label="Publicar"
      :processing="publishing"
      @confirm="confirmPublish"
      @cancel="confirmingPublish = false"
    />

    <ConfirmDialog
      :open="confirmingDelete"
      title="Excluir prova"
      :message="`Excluir a prova '${exam?.title}'? Esta ação não pode ser desfeita.`"
      confirm-label="Excluir"
      danger
      :processing="deleting"
      @confirm="confirmDelete"
      @cancel="confirmingDelete = false"
    />
  </section>
</template>

<style scoped>
.exam-detail__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
  background: var(--color-surface);
  border: 1px dashed var(--color-border);
  border-radius: 12px;
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.75rem;
}

.page-header__text {
  min-width: 0;
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

.exam-detail__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.button-secondary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 2.75rem;
  padding: 0 1.1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: var(--color-navy);
  font-weight: 600;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    color 0.2s ease;
}

.button-secondary:hover {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.publish {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  height: 2.75rem;
  padding: 0 1.1rem;
  background: var(--color-primary);
  border: none;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(249, 115, 22, 0.3);
  color: #fff;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.publish:hover {
  background: var(--color-primary-dark);
}

.icon-button {
  display: inline-grid;
  place-items: center;
  width: 2.75rem;
  height: 2.75rem;
  padding: 0;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 12px;
  color: var(--color-navy);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease;
}

.icon-button:hover:not(:disabled) {
  border-color: var(--color-primary);
  color: var(--color-primary-dark);
}

.icon-button.danger {
  color: var(--color-danger);
}

.icon-button.danger:hover:not(:disabled) {
  border-color: var(--color-danger);
  background: #fff5f5;
}

.icon-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.button-secondary svg,
.publish svg,
.icon-button svg {
  width: 1.1rem;
  height: 1.1rem;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
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

.stat--status .stat__icon,
.stat--attempts .stat__icon {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
}

.stat--questions .stat__icon,
.stat--published .stat__icon {
  background: #e8edf7;
  color: var(--color-navy);
}

.stat h2 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--color-muted);
}

.stat__value {
  margin: 0.35rem 0 0;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
  color: var(--color-navy);
}

.panel {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 16px;
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

.questions {
  display: grid;
  gap: 1rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.question {
  padding: 1.25rem;
  background: #f8fafd;
  border: 1px solid var(--color-border);
  border-left: 4px solid var(--color-primary);
  border-radius: 12px;
}

.question__number {
  color: var(--color-primary-dark);
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.question__statement {
  margin: 0.35rem 0 0.9rem;
  color: var(--color-navy);
  font-weight: 600;
}

.options {
  display: grid;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.85rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
}

.option__letter {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2rem;
  height: 2rem;
  background: #e8edf7;
  border-radius: 8px;
  color: var(--color-navy);
  font-weight: 700;
}

.option__text {
  flex: 1;
  min-width: 0;
}

.option--correct {
  background: #ebfbee;
  border-color: var(--color-success);
}

.option--correct .option__letter {
  background: var(--color-success);
  color: #fff;
}

.option__mark {
  color: var(--color-success);
  font-size: 0.9rem;
  font-weight: 600;
  white-space: nowrap;
}
</style>
