<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import AlertMessage from '@/components/AlertMessage.vue'
import { listStudents, listTeachers } from '@/services/userService'
import { ROLES, useSessionStore } from '@/stores/session'

const router = useRouter()
const session = useSessionStore()

const students = ref([])
const choosingStudent = ref(false)
const loading = ref(false)
const error = ref('')

onMounted(() => {
  if (session.isTeacher) {
    router.replace({ name: 'teacher-dashboard' })
  } else if (session.isStudent) {
    router.replace({ name: 'student-dashboard' })
  }
})

async function enterAsTeacher() {
  loading.value = true
  error.value = ''

  try {
    const [teacher] = await listTeachers()

    if (!teacher) {
      error.value = 'Nenhum professor cadastrado. Rode o seed do banco.'
      return
    }

    session.enterAs(ROLES.TEACHER, teacher)
    router.push({ name: 'teacher-dashboard' })
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

async function chooseStudent() {
  loading.value = true
  error.value = ''

  try {
    students.value = await listStudents()
    choosingStudent.value = true
  } catch (failure) {
    error.value = failure.message
  } finally {
    loading.value = false
  }
}

function enterAsStudent(student) {
  session.enterAs(ROLES.STUDENT, student)
  router.push({ name: 'student-dashboard' })
}
</script>

<template>
  <section class="home">
    <h1>Fênix — Provas Online</h1>
    <p class="home__subtitle">Crie, aplique e acompanhe avaliações de múltipla escolha.</p>

    <AlertMessage :message="error" />

    <div v-if="!choosingStudent" class="home__access">
      <button type="button" class="access-card" :disabled="loading" @click="enterAsTeacher">
        <strong>Professor</strong>
        <span>Criar e gerenciar provas, acompanhar o dashboard</span>
      </button>
      <button type="button" class="access-card" :disabled="loading" @click="chooseStudent">
        <strong>Aluno</strong>
        <span>Responder provas e ver sua pontuação</span>
      </button>
    </div>

    <div v-else class="home__students">
      <h2>Quem está acessando?</h2>
      <p v-if="students.length === 0">Nenhum aluno cadastrado. Rode o seed do banco.</p>
      <ul v-else>
        <li v-for="student in students" :key="student.id">
          <button type="button" class="student-option" @click="enterAsStudent(student)">
            {{ student.name }}
          </button>
        </li>
      </ul>
      <button type="button" class="link-button" @click="choosingStudent = false">Voltar</button>
    </div>
  </section>
</template>

<style scoped>
.home {
  text-align: center;
  padding: 2rem 1rem;
}

.home__subtitle {
  color: var(--color-muted);
  margin-bottom: 2rem;
}

.home__access {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  max-width: 600px;
  margin: 0 auto;
}

.access-card {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1.5rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 8px;
  cursor: pointer;
  font: inherit;
  color: inherit;
}

.access-card:hover:not(:disabled) {
  border-color: var(--color-primary);
}

.access-card strong {
  font-size: 1.25rem;
  color: var(--color-primary);
}

.access-card span {
  color: var(--color-muted);
}

.home__students ul {
  list-style: none;
  padding: 0;
  max-width: 360px;
  margin: 1rem auto;
  display: grid;
  gap: 0.5rem;
}

.student-option {
  width: 100%;
  padding: 0.75rem 1rem;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 6px;
  cursor: pointer;
  font: inherit;
}

.student-option:hover {
  border-color: var(--color-primary);
}

.link-button {
  background: none;
  border: none;
  color: var(--color-primary);
  cursor: pointer;
  font: inherit;
}
</style>
