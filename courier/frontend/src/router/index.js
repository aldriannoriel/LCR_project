import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/auth/Register.vue';
import RegistrationSubmitted from '../views/auth/RegistrationSubmitted.vue';
import PendingApprovals from '../views/admin/PendingApprovals.vue';
import OrderList from '../views/orders/OrderList.vue';
import SortingTerminal from '../views/sorting/SortingTerminal.vue';
import ManifestManager from '../views/manifests/ManifestManager.vue';
import RiderDirectory from '../views/fleet/RiderDirectory.vue';
import HubGrid from '../views/hubs/HubGrid.vue';
import HubInventoryAudit from '../views/hubs/HubInventoryAudit.vue';
import TransferRequests from '../views/hubs/TransferRequests.vue';
import ReturnIntakeQueue from '../views/returns/ReturnIntakeQueue.vue';
import DashboardOverview from '../views/DashboardOverview.vue';
import ReportBuilder from '../views/reports/ReportBuilder.vue';
import PickupManager from '../views/pickups/PickupManager.vue';
import ChatCenter from '../views/chat/ChatCenter.vue';
import AccountSettings from '../views/account/AccountSettings.vue';

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
    redirect: '/login',
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: DashboardOverview,
    meta: { requiresAuth: true },
  },
  {
    path: '/orders',
    name: 'Orders',
    component: OrderList,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/approvals',
    name: 'PendingApprovals',
    component: PendingApprovals,
    meta: { requiresAuth: true, roles: ['super_admin', 'hub_manager', 'admin'] },
  },
  { path: '/sorting', name: 'Sorting', component: SortingTerminal, meta: { requiresAuth: true } },
  { path: '/manifests', name: 'Manifests', component: ManifestManager, meta: { requiresAuth: true } },
  { path: '/fleet', name: 'Fleet', component: RiderDirectory, meta: { requiresAuth: true } },
  { path: '/hubs', name: 'Hubs', component: HubGrid, meta: { requiresAuth: true } },
  { path: '/hubs/:id/inventory', name: 'HubInventory', component: HubInventoryAudit, meta: { requiresAuth: true } },
  { path: '/transfers', name: 'Transfers', component: TransferRequests, meta: { requiresAuth: true } },
  { path: '/returns', name: 'Returns', component: ReturnIntakeQueue, meta: { requiresAuth: true } },
  { path: '/reports', name: 'Reports', component: ReportBuilder, meta: { requiresAuth: true, roles: ['super_admin', 'hub_manager', 'admin'] } },
  { path: '/pickups', name: 'Pickups', component: PickupManager, meta: { requiresAuth: true } },
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
  if (to.meta.requiresAuth && !localStorage.getItem('token')) return '/login';
  const roles = JSON.parse(localStorage.getItem('user_roles') || '[]');
  if (to.meta.roles && !to.meta.roles.some((role) => roles.includes(role))) return '/dashboard';
});

export default router;