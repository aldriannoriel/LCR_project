import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/auth/Register.vue';
import RegistrationSubmitted from '../views/auth/RegistrationSubmitted.vue';
import CourierLayout from '../courier/layouts/CourierLayout.vue';
import CourierDashboard from '../courier/views/Dashboard.vue';
import CourierPickups from '../courier/views/Pickups.vue';
import CourierDeliveries from '../courier/views/Deliveries.vue';
import CourierEarnings from '../courier/views/Earnings.vue';
import CourierHistory from '../courier/views/History.vue';
import CourierSettings from '../courier/views/Settings.vue';

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
    path: '/registration-submitted',
    name: 'RegistrationSubmitted',
    component: RegistrationSubmitted,
  },
  {
    path: '/',
    redirect: '/courier',
  },
  {
    path: '/courier',
    component: CourierLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '', name: 'CourierDashboard', component: CourierDashboard },
      { path: 'pickups', name: 'CourierPickups', component: CourierPickups },
      { path: 'deliveries', name: 'CourierDeliveries', component: CourierDeliveries },
      { path: 'earnings', name: 'CourierEarnings', component: CourierEarnings },
      { path: 'history', name: 'CourierHistory', component: CourierHistory },
      { path: 'settings', name: 'CourierSettings', component: CourierSettings },
    ],
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