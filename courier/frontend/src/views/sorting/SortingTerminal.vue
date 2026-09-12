<script setup>
import { onMounted, ref } from 'vue';
import { axios } from '../../lib/echo';
import { useOrderStore } from '../../stores/order';

const store = useOrderStore();
const pendingOrders = ref([]);
const selectedOrderIds = ref([]);
const hubs = ref([]);
const feedback = ref(null);
const sorting = ref(false);
const routedOrders = ref([]);
let refreshTimer;

const load = async () => {
  store.setAuthHeader();
  const [orders, capacity] = await Promise.all([
    axios.get('/orders', { params: { status: 'received', per_page: 100 } }),
    axios.get('/hubs/grid'),
  ]);
  pendingOrders.value = orders.data.data;
  hubs.value = capacity.data;
};
const toggleAll = (event) => { selectedOrderIds.value = event.target.checked ? pendingOrders.value.map((order) => order.id) : []; };
const capacityClass = (percentage) => percentage >= 90 ? 'bg-red-400' : percentage >= 70 ? 'bg-amber-300' : 'bg-emerald-400';
const sortSelected = async () => {
  if (!selectedOrderIds.value.length) { feedback.value = { success: false, text: 'Select at least one package to sort.' }; return; }
  sorting.value = true;
  try {
    store.setAuthHeader();
    const response = await axios.post('/orders/auto-sort', { order_ids: selectedOrderIds.value });
    routedOrders.value = [...response.data.sorted, ...routedOrders.value].slice(0, 20);
    feedback.value = { success: true, text: response.data.message, skipped: response.data.skipped };
    selectedOrderIds.value = [];
    await load();
  } catch (error) { feedback.value = { success: false, text: error.response?.data?.message || 'Sorting failed.' }; } finally { sorting.value = false; }
};
onMounted(async () => { await store.fetchHubs(); await load(); refreshTimer = setInterval(load, 15000); });
</script>

<template>
  <main class="min-h-screen bg-[#101817] p-5 text-white md:p-8"><div class="mx-auto max-w-7xl"><header class="border-b border-white/15 pb-6"><p class="text-sm font-black uppercase tracking-[0.3em] text-teal-300">Operations terminal / phase 03</p><h1 class="mt-2 text-4xl font-black tracking-tight md:text-6xl">Sorting queue</h1><p class="mt-3 max-w-xl text-slate-400">Review package details, select shipments, and route them by delivery city and province.</p></header><section class="mt-8 border border-white/15 bg-[#172522] p-6"><div class="flex items-center justify-between"><div><p class="text-sm font-black uppercase tracking-[0.25em] text-teal-300">Network capacity</p><h2 class="mt-1 text-2xl font-bold">All hubs</h2></div><span class="text-sm text-slate-400">{{ hubs.length }} hubs monitored</span></div><div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><article v-for="hub in hubs" :key="hub.id" class="border border-white/10 bg-black/20 p-4"><div class="flex justify-between gap-3"><span class="font-bold">{{ hub.name }}</span><span class="font-mono text-sm">{{ Number(hub.utilization_percentage || 0).toFixed(1) }}%</span></div><div class="mt-3 h-2 bg-white/10"><div class="h-2" :class="capacityClass(hub.utilization_percentage || 0)" :style="{ width: `${Math.min(100, Math.max(0, hub.utilization_percentage || 0))}%` }" /></div><div class="mt-2 flex justify-between text-xs text-slate-400"><span>{{ hub.current_stock }} / {{ hub.capacity }} stock</span><span>{{ hub.active_orders_count }} active</span></div></article></div></section><section class="mt-8 border-2 border-teal-400 bg-[#172522] p-6 shadow-[0_0_40px_rgba(45,212,191,0.08)]"><div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-sm font-black uppercase tracking-[0.25em] text-teal-300">Packages awaiting sort</p><h2 class="mt-1 text-2xl font-bold">Select shipments to route</h2></div><button :disabled="sorting || !selectedOrderIds.length" class="bg-teal-300 px-5 py-3 text-sm font-black text-slate-950 disabled:opacity-40" @click="sortSelected">{{ sorting ? 'Routing...' : `Sort selected (${selectedOrderIds.length})` }}</button></div><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[950px] text-left text-sm"><thead class="border-b border-white/10 text-xs uppercase tracking-wider text-slate-400"><tr><th class="w-12 px-3 py-3"><input type="checkbox" :checked="pendingOrders.length > 0 && selectedOrderIds.length === pendingOrders.length" @change="toggleAll" /></th><th class="px-3 py-3">AWB</th><th class="px-3 py-3">Sender</th><th class="px-3 py-3">Recipient</th><th class="px-3 py-3">Delivery address</th><th class="px-3 py-3">Current hub</th><th class="px-3 py-3">Received</th></tr></thead><tbody class="divide-y divide-white/10"><tr v-for="order in pendingOrders" :key="order.id" class="hover:bg-white/5"><td class="px-3 py-4"><input v-model="selectedOrderIds" type="checkbox" :value="order.id" /></td><td class="px-3 py-4 font-mono font-bold">{{ order.awb_number }}</td><td class="px-3 py-4">{{ order.sender_name }}</td><td class="px-3 py-4">{{ order.recipient_name }}</td><td class="max-w-[260px] px-3 py-4 text-slate-300">{{ order.recipient_address }}</td><td class="px-3 py-4 text-slate-300">{{ order.hub?.name || '—' }}</td><td class="px-3 py-4 text-slate-400">{{ order.scanned_at ? new Date(order.scanned_at).toLocaleString() : '—' }}</td></tr><tr v-if="!pendingOrders.length"><td colspan="7" class="p-8 text-center text-slate-400">No received packages are waiting to be sorted.</td></tr></tbody></table></div></section><section class="mt-8 border border-white/15 bg-[#172522] p-6"><p class="text-sm font-black uppercase tracking-[0.25em] text-teal-300">Recently routed</p><div class="mt-4 grid gap-3 md:grid-cols-2 lg:grid-cols-4"><article v-for="order in routedOrders" :key="order.awb_number + order.bin_code" class="border border-white/10 bg-black/20 p-4"><p class="font-mono font-bold">{{ order.awb_number }}</p><p class="mt-2 text-sm font-black text-teal-300">{{ order.bin_code }}</p><p class="mt-1 text-xs text-slate-400">{{ order.destination_hub }}</p></article><p v-if="!routedOrders.length" class="text-slate-400">No packages routed in this session.</p></div></section></div></main>
+</template>
