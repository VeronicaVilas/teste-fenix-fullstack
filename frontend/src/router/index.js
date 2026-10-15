import { createRouter, createWebHistory } from 'vue-router'

import { ROLES, useSessionStore } from '@/stores/session'
import HomeView from '@/views/HomeView.vue'

export const routes = [
  { path: '/', name: 'home', component: HomeView },
  {
    path: '/teacher/exams',
    name: 'teacher-exams',
    component: () => import('@/views/teacher/ExamListView.vue'),
    meta: { role: ROLES.TEACHER },
  },
  {
    path: '/student/exams',
    name: 'student-exams',
    component: () => import('@/views/student/StudentExamListView.vue'),
    meta: { role: ROLES.STUDENT },
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
