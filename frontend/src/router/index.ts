import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('@/modules/dashboard/views/DashboardView.vue'),
    },
    {
      path: '/finance',
      name: 'finance',
      component: () => import('@/modules/finance/views/FinanceView.vue'),
    },
    {
      path: '/studies',
      name: 'studies',
      component: () => import('@/modules/planner/views/StudiesView.vue'),
    },
    {
      path: '/tasks',
      name: 'tasks',
      component: () => import('@/modules/planner/views/TasksView.vue'),
    },
  ],
})

export default router
