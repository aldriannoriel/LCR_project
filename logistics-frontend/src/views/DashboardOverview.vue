<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { axios } from '../lib/echo';
import {
  ArrowUpRight,
  Bell,
  CalendarDays,
  CheckCircle2,
  ChevronRight,
  Clock3,
  MapPinned,
  PackageCheck,
  Search,
  Truck,
  Users,
  Wallet,
} from 'lucide-vue-next';

const loading = ref(true);
const metrics = reactive({
  delivered: 384,
  active_riders: 42,
  on_time_rate: 96,
  revenue: 184320,
  pending: 28,
  shipments_today: 524,
});

const formatCurrency = (value) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(value);

const kpis = computed(() => [
  {
    label: 'Delivered Today',
    value: metrics.delivered,
    change: '+12.4%',
    accent: 'bg-blue-50 text-blue-700',
    icon: PackageCheck,
  },
  {
    label: 'Active Riders',
    value: metrics.active_riders,
    change: '+6 riders',
    accent: 'bg-indigo-50 text-indigo-700',
    icon: Users,
  },
  {
    label: 'On-Time Rate',
    value: `${metrics.on_time_rate}%`,
    change: '+2.1%',
    accent: 'bg-emerald-50 text-emerald-700',
    icon: CheckCircle2,
  },
  {
    label: 'Gross Revenue',
    value: formatCurrency(metrics.revenue),
    change: '+8.7%',
    accent: 'bg-slate-900 text-white',
    icon: Wallet,
  },
]);

const deliveryStages = [
  { label: 'Pending', value: 18, color: 'bg-slate-200 text-slate-700', total: 40 },
  { label: 'Assigned', value: 32, color: 'bg-blue-100 text-blue-700', total: 48 },
  { label: 'In Transit', value: 41, color: 'bg-indigo-100 text-indigo-700', total: 58 },
  { label: 'Completed', value: 78, color: 'bg-emerald-100 text-emerald-700', total: 92 },
];

const riderRows = [
  { name: 'Maria Santos', area: 'Quezon City', parcels: 28, eta: '12 min', status: 'On route', tone: 'bg-emerald-100 text-emerald-700' },
  { name: 'John Cruz', area: 'Makati', parcels: 16, eta: '18 min', status: 'Break', tone: 'bg-amber-100 text-amber-700' },
  { name: 'Rafael Lim', area: 'Pasig', parcels: 22, eta: '9 min', status: 'On route', tone: 'bg-emerald-100 text-emerald-700' },
  { name: 'Anne dela Rosa', area: 'Taguig', parcels: 12, eta: '25 min', status: 'Delayed', tone: 'bg-red-100 text-red-700' },
];

const trafficBars = [70, 88, 62, 95, 76, 82, 90, 72];

const liveUpdates = [
  { title: 'Route optimized for Central District', time: '4 min ago', tone: 'bg-blue-100 text-blue-700' },
  { title: '2 new pickup requests assigned', time: '12 min ago', tone: 'bg-emerald-100 text-emerald-700' },
  { title: 'High-priority delivery in Makati', time: '19 min ago', tone: 'bg-amber-100 text-amber-700' },
];

const load = async () => {
  const token = localStorage.getItem('token');
  if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;

  try {
    const response = await axios.get('/dashboard/metrics');
    const payload = response.data || {};

    metrics.delivered = payload.delivered ?? metrics.delivered;
    metrics.active_riders = payload.active_riders ?? metrics.active_riders;
    metrics.on_time_rate = payload.on_time_rate ?? metrics.on_time_rate;
    metrics.revenue = payload.revenue ?? metrics.revenue;
    metrics.pending = payload.pending ?? metrics.pending;
    metrics.shipments_today = payload.shipments_today ?? metrics.shipments_today;
  } catch (_) {
    // fallback demo values remain in place
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  load();
  timer = setInterval(load, 60000);
});

onBeforeUnmount(() => clearInterval(timer));

let timer;
</script>

