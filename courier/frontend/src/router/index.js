import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import CourierAdminDashboard from '../views/CourierAdminDashboard.vue';
import Register from '../views/auth/Register.vue';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: Login,
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
  },
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/courier',
    name: 'CourierAdmin',
    component: CourierAdminDashboard,
    meta: { requiresAuth: true, roles: ['courier_admin', 'admin', 'super_admin', 'hub_manager', 'Courier Admin', 'Admin', 'Super Admin', 'Hub Manager'] },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  if (to.meta.requiresAuth && !localStorage.getItem('token')) return '/login';
  const roles = JSON.parse(localStorage.getItem('user_roles') || '[]');
  if (to.meta.roles && !to.meta.roles.some((role) => roles.includes(role))) return '/dashboard';
});

export default router;