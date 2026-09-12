<script setup>
import { onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useCourierStore } from '../stores/courier';
import {
  Activity,
  ArrowUpRight,
  Bike,
  Calendar,
  CheckCircle2,
  Clock,
  Package,
  RefreshCw,
  TrendingUp,
  Truck,
  Wallet
} from 'lucide-vue-next';

const courier = useCourierStore();
const router = useRouter();

const stats = computed(() => courier.stats);

const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';
  return 'Good evening';
});

const quickActions = [
  { label: 'View Pickups', to: '/courier/pickups', icon: Package, color: 'from-teal-500 to-teal-600', bg: 'bg-teal-50', text: 'text-teal-600' },
  { label: 'View Deliveries', to: '/courier/deliveries', icon: Bike, color: 'from-blue-500 to-blue-600', bg: 'bg-blue-50', text: 'text-blue-600' },
  { label: 'Check Earnings', to: '/courier/earnings', icon: Wallet, color: 'from-emerald-500 to-emerald-600', bg: 'bg-emerald-50', text: 'text-emerald-600' },
];

const refresh = () => courier.fetchDashboard();

onMounted(() => {
  courier.fetchDashboard();
});
</script>

<template>
  <div class="p-4 md:p-6 space-y-6">
    <!-- Greeting Header -->
    <div class="bg-gradient-to-r from-teal-600 to-blue-600 rounded-2xl p-6 text-white shadow-lg">
      <p class="text-teal-100 text-sm font-medium">{{ greeting }}</p>
      <h1 class="text-2xl font-black mt-1">Ready for deliveries?</h1>
      <p class="text-teal-100 text-xs mt-1">
        {{ stats?.rider_status === 'available' ? 'You are online and ready to accept jobs.' : 'You are currently offline.' }}
      </p>

      <!-- Today's Summary -->
      <div v-if="stats" class="mt-4 grid grid-cols-3 gap-3">
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center">
          <p class="text-2xl font-black">{{ stats.today_deliveries || 0 }}</p>
          <p class="text-xs text-teal-100">Today</p>
        </div>
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center">
          <p class="text-2xl font-black">{{ stats.completed_deliveries_today || 0 }}</p>
          <p class="text-xs text-teal-100">Done</p>
        </div>
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-3 text-center">
          <p class="text-2xl font-black">₱{{ (stats.weekly_earnings || 0).toLocaleString() }}</p>
          <p class="text-xs text-teal-100">Week</p>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-3 gap-3">
      <button
        v-for="action in quickActions"
        :key="action.label"
        @click="router.push(action.to)"
        class="flex flex-col items-center gap-2 p-4 bg-white rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all active:scale-95"
      >
        <div class="h-12 w-12 rounded-xl bg-gradient-to-br p-0.5" :class="action.color">
          <div class="h-full w-full rounded-lg flex items-center justify-center" :class="action.bg">
            <component :is="action.icon" class="h-6 w-6" :class="action.text" />
          </div>
        </div>
        <span class="text-xs font-bold text-slate-700">{{ action.label }}</span>
      </button>
    </div>

    <!-- Stats Cards -->
    <div v-if="stats" class="grid gap-4 sm:grid-cols-2">
      <!-- Pending Pickups -->
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Pending Pickups</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ stats.pending_pickups || 0 }}</p>
          </div>
          <div class="h-14 w-14 rounded-xl bg-amber-50 flex items-center justify-center">
            <Package class="h-7 w-7 text-amber-500" />
          </div>
        </div>
        <button
          @click="router.push('/courier/pickups')"
          class="mt-4 flex items-center gap-1 text-xs font-bold text-teal-600 hover:text-teal-700"
        >
          View all <ArrowUpRight class="h-3 w-3" />
        </button>
      </div>

      <!-- Success Rate -->
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Success Rate</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ stats.success_rate || 100 }}%</p>
          </div>
          <div class="h-14 w-14 rounded-xl bg-emerald-50 flex items-center justify-center">
            <TrendingUp class="h-7 w-7 text-emerald-500" />
          </div>
        </div>
        <div class="mt-3 w-full bg-slate-100 rounded-full h-2">
          <div
            class="bg-emerald-500 h-2 rounded-full transition-all"
            :style="{ width: (stats.success_rate || 100) + '%' }"
          />
        </div>
      </div>

      <!-- Completed Today -->
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Pickups Today</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ stats.completed_pickups_today || 0 }}</p>
          </div>
          <div class="h-14 w-14 rounded-xl bg-blue-50 flex items-center justify-center">
            <CheckCircle2 class="h-7 w-7 text-blue-500" />
          </div>
        </div>
        <p class="mt-4 text-xs text-slate-500">Collected today</p>
      </div>

      <!-- Failed -->
      <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Failed Today</p>
            <p class="text-3xl font-black text-slate-900 mt-1">{{ stats.failed_deliveries_today || 0 }}</p>
          </div>
          <div class="h-14 w-14 rounded-xl bg-rose-50 flex items-center justify-center">
            <Clock class="h-7 w-7 text-rose-500" />
          </div>
        </div>
        <p class="mt-4 text-xs text-slate-500">Delivery attempts today</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="courier.loading && !stats" class="flex items-center justify-center py-16">
      <div class="flex flex-col items-center gap-3">
        <RefreshCw class="h-8 w-8 text-teal-600 animate-spin" />
        <p class="text-sm text-slate-500">Loading dashboard...</p>
      </div>
    </div>
  </div>
</template>
