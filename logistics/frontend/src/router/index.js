import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import ChatCenter from '../views/chat/ChatCenter.vue';
import AccountSettings from '../views/account/AccountSettings.vue';
import AlonaLogisticsDashboard from '../views/alona/AlonaLogisticsDashboard.vue';
import RiderDirectory from '../views/fleet/RiderDirectory.vue';
import PickupManager from '../views/pickups/PickupManager.vue';
import ReportBuilder from '../views/reports/ReportBuilder.vue';
import DisputeCenter from '../views/disputes/DisputeCenter.vue';
import PendingApprovals from '../views/admin/PendingApprovals.vue';
import RegisterRoleView from '../views/auth/RegisterRoleView.vue';
import RegisterRiderView from '../views/auth/RegisterRiderView.vue';
import RegisterStaffView from '../views/auth/RegisterStaffView.vue';

// Courier app
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
  { path: '/register', redirect: '/auth/register-role' },
  { path: '/auth/register-role', name: 'RegisterRole', component: RegisterRoleView },
  { path: '/auth/register/rider', name: 'RegisterRider', component: RegisterRiderView },
  { path: '/auth/register/staff', name: 'RegisterStaff', component: RegisterStaffView },
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    redirect: '/alona/logistics',
    meta: { requiresAuth: true },
  },
  {
    path: '/alona/logistics',
    name: 'AlonaLogisticsDashboard',
    component: AlonaLogisticsDashboard,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'logistics_admin', 'Admin', 'Super Admin', 'Logistics Admin'] },
  },
  {
    path: '/preview/admin',
    name: 'AdminDashboardPreview',
    component: AlonaLogisticsDashboard,
    meta: { requiresAuth: true, preview: true, roles: ['admin'] },
  },
  // Core operations remain separate screens inside the Alona control room shell.
  { path: '/orders', redirect: '/alona/logistics' },
  { path: '/admin/approvals', name: 'AdminApprovals', component: PendingApprovals, meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'logistics_admin', 'Admin', 'Super Admin', 'Logistics Admin'] } },
  { path: '/sorting', redirect: '/alona/logistics' },
  { path: '/manifests', redirect: '/alona/logistics' },
  { path: '/fleet', name: 'RiderDirectory', component: RiderDirectory, meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'logistics_admin', 'Admin', 'Super Admin', 'Logistics Admin'] } },
  { path: '/hubs', redirect: '/alona/logistics' },
  { path: '/hubs/:id/inventory', redirect: '/alona/logistics' },
  { path: '/transfers', redirect: '/alona/logistics' },
  { path: '/returns', redirect: '/alona/logistics' },
  { path: '/reports', name: 'Reports', component: ReportBuilder, meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'logistics_admin', 'Admin', 'Super Admin', 'Logistics Admin'] } },
  { path: '/pickups', name: 'PickupManager', component: PickupManager, meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'logistics_admin', 'Admin', 'Super Admin', 'Logistics Admin', 'Hub Manager', 'Dispatcher'] } },
  { path: '/disputes', name: 'DisputeCenter', component: DisputeCenter, meta: { requiresAuth: true } },
  { path: '/chat', name: 'Chat', component: ChatCenter, meta: { requiresAuth: true } },
  { path: '/settings', name: 'Settings', component: AccountSettings, meta: { requiresAuth: true } },

  // ─── Courier / Rider App (Mobile-first Web) ───────────────────────
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
  if (import.meta.env.DEV && to.meta.preview) {
    localStorage.setItem('token', 'local-admin-preview');
    localStorage.setItem('user_roles', JSON.stringify(['admin']));
  }
  if (to.meta.requiresAuth && !localStorage.getItem('token')) return '/login';
  const roles = JSON.parse(localStorage.getItem('user_roles') || '[]');
  if (to.meta.roles && !to.meta.roles.some((role) => roles.includes(role))) return '/dashboard';
});

export default router;