<template>
  <main class="min-h-screen bg-[#f3f4f6] text-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
      <header class="rounded-[28px] border border-slate-200 bg-white px-5 py-4 shadow-[0_18px_60px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-blue-600">Operations dashboard</p>
            <div class="mt-2 flex items-center gap-3">
              <h1 class="text-3xl font-black tracking-tight text-slate-900">Courier & Logistics</h1>
              <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-500" /> Live
              </span>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500">
              <CalendarDays class="h-4 w-4 text-slate-500" />
              Today, 8:45 AM
            </div>
            <button class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10">
              <Bell class="h-4 w-4" /> Alerts
            </button>
            <button class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500">
              Dispatch Task
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </div>
      </header>

      <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="item in kpis"
          :key="item.label"
          class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-[0_16px_36px_rgba(15,23,42,0.05)]"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-medium text-slate-500">{{ item.label }}</p>
              <p class="mt-4 text-3xl font-black tracking-tight text-slate-900">{{ item.value }}</p>
            </div>
            <div :class="item.accent" class="flex h-12 w-12 items-center justify-center rounded-2xl">
              <component :is="item.icon" class="h-6 w-6" />
            </div>
          </div>

          <div class="mt-5 flex items-center justify-between text-sm">
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 font-semibold text-blue-700">
              <ArrowUpRight class="h-3.5 w-3.5" />
              {{ item.change }}
            </span>
            <span class="text-slate-400">vs yesterday</span>
          </div>
        </div>
      </section>

      <section class="mt-6 grid gap-6 xl:grid-cols-[1.4fr_0.6fr]">
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
          <div class="flex items-center justify-between gap-4 pb-4">
            <div>
              <p class="text-sm font-medium text-slate-500">Fleet Overview</p>
              <h2 class="mt-1 text-xl font-black text-slate-900">Live network coverage</h2>
            </div>
            <button class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-blue-700">
              <MapPinned class="h-3.5 w-3.5" /> Metro
            </button>
          </div>

          <div class="relative overflow-hidden rounded-[24px] border border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.14),_transparent_40%),linear-gradient(135deg,#ffffff_0%,#f9fafb_100%)] p-4">
            <div class="grid h-[260px] grid-cols-8 gap-3">
              <div v-for="n in 32" :key="n" class="rounded-xl border border-dashed border-slate-200 bg-white/60" />
            </div>

            <div class="absolute inset-0 flex items-center justify-center">
              <div class="flex h-40 w-40 items-center justify-center rounded-full border border-blue-200 bg-blue-50/80 shadow-[0_0_0_18px_rgba(37,99,235,0.05)]">
                <div class="text-center">
                  <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-600">Dispatch</p>
                  <p class="mt-2 text-3xl font-black text-slate-900">{{ metrics.shipments_today }}</p>
                  <p class="text-xs text-slate-500">shipments today</p>
                </div>
              </div>
            </div>

            <div class="absolute left-8 top-8 flex items-center gap-2 rounded-full border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-md">
              <Truck class="h-3.5 w-3.5 text-blue-600" /> North Hub
            </div>
            <div class="absolute right-8 top-12 flex items-center gap-2 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-md">
              <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" /> South Hub
            </div>
            <div class="absolute bottom-10 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-md">
              <MapPinned class="h-3.5 w-3.5 text-blue-600" /> Central route active
            </div>
          </div>
        </div>

        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-slate-500">Delivery Pipeline</p>
              <h2 class="mt-1 text-xl font-black text-slate-900">Flow status</h2>
            </div>
          </div>

          <div class="mt-5 space-y-4">
            <div v-for="stage in deliveryStages" :key="stage.label" class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
              <div class="flex items-center justify-between gap-3">
                <span :class="stage.color" class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.2em]">
                  {{ stage.label }}
                </span>
                <span class="text-xs font-semibold text-slate-500">{{ stage.value }}/{{ stage.total }}</span>
              </div>

              <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-200">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-blue-600 to-indigo-500"
                  :style="{ width: `${(stage.value / stage.total) * 100}%` }"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
          <div class="mb-4 flex items-center justify-between gap-4">
            <div>
              <p class="text-sm font-medium text-slate-500">Rider performance</p>
              <h2 class="mt-1 text-xl font-black text-slate-900">Active assignments</h2>
            </div>
            <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600">
              View all
              <ChevronRight class="h-3.5 w-3.5" />
            </button>
          </div>

          <div class="overflow-hidden rounded-2xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
              <thead class="bg-slate-50 text-slate-500">
                <tr>
                  <th class="px-4 py-3 font-semibold">Rider</th>
                  <th class="px-4 py-3 font-semibold">Area</th>
                  <th class="px-4 py-3 font-semibold">Parcels</th>
                  <th class="px-4 py-3 font-semibold">ETA</th>
                  <th class="px-4 py-3 font-semibold">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 bg-white">
                <tr v-for="rider in riderRows" :key="rider.name" class="hover:bg-slate-50/80">
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                      <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">
                        {{ rider.name.charAt(0) }}
                      </div>
                      <span class="font-semibold text-slate-800">{{ rider.name }}</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-slate-600">{{ rider.area }}</td>
                  <td class="px-4 py-3 text-slate-700 font-semibold">{{ rider.parcels }}</td>
                  <td class="px-4 py-3 text-slate-700 font-medium">{{ rider.eta }}</td>
                  <td class="px-4 py-3">
                    <span :class="rider.tone" class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em]">
                      {{ rider.status }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-slate-500">Regional volume</p>
                <h2 class="mt-1 text-xl font-black text-slate-900">Delivery load</h2>
              </div>
              <div class="flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-blue-700">
                <Clock3 class="h-3.5 w-3.5" />
                <span class="text-[10px] font-bold uppercase tracking-[0.18em]">4h trend</span>
              </div>
            </div>

            <div class="mt-6 flex h-36 items-end gap-2">
              <div v-for="(bar, index) in trafficBars" :key="index" class="flex-1 rounded-t-2xl bg-gradient-to-t from-blue-700 to-blue-400" :style="{ height: `${bar}%` }" />
            </div>
          </div>

          <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-slate-500">Live updates</p>
                <h2 class="mt-1 text-xl font-black text-slate-900">Operations feed</h2>
              </div>
              <button class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-600">
                <Search class="h-3 w-3" /> Monitor
              </button>
            </div>

            <div class="mt-5 space-y-3">
              <div v-for="item in liveUpdates" :key="item.title" class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                <span :class="item.tone" class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full">
                  <span class="h-2.5 w-2.5 rounded-full bg-current" />
                </span>
                <div class="flex-1">
                  <p class="font-semibold text-slate-800">{{ item.title }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ item.time }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>
