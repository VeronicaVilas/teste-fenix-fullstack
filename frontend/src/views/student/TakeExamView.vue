<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import { getAvailableExam } from '@/services/studentExamService'
import { useSessionStore } from '@/stores/session'

const props = defineProps({
  id: { type: String, required: true },
})

const session = useSessionStore()

const exam = ref(null)
const answers = reactive({})
const loading = ref(true)
const notFound = ref(false)
const error = ref('')

const letters = 'ABCDE'

const answeredCount = computed(() => Object.keys(answers).length)
const totalQuestions = computed(() => exam.value?.questions.length ?? 0)

onMounted(async () => {
  try {
    exam.value = await getAvailableExam(session.userId, props.id)
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
  <section class="take-exam">
    <RouterLink :to="{ name: 'student-exams' }" class="take-exam__back">← Voltar para minhas provas</RouterLink>

    <p v-if="loading" class="take-exam__state">Carregando prova...</p>

    <div v-else-if="notFound" class="take-exam__state">
      <p>Prova não encontrada.</p>
      <RouterLink :to="{ name: 'student-exams' }">Ir para minhas provas</RouterLink>
    </div>

    <AlertMessage v-else-if="error" :message="error" />

    <div v-else-if="exam.answered" class="take-exam__state">
      <p>Você já respondeu esta prova.</p>
      <RouterLink :to="{ name: 'student-exams' }">Ir para minhas provas</RouterLink>
    </div>

    <template v-else>
      <header class="take-exam__header">
        <h1>{{ exam.title }}</h1>
        <p v-if="exam.description" class="take-exam__description">{{ exam.description }}</p>
      </header>

      <AlertMessage type="info" message="Você só pode enviar esta prova uma vez." />

      <ol class="questions">
        <li v-for="question in exam.questions" :key="question.id" class="question">
          <fieldset>
            <legend>{{ question.statement }}</legend>
            <label
              v-for="(option, index) in question.options"
              :key="option.id"
              class="option"
              :class="{ 'option--selected': answers[question.id] === option.id }"
            >
              <input v-model="answers[question.id]" type="radio" :name="`question-${question.id}`" :value="option.id" />
              <span class="option__letter">{{ letters[index] }}</span>
              <span>{{ option.text }}</span>
            </label>
          </fieldset>
        </li>
      </ol>

      <footer class="take-exam__footer">
        <span>Respondidas: {{ answeredCount }} de {{ totalQuestions }}</span>
        <button type="button" class="primary" disabled title="Disponível em breve">Enviar prova</button>
      </footer>
    </template>
  </section>
</template>

<style scoped>
.take-exam__back {
  color: var(--color-muted);
  text-decoration: none;
}

.take-exam__state {
  padding: 2rem;
  text-align: center;
  color: var(--color-muted);
}

.take-exam__header h1 {
  margin: 1rem 0 0.25rem;
}

.take-exam__description {
  margin: 0 0 1rem;
  color: var(--color-muted);
}

.questions {
  padding-left: 1.25rem;
  display: grid;
  gap: 1rem;
}

.question fieldset {
  margin: 0;
  padding: 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

.question legend {
  font-weight: 600;
  padding: 0;
  margin-bottom: 0.75rem;
}

.option {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.4rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
}

.option--selected {
  border-color: var(--color-primary);
  background: #fff4e6;
}

.option__letter {
  font-weight: 600;
}

.take-exam__footer {
  position: sticky;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  margin-top: 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
}

button {
  padding: 0.5rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}

button.primary {
  background: var(--color-primary);
  border-color: var(--color-primary);
  color: #fff;
}

button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
