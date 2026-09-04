<script setup>
import { ref, onMounted } from 'vue';
import { useCourierStore } from '../../stores/courier';
import {
  ArrowRightLeft,
  Bike,
  Calendar,
  CheckCircle2,
  Package,
  RefreshCw,
  X
} from 'lucide-vue-next';

const courier = useCourierStore();

const dateFrom = ref('');
const dateTo = ref('');
const typeFilter = ref('all');
const errorMsg = ref('');

const fetchHistory = () => {
  errorMsg.value = '';
  courier.fetchHistory({
    type: typeFilter.value,
    from: dateFrom.value || undefined,
    to: dateTo.value || undefined,
  });
};

const typeIcon = (item) => item.type === 'pickup' ? Package : Bike;
const typeColor = (item) => item.type === 'pickup' ? 'text-teal-600 bg-teal-50' : 'text-blue-600 bg-blue-50';
const statusColor = (status) => ({
  delivered: 'bg-emerald-100 text-emerald-700',
  completed: 'bg-emerald-100 text-emerald-700',
  failed: 'bg-rose-100 text-rose-700',
  returned: 'bg-orange-100 text-orange-700',
}[status] || 'bg-slate-100 text-slate-600');

onMounted(fetchHistory);
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black text-slate-900">History</h1>
        <p class="text-xs text-slate-500 mt-0.5">Your completed pickups & deliveries</p>
      </div>
      <button @click="fetchHistory" class="p-2 hover:bg-slate-200 rounded-lg transition">
        <RefreshCw class="h-5 w-5 text-slate-600" :class="courier.loading ? 'animate-spin' : ''" />
      </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-3">
      <div class="flex gap-2 overflow-x-auto">
        <button
          v-for="t in [{ key: 'all', label: 'All' }, { key: 'deliveries', label: 'Deliveries' }, { key: 'pickups', label: 'Pickups' }]"
          :key="t.key"
          @click="typeFilter = t.key; fetchHistory()"
          class="shrink-0 px-4 py-2 rounded-lg text-xs font-bold transition-all"
          :class="typeFilter === t.key
            ? 'bg-slate-900 text-white'
            : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
        >
          {{ t.label }}
        </button>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs text-slate-500">From</label>
          <input v-model="dateFrom" type="date" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 text-sm" />
        </div>
        <div>
          <label class="text-xs text-slate-500">To</label>
          <input v-model="dateTo" type="date" class="mt-1 w-full border border-slate-300 rounded-lg p-2.5 text-sm" />
        </div>
      </div>
      <button @click="fetchHistory" class="w-full py-2.5 bg-teal-600 text-white text-sm font-bold rounded-lg hover:bg-teal-500">
        Filter
      </button>
    </div>

    <!-- History List -->
    <div v-if="!courier.loading" class="space-y-3">
      <div
        v-for="item in courier.history.data"
        :key="item.id + '-' + item.type"
        class="bg-white rounded-xl border border-slate-200 p-4 flex items-start gap-3"
      >
        <div class="h-10 w-10 rounded-full flex items-center justify-center shrink-0" :class="typeColor(item)">
          <component :is="typeIcon(item)" class="h-5 w-5" />
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <span class="font-mono text-xs font-bold text-slate-700">
              {{ item.awb || item.code }}
            </span>
            <span class="px-2 py-0.5 rounded-full text-xs font-bold capitalize" :class="statusColor(item.status)">
              {{ item.status }}
            </span>
          </div>
          <p class="text-sm font-semibold text-slate-900 mt-1">
            {{ item.type === 'pickup' ? item.seller : item.recipient }}
          </p>
          <div class="flex items-center gap-3 mt-1 text-xs text-slate-500">
            <span v-if="item.completed_at" class="flex items-center gap-1">
              <Calendar class="h-3 w-3" />
              {{ new Date(item.completed_at).toLocaleDateString() }}
            </span>
            <span v-if="item.parcels" class="flex items-center gap-1 text-teal-700 font-semibold">
              <Package class="h-3 w-3" />
              {{ item.parcels }} parcels
            </span>
          </div>
        </div>
      </div>

      <div v-if="!courier.history.data.length" class="text-center py-12 text-slate-400">
        <ArrowRightLeft class="h-12 w-12 mx-auto mb-3 text-slate-300" />
        <p class="font-bold">No history</p>
        <p class="text-xs mt-1">No completed items in this period</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-else class="flex justify-center py-12">
      <RefreshCw class="h-8 w-8 text-slate-600 animate-spin" />
    </div>
  </div>
</template>
