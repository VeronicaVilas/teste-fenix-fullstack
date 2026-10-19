<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowLeft, CalendarCheck, Check, FileQuestion, Pencil, Send, Trash2, Users } from 'lucide-vue-next'

import AlertMessage from '@/components/AlertMessage.vue'
import BasePanel from '@/components/BasePanel.vue'
import ConfirmDialog from '@/components/ConfirmDialog.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatCard from '@/components/StatCard.vue'
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
    <p v-if="loading" class="empty-state">Carregando prova...</p>

    <div v-else-if="notFound" class="empty-state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'teacher-exams' }">Ir para a lista de provas</RouterLink>
    </div>

    <AlertMessage v-else-if="error && !exam" :message="error" />

    <template v-if="exam">
      <PageHeader :title="exam.title" :subtitle="exam.description ?? ''">
        <template #actions>
          <div class="exam-detail__actions">
            <RouterLink :to="{ name: 'teacher-exams' }" class="button-secondary">
              <ArrowLeft aria-hidden="true" />
              Voltar
            </RouterLink>
            <template v-if="isDraft">
              <button type="button" class="button-primary publish" @click="confirmingPublish = true">
                <Send aria-hidden="true" />
                Publicar
              </button>
              <RouterLink
                :to="{ name: 'teacher-exam-edit', params: { id: exam.id } }"
                class="icon-button icon-button--large"
                title="Editar prova"
                aria-label="Editar prova"
              >
                <Pencil aria-hidden="true" />
              </RouterLink>
            </template>
            <button
              type="button"
              class="icon-button icon-button--large danger"
              :disabled="exam.attempts_count > 0"
              :title="exam.attempts_count > 0 ? 'Prova já respondida por alunos' : 'Excluir prova'"
              aria-label="Excluir prova"
              @click="confirmingDelete = true"
            >
              <Trash2 aria-hidden="true" />
            </button>
          </div>
        </template>
      </PageHeader>

      <AlertMessage :message="error" />
      <AlertMessage :message="success" type="success" />
      <AlertMessage v-if="!isDraft" type="info" message="Prova publicada: não pode mais ser editada." />

      <div class="stats">
        <StatCard label="Status" :icon="Check" compact>
          <StatusBadge :status="exam.status" />
        </StatCard>
        <StatCard label="Questões" :icon="FileQuestion" tone="navy" :value="exam.questions_count" />
        <StatCard label="Tentativas" :icon="Users" :value="exam.attempts_count" />
        <StatCard label="Publicada em" :icon="CalendarCheck" tone="navy" :value="formatDate(exam.published_at)" compact />
      </div>

      <BasePanel title="Questões" subtitle="A alternativa correta de cada questão aparece em verde.">
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
      </BasePanel>
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
.exam-detail__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
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
