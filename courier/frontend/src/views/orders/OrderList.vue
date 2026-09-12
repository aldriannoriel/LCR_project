<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import ManualOverrideModal from '../../components/orders/ManualOverrideModal.vue';
import { useOrderStore } from '../../stores/order';

const store = useOrderStore();
const filters = reactive({ search: '', status: 'pending', hub_id: '', date_from: '', date_to: '', page: 1, per_page: 10 });
const selectedOrder = ref(null);
let searchTimer;
const rows = ref([]);
const allSelected = ref(false);
const statusClasses = { pending: 'bg-amber-50 text-amber-800', received: 'bg-emerald-50 text-emerald-800', in_transit: 'bg-sky-50 text-sky-800', out_for_delivery: 'bg-blue-50 text-blue-800', delivered: 'bg-slate-100 text-slate-700', damaged: 'bg-red-50 text-red-800', flagged: 'bg-red-50 text-red-800' };
const label = (value) => value.replaceAll('_', ' ');

const load = () => store.fetchOrders({ ...filters, hub_id: filters.hub_id || undefined, status: filters.status || undefined });
const resetPageAndLoad = () => { filters.page = 1; load(); };
const search = () => { clearTimeout(searchTimer); searchTimer = setTimeout(resetPageAndLoad, 300); };
const setPage = (page) => { if (page > 0 && page <= store.orders.last_page) { filters.page = page; load(); } };
const saved = () => { selectedOrder.value = null; load(); };

const selectedCount = computed(() => rows.value.filter(o => o._selected && o.status === 'pending').length);

const bulkConfirm = async () => {
  const selectedOrders = rows.value.filter(o => o._selected && o.status === 'pending');
  if (selectedOrders.length === 0) {
    alert('Please select at least one order to confirm');
    return;
  }

  // Get hub_id from selected orders
  const hubId = selectedOrders[0].hub_id;

  // Check if all selected orders are for the same hub
  const mixedHubs = selectedOrders.some(o => o.hub_id !== hubId);
  if (mixedHubs) {
    alert('Selected orders belong to different hubs. Please filter by a single hub and select orders from that hub only.');
    return;
  }

  if (!confirm(`Confirm arrival for ${selectedOrders.length} order(s) at hub "${selectedOrders[0].hub?.name || 'Unknown'}"?`)) return;
  try {
    const selectedIds = selectedOrders.map(o => o.id);
    await store.bulkConfirmArrivals(selectedIds, hubId);
    await load();
    allSelected.value = false;
    alert(`${selectedOrders.length} orders confirmed as arrived`);
  } catch (error) {
    alert('Failed to confirm arrivals: ' + (error.response?.data?.message || error.message));
  }
};

const toggleAll = () => {
  const newValue = !allSelected.value;
  rows.value.forEach(o => { if (o.status === 'pending') o._selected = newValue; });
  allSelected.value = newValue;
};

const updateAllSelected = () => {
  const pendingRows = rows.value.filter(o => o.status === 'pending');
  allSelected.value = pendingRows.length > 0 && pendingRows.every(o => o._selected);
};

watch(() => store.orders.data, (newData) => {
  rows.value = (newData || []).map(o => ({ ...o, _selected: false }));
});

