<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { axios } from '../lib/echo';
import {
  Activity,
  ArrowRightLeft,
  BarChart3,
  Boxes,
  ChevronDown,
  ClipboardList,
  LayoutDashboard,
  LogOut,
  Menu,
  MessageSquare,
  PackagePlus,
  PackageSearch,
  ScanLine,
  Settings,
  ShieldCheck,
  Truck,
  Users,
  X
} from 'lucide-vue-next';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const mobileOpen = ref(false);
const quickOpen = ref(false);

const navigation = computed(() => [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard },
  { label: 'Incoming Parcels', to: '/orders', icon: PackageSearch },
  { label: 'Sort Parcels', to: '/sorting', icon: ScanLine },
  { label: 'Riders', to: '/fleet', icon: Truck },
  { label: 'Hub Stock', to: '/hubs', icon: Boxes },
  { label: 'Returns', to: '/returns', icon: ArrowRightLeft },
  { label: 'Approve Users', to: '/admin/approvals', icon: ShieldCheck, roles: ['super_admin', 'hub_manager', 'admin'] },
  { label: 'Reports', to: '/reports', icon: BarChart3, roles: ['super_admin', 'hub_manager', 'admin'] },
  { label: 'Pickup Requests', to: '/pickups', icon: PackagePlus },
  { label: 'Messages', to: '/chat', icon: MessageSquare },
  { label: 'Settings', to: '/settings', icon: Settings },
].filter((item) => !item.roles || item.roles.some((allowedRole) => auth.hasRole(allowedRole))));

const userEmail = computed(() => auth.user?.email || 'Operations user');
const role = computed(() => auth.userRoles[0] || 'Dispatcher');
const hubName = computed(() => auth.user?.hub?.name || 'Network-wide');

const closeMenus = () => {
  mobileOpen.value = false;
  quickOpen.value = false;
};

const go = (to) => {
  closeMenus();
  router.push(to);
};

const logout = async () => {
  await auth.logout();
  router.push('/login');
};

onMounted(async () => {
  if (auth.token && !auth.user) {
    try {
      auth.user = (await axios.get('/me', { headers: { Authorization: `Bearer ${auth.token}` } })).data;
      auth.roles = auth.user.roles?.map((item) => item.name.toLowerCase().replaceAll(' ', '_')) || auth.roles;
      localStorage.setItem('user_roles', JSON.stringify(auth.roles));
    } catch (_) {}
  }
});
</script>

<template>
  <div class="app-theme min-h-screen bg-[#f3f4f6] text-slate-900">
    <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="mobileOpen = false" />
    <aside
      class="fixed left-0 top-0 z-50 flex h-screen w-64 -translate-x-full flex-col border-r border-slate-200 bg-white/90 text-slate-800 shadow-[0_20px_50px_rgba(15,23,42,0.08)] backdrop-blur-xl transition-transform lg:translate-x-0"
      :class="mobileOpen ? 'translate-x-0' : ''"
    >
      <div class="flex items-center justify-between border-b border-slate-200 px-5 py-5">
        <div>
          <div class="flex items-center gap-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
              <Activity class="h-4 w-4" />
            </div>
            <span class="text-lg font-black tracking-tight text-slate-900">Logistics OS</span>
          </div>
          <span class="mt-2 inline-flex max-w-full truncate rounded-full bg-blue-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-700">
            {{ hubName }}
          </span>
        </div>
        <button class="lg:hidden" aria-label="Close navigation" @click="mobileOpen = false">
          <X class="h-5 w-5 text-slate-500" />
        </button>
      </div>

      <div class="relative px-4 py-4">
        <button
          class="flex w-full items-center justify-between rounded-xl bg-blue-600 px-3 py-2.5 text-sm font-bold text-white shadow-lg shadow-blue-600/20"
          @click="quickOpen = !quickOpen"
        >
          <span class="flex items-center gap-2">
            <ChevronDown class="h-4 w-4" :class="quickOpen ? 'rotate-180' : ''" /> Quick actions
          </span>
          <span class="text-blue-100">+</span>
        </button>
        <div v-if="quickOpen" class="absolute left-4 right-4 top-16 z-10 rounded-xl border border-slate-200 bg-white p-1 shadow-xl">
          <button class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100" @click="go('/orders?focus=scanner')">
            <ScanLine class="h-4 w-4 text-blue-600" />Scan inbound AWB
          </button>
          <button class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100" @click="go('/transfers?new=1')">
            <ArrowRightLeft class="h-4 w-4 text-amber-600" />Create transfer request
          </button>
          <button class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100" @click="go('/fleet?assign=1')">
            <Users class="h-4 w-4 text-indigo-600" />Assign rider
          </button>
          <button class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-100" @click="go('/returns')">
            <ClipboardList class="h-4 w-4 text-rose-600" />Process return
          </button>
        </div>
      </div>

      <nav class="flex-1 space-y-1 overflow-y-auto px-3">
        <router-link
          v-for="item in navigation"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900"
          :class="item.highlight ? 'bg-blue-50 text-blue-700 hover:bg-blue-100 hover:text-blue-800 font-bold' : ''"
          active-class="bg-blue-600 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/15 hover:bg-blue-500 hover:text-white"
          @click="mobileOpen = false"
        >
          <component :is="item.icon" class="h-5 w-5 shrink-0" />
          {{ item.label }}
        </router-link>
      </nav>

      <footer class="border-t border-slate-200 p-4">
        <div class="mb-3 min-w-0">
          <p class="truncate text-sm font-semibold text-slate-900">{{ userEmail }}</p>
          <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">
            {{ role }}
          </span>
        </div>
        <button class="flex w-full items-center gap-2 rounded-xl px-2 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900" @click="logout">
          <LogOut class="h-4 w-4" />Log out
        </button>
      </footer>
    </aside>

    <div class="min-h-screen lg:ml-64">
      <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur-xl lg:hidden">
        <button class="text-slate-700" aria-label="Open navigation" @click="mobileOpen = true">
          <Menu class="h-6 w-6" />
        </button>
        <span class="font-black text-slate-900">Logistics OS</span>
        <button class="text-slate-700" aria-label="Quick actions" @click="go('/orders?focus=scanner')">
          <ScanLine class="h-5 w-5" />
        </button>
      </header>
      <div class="overflow-y-auto">
        <router-view />
      </div>
    </div>
  </div>
</template>