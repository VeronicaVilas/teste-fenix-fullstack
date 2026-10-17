<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ChevronDown, ClipboardList, FilePlus2, Flame, LayoutDashboard, X } from 'lucide-vue-next'

import { listStudents, listTeachers } from '@/services/userService'
import { ROLES, useSessionStore } from '@/stores/session'

defineProps({
  open: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const router = useRouter()
const session = useSessionStore()

const teachers = ref([])
const students = ref([])
const accessOpen = ref(false)

const menus = {
  [ROLES.TEACHER]: [
    { label: 'Dashboard', to: { name: 'teacher-dashboard' }, icon: LayoutDashboard },
    { label: 'Minhas provas', to: { name: 'teacher-exams' }, icon: ClipboardList },
    { label: 'Criar prova', to: { name: 'teacher-exam-create' }, icon: FilePlus2 },
  ],
  [ROLES.STUDENT]: [{ label: 'Minhas provas', to: { name: 'student-exams' }, icon: ClipboardList }],
}

const homes = {
  [ROLES.TEACHER]: { name: 'teacher-exams' },
  [ROLES.STUDENT]: { name: 'student-exams' },
}

const menu = computed(() => menus[session.role] ?? [])

function initials(name) {
  return (name ?? '')
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0].toUpperCase())
    .join('')
}

function isCurrent(role, user) {
  return session.role === role && session.userId === user.id
}

function choose(role, user) {
  accessOpen.value = false
  session.enterAs(role, user)
  router.push(homes[role])
}

onMounted(async () => {
  try {
    const [teacherList, studentList] = await Promise.all([listTeachers(), listStudents()])
    teachers.value = teacherList
    students.value = studentList
  } catch {
    teachers.value = []
    students.value = []
  }
})
</script>

<template>
  <aside class="sidebar" :class="{ 'sidebar--open': open }">
    <button type="button" class="sidebar__close" aria-label="Fechar menu" @click="emit('close')">
      <X aria-hidden="true" />
    </button>

    <RouterLink :to="{ name: 'home' }" class="brand">
      <span class="brand__logo" aria-hidden="true">
        <Flame />
      </span>
      <span class="brand__text">
        <strong>Fênix</strong>
        <small>Provas online</small>
      </span>
    </RouterLink>

    <section class="sidebar__section">
      <p class="sidebar__label">Visualizando como</p>

      <div class="access">
        <button
          type="button"
          class="access__current"
          :aria-expanded="accessOpen"
          aria-haspopup="listbox"
          @click="accessOpen = !accessOpen"
        >
          <span class="avatar">{{ session.role ? initials(session.name) : '?' }}</span>
          <span class="access__name">
            <strong>{{ session.name ?? 'Selecione um acesso' }}</strong>
            <small v-if="session.role">{{ session.roleLabel }}</small>
          </span>
          <ChevronDown class="access__chevron" :class="{ 'access__chevron--open': accessOpen }" aria-hidden="true" />
        </button>

        <div v-if="accessOpen" class="access__backdrop" @click="accessOpen = false" />

        <div v-if="accessOpen" class="access__menu" role="listbox">
          <p class="access__group">Professor</p>
          <button
            v-for="teacher in teachers"
            :key="`teacher-${teacher.id}`"
            type="button"
            role="option"
            class="access__option"
            :class="{ 'access__option--active': isCurrent(ROLES.TEACHER, teacher) }"
            :aria-selected="isCurrent(ROLES.TEACHER, teacher)"
            @click="choose(ROLES.TEACHER, teacher)"
          >
            <span class="avatar avatar--small">{{ initials(teacher.name) }}</span>
            {{ teacher.name }}
          </button>

          <p class="access__group">Alunos</p>
          <button
            v-for="student in students"
            :key="`student-${student.id}`"
            type="button"
            role="option"
            class="access__option"
            :class="{ 'access__option--active': isCurrent(ROLES.STUDENT, student) }"
            :aria-selected="isCurrent(ROLES.STUDENT, student)"
            @click="choose(ROLES.STUDENT, student)"
          >
            <span class="avatar avatar--small">{{ initials(student.name) }}</span>
            {{ student.name }}
          </button>
        </div>
      </div>
    </section>

    <section v-if="menu.length" class="sidebar__section">
      <p class="sidebar__label">Menu principal</p>
      <nav class="menu">
        <RouterLink
          v-for="item in menu"
          :key="item.label"
          :to="item.to"
          class="menu__item"
          exact-active-class="menu__item--active"
        >
          <component :is="item.icon" class="menu__icon" aria-hidden="true" />
          {{ item.label }}
        </RouterLink>
      </nav>
    </section>
  </aside>
