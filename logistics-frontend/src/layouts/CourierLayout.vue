<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useCourierStore } from '../stores/courier';
import { axios, echo } from '../lib/echo';
import NotificationBanner from '../components/courier/NotificationBanner.vue';
import {
  Activity,
  Bike,
  ChevronLeft,
  History,
  Home,
  LogOut,
  MapPin,
  Menu,
  MessageSquare,
  Package,
  Settings,
  Wallet,
  X,
  Bell
} from 'lucide-vue-next';

const auth = useAuthStore();
const courier = useCourierStore();
const route = useRoute();
const router = useRouter();

const mobileOpen = ref(false);

// Courier-specific nav items
const navItems = [
  { label: 'Dashboard', to: '/courier', icon: Home, exact: true },
  { label: 'Pickups', to: '/courier/pickups', icon: Package },
  { label: 'Deliveries', to: '/courier/deliveries', icon: Bike },
  { label: 'Earnings', to: '/courier/earnings', icon: Wallet },
  { label: 'History', to: '/courier/history', icon: History },
];

const bottomNav = computed(() => navItems.slice(0, 5));

const userName = computed(() => auth.user?.name || 'Courier');
const riderStatus = computed(() => courier.stats?.rider_status || 'off_duty');

const statusColor = computed(() => ({
  available: 'bg-emerald-500',
  on_delivery: 'bg-blue-500',
  off_duty: 'bg-slate-400',
  suspended: 'bg-red-500',
}[riderStatus.value] || 'bg-slate-400'));

const statusLabel = computed(() => ({
  available: 'Available',
  on_delivery: 'On Delivery',
  off_duty: 'Off Duty',
  suspended: 'Suspended',
}[riderStatus.value] || 'Unknown'));

const go = (to) => {
  mobileOpen.value = false;
  router.push(to);
};

const toggleStatus = async () => {
  const newStatus = riderStatus.value === 'available' ? 'off_duty' : 'available';
  try {
    await courier.toggleStatus(newStatus);
    courier.fetchDashboard();
  } catch (e) {
    console.error('Failed to toggle status:', e);
  }
};

const logout = async () => {
  await auth.logout();
  router.push('/login');
};

onMounted(async () => {
  // Restore session
  if (auth.token && !auth.user) {
    try {
      auth.user = (await axios.get('/me', { headers: { Authorization: `Bearer ${auth.token}` } })).data;
      auth.roles = auth.user.roles?.map(r => r.name.toLowerCase().replaceAll(' ', '_')) || auth.roles;
      localStorage.setItem('user_roles', JSON.stringify(auth.roles));
    } catch (_) {}
  }

  // Fetch initial dashboard data
  courier.fetchDashboard();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100">
    <!-- Mobile Notification Banner -->
    <NotificationBanner />

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-sm">
      <div class="flex items-center justify-between px-4 py-3">
        <!-- Logo & Status Toggle -->
        <div class="flex items-center gap-3">
          <button class="lg:hidden p-2 -ml-2 hover:bg-slate-100 rounded-lg" @click="mobileOpen = !mobileOpen">
            <Menu class="h-6 w-6 text-slate-700" />
          </button>
          <div class="flex items-center gap-2">
            <div class="h-9 w-9 rounded-xl bg-gradient-to-br from-teal-500 to-blue-600 flex items-center justify-center shadow">
              <Bike class="h-5 w-5 text-white" />
            </div>
            <div class="hidden sm:block">
              <h1 class="font-black text-slate-900 text-lg leading-tight">Courier</h1>
              <p class="text-xs text-slate-500">LCR Logistics</p>
            </div>
          </div>
        </div>

        <!-- Status Toggle & Actions -->
        <div class="flex items-center gap-2">
          <!-- Online Status -->
          <button
            @click="toggleStatus"
            class="flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold transition-all"
            :class="riderStatus === 'available'
              ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            <span class="h-2 w-2 rounded-full animate-pulse" :class="statusColor" />
            {{ statusLabel }}
          </button>

          <!-- Messages -->
          <router-link to="/chat" class="p-2 hover:bg-slate-100 rounded-lg">
            <MessageSquare class="h-5 w-5 text-slate-600" />
          </router-link>

          <!-- Settings -->
          <router-link to="/courier/settings" class="p-2 hover:bg-slate-100 rounded-lg">
            <Settings class="h-5 w-5 text-slate-600" />
          </router-link>
        </div>
      </div>
    </header>

    <!-- Mobile Sidebar Overlay -->
    <div v-if="mobileOpen" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="mobileOpen = false" />

    <!-- Sidebar (Desktop) -->
    <aside
      class="fixed left-0 top-[65px] bottom-16 lg:top-[65px] z-40 w-64 -translate-x-full lg:translate-x-0 flex flex-col bg-white border-r border-slate-200 transition-transform overflow-y-auto"
      :class="mobileOpen ? 'translate-x-0' : ''"
    >
      <!-- Rider Profile Card -->
      <div class="p-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div class="h-12 w-12 rounded-full bg-gradient-to-br from-teal-400 to-blue-500 flex items-center justify-center text-white font-black text-lg shadow">
            {{ userName.charAt(0) }}
          </div>
          <div class="min-w-0">
            <p class="font-bold text-slate-900 truncate">{{ userName }}</p>
            <p class="text-xs text-slate-500 truncate">{{ auth.user?.rider?.hub?.name || 'Assigned Hub' }}</p>
          </div>
        </div>
        <div class="mt-3 flex items-center gap-2">
          <span class="h-2 w-2 rounded-full animate-pulse" :class="statusColor" />
          <span class="text-xs font-medium" :class="riderStatus === 'available' ? 'text-emerald-600' : 'text-slate-500'">
            {{ statusLabel }}
          </span>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 p-3 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition-all"
          :class="route.path === item.to
            ? 'bg-teal-600 text-white shadow-md'
            : 'text-slate-600 hover:bg-slate-100'"
          @click="mobileOpen = false"
        >
          <component :is="item.icon" class="h-5 w-5 shrink-0" />
          {{ item.label }}
        </router-link>
      </nav>

      <!-- Logout -->
      <div class="p-3 border-t border-slate-100">
        <button
          @click="logout"
          class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-red-600 hover:bg-red-50 transition-all"
        >
          <LogOut class="h-5 w-5" />
          Log Out
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="min-h-[calc(100vh-65px-64px)] lg:ml-64 lg:min-h-[calc(100vh-65px)] pb-20 lg:pb-6">
      <router-view />
    </main>

    <!-- Bottom Navigation (Mobile) -->
    <nav class="fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-slate-200 lg:hidden">
      <div class="flex items-center justify-around py-2">
        <router-link
          v-for="item in bottomNav"
          :key="item.to"
          :to="item.to"
          class="flex flex-col items-center gap-1 px-4 py-2 text-xs font-medium transition-all"
          :class="route.path.startsWith(item.to)
            ? 'text-teal-600'
            : 'text-slate-500'"
        >
          <component
            :is="item.icon"
            class="h-5 w-5"
            :stroke-width="route.path.startsWith(item.to) ? 2.5 : 2"
          />
          {{ item.label }}
        </router-link>
      </div>
    </nav>
  </div>
</template>
