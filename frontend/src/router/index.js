import { createRouter, createWebHistory } from 'vue-router'

import { ROLES, useSessionStore } from '@/stores/session'
import HomeView from '@/views/HomeView.vue'

export const routes = [
  { path: '/', name: 'home', component: HomeView, meta: { title: 'Início' } },
  {
    path: '/teacher/exams',
    name: 'teacher-exams',
    component: () => import('@/views/teacher/ExamListView.vue'),
    meta: { role: ROLES.TEACHER, title: 'Minhas provas' },
  },
  {
    path: '/teacher/exams/new',
    name: 'teacher-exam-create',
    component: () => import('@/views/teacher/ExamFormView.vue'),
    meta: { role: ROLES.TEACHER, title: 'Criar prova' },
  },
  {
    path: '/teacher/exams/:id(\\d+)',
    name: 'teacher-exam-detail',
    component: () => import('@/views/teacher/ExamDetailView.vue'),
    props: true,
    meta: { role: ROLES.TEACHER, title: 'Detalhes da prova' },
  },
  {
    path: '/teacher/exams/:id(\\d+)/edit',
    name: 'teacher-exam-edit',
    component: () => import('@/views/teacher/ExamFormView.vue'),
    props: true,
    meta: { role: ROLES.TEACHER, title: 'Editar prova' },
  },
  {
    path: '/teacher/dashboard',
    name: 'teacher-dashboard',
    component: () => import('@/views/teacher/DashboardView.vue'),
    meta: { role: ROLES.TEACHER, title: 'Dashboard' },
  },
  {
    path: '/student/exams',
    name: 'student-exams',
    component: () => import('@/views/student/StudentExamListView.vue'),
    meta: { role: ROLES.STUDENT, title: 'Minhas provas' },
  },
  {
    path: '/student/exams/:id(\\d+)',
    name: 'student-exam-take',
    component: () => import('@/views/student/TakeExamView.vue'),
    props: true,
    meta: { role: ROLES.STUDENT, title: 'Responder prova' },
  },
  {
    path: '/student/exams/:id(\\d+)/result',
    name: 'student-exam-result',
    component: () => import('@/views/student/ExamResultView.vue'),
    props: true,
    meta: { role: ROLES.STUDENT, title: 'Resultado' },
  },
  { path: '/:pathMatch(.*)*', redirect: { name: 'home' } },
]

export function roleGuard(to) {
  const session = useSessionStore()

  if (to.meta.role && to.meta.role !== session.role) {
    return { name: 'home' }
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach(roleGuard)

export default router
