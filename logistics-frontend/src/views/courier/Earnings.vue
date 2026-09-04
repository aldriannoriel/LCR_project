<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourierStore } from '../../stores/courier';
import {
  Bike,
  Calendar,
  CheckCircle2,
  Package,
  RefreshCw,
  TrendingUp,
  Wallet,
  X
} from 'lucide-vue-next';

const courier = useCourierStore();

const today = new Date();
const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, 1);
const toDate = today.toISOString().split('T')[0];
const fromDate = monthAgo.toISOString().split('T')[0];

const dateRange = ref({ from: fromDate, to: toDate });
const errorMsg = ref('');

const summary = computed(() => courier.earnings?.summary || null);
const period = computed(() => courier.earnings?.period || null);
const weekly = computed(() => courier.earnings?.weekly_breakdown || []);

// Maximum for chart scaling
const maxEarnings = computed(() => Math.max(...weekly.value.map(w => w.earnings), 1));

const fetchEarnings = () => {
  errorMsg.value = '';
  if (!dateRange.value.from || !dateRange.value.to) {
    errorMsg.value = 'Please select both from and to dates.';
    return;
  }
  courier.fetchEarnings({ from: dateRange.value.from, to: dateRange.value.to });
};

onMounted(fetchEarnings);
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black text-slate-900">Earnings</h1>
        <p class="text-xs text-slate-500 mt-0.5">Track your delivery earnings</p>
      </div>
      <button @click="fetchEarnings" class="p-2 hover:bg-slate-200 rounded-lg transition">
        <RefreshCw class="h-5 w-5 text-slate-600" :class="courier.loading ? 'animate-spin' : ''" />
      </button>
    </div>

    <!-- Error -->
    <div v-if="errorMsg" class="flex items-center gap-2 bg-rose-50 border border-rose-200 rounded-xl p-4">
      <X class="h-5 w-5 text-rose-600" />
      <p class="text-sm font-semibold text-rose-800 flex-1">{{ errorMsg }}</p>
    </div>

    <!-- Date Range -->
    <div class="bg-white rounded-xl border border-slate-200 p-4">
      <div class="flex items-center gap-2 mb-3">
        <Calendar class="h-4 w-4 text-slate-500" />
        <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Date Range</span>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500">From</label>
          <input v-model="dateRange.from" type="date" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 text-sm font-semibold" />
        </div>
        <div>
          <label class="text-xs text-slate-500">To</label>
          <input v-model="dateRange.to" type="date" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 text-sm font-semibold" />
        </div>
      </div>
      <button @click="fetchEarnings" class="mt-3 w-full py-2.5 bg-teal-600 text-white text-sm font-bold rounded-lg hover:bg-teal-500">
        Update
      </button>
    </div>

    <!-- Total Earnings Hero -->
    <div v-if="summary" class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl p-6 text-white shadow-lg">
      <p class="text-xs font-bold uppercase tracking-wider text-emerald-100">Total Earnings</p>
      <p class="text-4xl font-black mt-2">₱{{ (summary.total_earnings || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
      <p class="text-xs text-emerald-100 mt-1">{{ period?.from }} — {{ period?.to }}</p>
    </div>

    <!-- Summary Cards -->
    <div v-if="summary" class="grid grid-cols-3 gap-3">
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <div class="h-10 w-10 rounded-full bg-emerald-50 flex items-center justify-center mx-auto mb-2">
          <CheckCircle2 class="h-5 w-5 text-emerald-600" />
        </div>
        <p class="text-2xl font-black text-slate-900">{{ summary.completed_deliveries || 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Delivered</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <div class="h-10 w-10 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-2">
          <X class="h-5 w-5 text-rose-600" />
        </div>
        <p class="text-2xl font-black text-slate-900">{{ summary.failed_deliveries || 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Failed</p>
      </div>
      <div class="bg-white rounded-xl border border-slate-200 p-4 text-center">
        <div class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-2">
          <Package class="h-5 w-5 text-blue-600" />
        </div>
        <p class="text-2xl font-black text-slate-900">{{ summary.completed_pickups || 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">Pickups</p>
      </div>
    </div>

    <!-- Rate Card -->
    <div v-if="summary" class="bg-white rounded-xl border border-slate-200 p-4">
      <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Your Rates</h3>
      <div class="space-y-2">
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-600">Per Successful Delivery</span>
          <span class="font-bold text-emerald-700">₱{{ summary.delivery_rate }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-600">Per Pickup</span>
          <span class="font-bold text-blue-700">₱{{ summary.pickup_rate }}</span>
        </div>
      </div>
    </div>

    <!-- Weekly Trend -->
    <div v-if="weekly.length" class="bg-white rounded-xl border border-slate-200 p-4">
      <div class="flex items-center gap-2 mb-4">
        <TrendingUp class="h-4 w-4 text-teal-600" />
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Last 4 Weeks</h3>
      </div>
      <div class="space-y-3">
        <div v-for="(week, idx) in weekly" :key="idx" class="flex items-center gap-3">
          <span class="text-xs font-semibold text-slate-600 w-16">{{ week.week }}</span>
          <div class="flex-1 bg-slate-100 rounded-full h-7 overflow-hidden relative">
            <div
              class="bg-gradient-to-r from-teal-500 to-blue-500 h-full rounded-full transition-all flex items-center justify-end pr-2"
              :style="{ width: Math.max((week.earnings / maxEarnings) * 100, 8) + '%' }"
            >
              <span class="text-xs font-bold text-white whitespace-nowrap">
                ₱{{ week.earnings.toLocaleString() }}
              </span>
            </div>
          </div>
          <span class="text-xs font-semibold text-slate-500 w-10 text-right">{{ week.deliveries }}</span>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="courier.loading && !summary" class="flex justify-center py-12">
      <RefreshCw class="h-8 w-8 text-teal-600 animate-spin" />
    </div>
  </div>
</template>