watch(() => [filters.status, filters.hub_id, filters.date_from, filters.date_to], resetPageAndLoad);
onMounted(async () => { await store.fetchHubs(); await load(); });
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <div>
          <p class="text-xs font-bold uppercase tracking-[0.22em] text-teal-700">Logistics control</p>
          <h1 class="mt-1 text-3xl font-black tracking-tight">Inbound arrival confirm</h1>
        </div>
        <span class="text-sm font-semibold text-slate-500">{{ store.orders.total }} awaiting arrival</span>
      </div>
    </header>
    <div class="mx-auto max-w-7xl space-y-6 px-6 py-8">
      <section class="border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 p-5">
          <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
            <div>
              <h2 class="text-xl font-bold">Packages to confirm</h2>
              <p class="mt-1 text-sm text-slate-500">Select orders that have physically arrived and click "Confirm Selected" to confirm.</p>
            </div>
            <div class="flex items-center gap-4">
              <input v-model="filters.search" @input="search" placeholder="Search AWB, sender, recipient" class="w-full border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-teal-500 lg:w-80" />
              <button @click="bulkConfirm" :disabled="selectedCount === 0" class="bg-teal-600 text-white px-5 py-2.5 rounded font-semibold hover:bg-teal-700 disabled:opacity-40 disabled:cursor-not-allowed">
                Confirm Selected ({{ selectedCount }})
              </button>
            </div>
          </div>
          <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <select v-model="filters.status" class="border border-slate-300 px-3 py-2 text-sm">
              <option value="pending">Awaiting arrival</option>
              <option value="">All statuses</option>
              <option v-for="status in ['received','in_transit','out_for_delivery','delivered','damaged','flagged']" :key="status" :value="status">{{ label(status) }}</option>
            </select>
            <select v-model="filters.hub_id" class="border border-slate-300 px-3 py-2 text-sm">
              <option value="">All hubs</option>
              <option v-for="hub in store.hubs" :key="hub.id" :value="hub.id">{{ hub.name }}</option>
            </select>
            <input v-model="filters.date_from" type="date" class="border border-slate-300 px-3 py-2 text-sm" />
            <input v-model="filters.date_to" type="date" class="border border-slate-300 px-3 py-2 text-sm" />
          </div>
        </div>
        <div v-if="store.error" class="m-5 bg-red-50 px-4 py-3 text-sm text-red-700">{{ store.error }}</div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th class="px-5 py-3">
                  <input type="checkbox" :checked="allSelected" @change="toggleAll" class="border border-teal-500 w-4 h-4 rounded text-teal-600 focus:ring-teal-500" />
                </th>
                <th class="px-5 py-3">AWB</th>
                <th class="px-5 py-3">Sender / recipient</th>
                <th class="px-5 py-3">Delivery address</th>
                <th class="px-5 py-3">Hub</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="store.loading">
                <td colspan="7" class="px-5 py-10 text-center text-slate-400">Loading arrival queue...</td>
              </tr>
              <tr v-for="order in rows" :key="order.id" class="hover:bg-teal-50/30" :class="{ 'bg-teal-50/50': order._selected }">
                <td class="px-5 py-4">
                  <input type="checkbox" v-model="order._selected" @change="updateAllSelected" class="border border-teal-500 w-4 h-4 rounded text-teal-600 focus:ring-teal-500" />
                </td>
                <td class="px-5 py-4 font-mono font-semibold">{{ order.awb_number }}</td>
                <td class="px-5 py-4">
                  <div class="font-semibold">{{ order.sender_name }}</div>
                  <div class="text-slate-500">to {{ order.recipient_name }}</div>
                </td>
                <td class="max-w-[260px] px-5 py-4 text-slate-600">{{ order.recipient_address }}</td>
                <td class="px-5 py-4 text-slate-600">{{ order.hub?.name || '—' }}</td>
                <td class="px-5 py-4">
                  <span class="inline-flex px-2.5 py-1 text-xs font-bold capitalize" :class="statusClasses[order.status]">{{ label(order.status) }}</span>
                </td>
                <td class="px-5 py-4 text-right">
                  <button v-if="order.status === 'pending'" class="font-semibold text-teal-700" @click="selectedOrder = order">Review</button>
                  <span v-else class="text-slate-400">Confirmed</span>
                </td>
              </tr>
              <tr v-if="!store.loading && !rows.length">
                <td colspan="7" class="px-5 py-10 text-center text-slate-400">No packages are waiting for arrival confirmation.</td>
              </tr>
            </tbody>
          </table>
        </div>
        <footer class="flex items-center justify-between border-t border-slate-200 px-5 py-4 text-sm">
          <span class="text-slate-500">Page {{ store.orders.current_page }} of {{ store.orders.last_page }}</span>
          <div class="flex gap-2">
            <button :disabled="store.orders.current_page <= 1" class="border border-slate-300 px-3 py-1.5 disabled:opacity-40" @click="setPage(store.orders.current_page - 1)">Previous</button>
            <button :disabled="store.orders.current_page >= store.orders.last_page" class="border border-slate-300 px-3 py-1.5 disabled:opacity-40" @click="setPage(store.orders.current_page + 1)">Next</button>
          </div>
        </footer>
      </section>
    </div>
    <ManualOverrideModal v-if="selectedOrder" :order="selectedOrder" @close="selectedOrder = null" @saved="saved" />
  </main>
</template>