</template>

<style scoped>
.sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  padding: 1.5rem 1.25rem;
  background: var(--color-navy);
  color: var(--color-sidebar-text);
}

.sidebar__close {
  display: none;
  position: absolute;
  top: 1.25rem;
  right: 1rem;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  padding: 0;
  background: var(--color-navy-light);
  border: none;
  border-radius: 8px;
  color: #fff;
  cursor: pointer;
}

.sidebar__close:hover {
  background: var(--color-primary);
}

.brand {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding-bottom: 1.25rem;
  border-bottom: 1px solid var(--color-navy-border);
  color: inherit;
  text-decoration: none;
}

.brand__logo {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 10px;
  background: var(--color-primary);
  color: #fff;
}

.brand__logo svg {
  width: 1.4rem;
  height: 1.4rem;
}

.brand__text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.brand__text strong {
  font-size: 1.35rem;
  color: #fff;
}

.brand__text small {
  color: var(--color-primary);
  font-weight: 600;
}

.sidebar__section {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.sidebar__label {
  margin: 0;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--color-sidebar-muted);
}

.access {
  position: relative;
}

.access__current {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  padding: 0.6rem 0.75rem;
  background: var(--color-navy-light);
  border: 1px solid var(--color-navy-border);
  border-radius: 10px;
  color: inherit;
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.access__current:hover {
  border-color: var(--color-primary);
}

.access__name {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: 0;
}

.access__name strong {
  overflow: hidden;
  color: #fff;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.access__name small {
  color: var(--color-sidebar-muted);
}

.access__chevron {
  width: 1.1rem;
  height: 1.1rem;
  transition: transform 0.15s;
}

.access__chevron--open {
  transform: rotate(180deg);
}

.access__backdrop {
  position: fixed;
  inset: 0;
  z-index: 20;
}

.access__menu {
  position: absolute;
  top: calc(100% + 0.4rem);
  left: 0;
  right: 0;
  z-index: 21;
  max-height: 22rem;
  padding: 0.5rem;
  overflow-y: auto;
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: 10px;
  box-shadow: 0 12px 32px rgba(11, 22, 46, 0.25);
}

.access__group {
  margin: 0.5rem 0.5rem 0.25rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--color-muted);
}

.access__option {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  width: 100%;
  padding: 0.5rem;
  background: none;
  border: none;
  border-radius: 8px;
  color: var(--color-text);
  font: inherit;
  text-align: left;
  cursor: pointer;
}

.access__option:hover {
  background: var(--color-primary-soft);
}

.access__option--active {
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
  font-weight: 600;
}

.avatar {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 50%;
  background: var(--color-primary-soft);
  color: var(--color-primary-dark);
  font-size: 0.85rem;
  font-weight: 700;
}

.avatar--small {
  width: 1.75rem;
  height: 1.75rem;
  font-size: 0.7rem;
}

.menu {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.menu__item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.7rem 0.85rem;
  border-radius: 10px;
  color: var(--color-sidebar-text);
  font: inherit;
  text-decoration: none;
}

.menu__item:hover {
  background: var(--color-navy-light);
  color: #fff;
}

.menu__item--active {
  background: var(--color-primary);
  color: #fff;
  font-weight: 600;
}

.menu__item--active:hover {
  background: var(--color-primary-dark);
}

.menu__icon {
  flex-shrink: 0;
  width: 1.15rem;
  height: 1.15rem;
}

@media (max-width: 1024px) {
  .sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 40;
    width: min(300px, 85vw);
    overflow-y: auto;
    box-shadow: 8px 0 32px rgba(11, 22, 46, 0.35);
    transform: translateX(-100%);
    transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
  }

  .sidebar--open {
    transform: translateX(0);
  }

  .sidebar__close {
    display: grid;
  }
}
</style>
