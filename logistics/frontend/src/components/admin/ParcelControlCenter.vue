<script setup>
import { computed, onMounted, ref } from 'vue';
import { LoaderCircle, PackageSearch, RefreshCw, Send, Warehouse } from 'lucide-vue-next';
import { axios } from '../../lib/echo';

const orders = ref([]);
const hubs = ref([]);
const loading = ref(false);
const busy = ref(false);
const error = ref('');
const notice = ref('');
const search = ref('');
const status = ref('');
const hubId = ref('');
const selectedIds = ref([]);
const destinationHubId = ref('');
const statusDrafts = ref({});

const statusOptions = ['pending', 'received', 'in_transit', 'in_hub', 'out_for_delivery', 'delivered', 'in_return_queue', 'damaged', 'flagged'];
const operationalStatuses = ['received', 'in_transit', 'in_hub', 'out_for_delivery', 'delivered', 'delivery_failed'];
const label = (value) => String(value || '').replaceAll('_', ' ');
const filteredOrders = computed(() => orders.value.filter((order) => {
  const query = search.value.trim().toLowerCase();
  const matchesSearch = !query || [order.awb_number, order.sender_name, order.recipient_name, order.recipient_address].filter(Boolean).join(' ').toLowerCase().includes(query);
  return matchesSearch && (!status.value || order.status === status.value) && (!hubId.value || String(order.current_hub_id || order.hub_id) === String(hubId.value));
}));
const selectedOrders = computed(() => orders.value.filter((order) => selectedIds.value.includes(order.id)));
const sourceHubId = computed(() => selectedOrders.value[0]?.current_hub_id || selectedOrders.value[0]?.hub_id || '');
const toggleSelected = (id) => { selectedIds.value = selectedIds.value.includes(id) ? selectedIds.value.filter((item) => item !== id) : [...selectedIds.value, id]; };
const selectVisible = () => { selectedIds.value = filteredOrders.value.length && selectedIds.value.length === filteredOrders.value.length ? [] : filteredOrders.value.map((order) => order.id); };
const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const response = await axios.get('/orders', { params: { per_page: 100, status: status.value || undefined, hub_id: hubId.value || undefined } });
    orders.value = response.data.data || [];
    hubs.value = (await axios.get('/hubs')).data || [];
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Unable to load parcel operations.';
  } finally { loading.value = false; }
};
const createTransfer = async () => {
  if (!sourceHubId.value || !destinationHubId.value || !selectedIds.value.length) return;
  busy.value = true; error.value = ''; notice.value = '';
  try {
    await axios.post('/transfer-requests', { from_hub_id: sourceHubId.value, to_hub_id: destinationHubId.value, order_ids: selectedIds.value });
    notice.value = `${selectedIds.value.length} parcel(s) added to a bulk transfer request.`;
    selectedIds.value = []; destinationHubId.value = '';
    await load();
  } catch (requestError) { error.value = requestError.response?.data?.message || 'Unable to create transfer request.'; }
  finally { busy.value = false; }
};
const updateStatus = async (order) => {
  const nextStatus = statusDrafts.value[order.id];
  if (!nextStatus || nextStatus === order.status) return;
  const reason = nextStatus === 'delivery_failed' ? window.prompt('Why did delivery fail?') : '';
  if (nextStatus === 'delivery_failed' && !reason?.trim()) return;
  busy.value = true; error.value = ''; notice.value = '';
  try {
    await axios.patch(`/orders/${order.id}/operational-status`, { status: nextStatus, reason: reason || undefined });
    notice.value = `${order.awb_number} updated to ${label(nextStatus)}.`;
    await load();
  } catch (requestError) { error.value = requestError.response?.data?.message || 'Unable to update parcel status.'; }
  finally { busy.value = false; }
};
onMounted(load);
</script>

<template>
  <section class="mt-6 overflow-hidden border border-slate-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 p-5"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Parcel control center</p><h2 class="mt-1 text-xl font-black">All parcel statuses</h2><p class="mt-1 text-sm text-slate-500">Monitor parcel location, status, and bulk movement between registered hubs.</p></div><button class="inline-flex items-center gap-2 border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="load"><RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />Refresh</button></header>
    <div class="grid gap-3 border-b border-slate-200 bg-slate-50 p-4 md:grid-cols-[1.5fr_1fr_1fr]"><label class="relative"><PackageSearch class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" /><input v-model="search" class="w-full border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm" placeholder="Search tracking number or recipient" /></label><select v-model="status" class="border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">All statuses</option><option v-for="item in statusOptions" :key="item" :value="item">{{ label(item) }}</option></select><select v-model="hubId" class="border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">All current hubs</option><option v-for="hub in hubs" :key="hub.id" :value="hub.id">{{ hub.name }}</option></select></div>
    <div v-if="notice" class="border-b border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-800">{{ notice }}</div><div v-if="error" class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-sm font-bold text-rose-800">{{ error }}</div>
    <div class="overflow-x-auto"><table class="w-full min-w-[1180px] text-left text-sm"><thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3"><input type="checkbox" :checked="filteredOrders.length > 0 && selectedIds.length === filteredOrders.length" @change="selectVisible" /></th><th class="px-5 py-3">Parcel</th><th class="px-5 py-3">Destination</th><th class="px-5 py-3">Current hub</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Updated</th><th class="px-5 py-3">Staff update</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-teal-50/30"><td class="px-5 py-4"><input type="checkbox" :checked="selectedIds.includes(order.id)" @change="toggleSelected(order.id)" /></td><td class="px-5 py-4"><p class="font-mono font-black text-blue-700">{{ order.awb_number }}</p><p class="mt-1 text-xs text-slate-500">{{ order.recipient_name }}</p></td><td class="max-w-xs px-5 py-4 text-xs text-slate-600">{{ order.recipient_address || 'Destination not provided' }}</td><td class="px-5 py-4"><span class="inline-flex items-center gap-1 text-xs font-bold"><Warehouse class="h-3.5 w-3.5 text-teal-700" />{{ order.current_hub?.name || order.hub?.name || 'Unassigned' }}</span></td><td class="px-5 py-4"><span class="bg-slate-100 px-2 py-1 text-[11px] font-black capitalize text-slate-700">{{ label(order.status) }}</span></td><td class="px-5 py-4 text-xs text-slate-500">{{ order.updated_at ? new Date(order.updated_at).toLocaleString() : '—' }}</td><td class="px-5 py-4"><div class="flex items-center gap-2"><select v-model="statusDrafts[order.id]" class="border border-slate-300 bg-white px-2 py-1.5 text-xs"><option value="">Choose status</option><option v-for="item in operationalStatuses" :key="item" :value="item">{{ label(item) }}</option></select><button class="bg-slate-950 px-2 py-1.5 text-xs font-bold text-white disabled:opacity-40" :disabled="busy || !statusDrafts[order.id] || statusDrafts[order.id] === order.status" @click="updateStatus(order)">Update</button></div></td></tr><tr v-if="!loading && !filteredOrders.length"><td colspan="7" class="p-12 text-center text-sm text-slate-500">No parcels match the current filters.</td></tr><tr v-if="loading"><td colspan="7" class="p-12 text-center text-sm text-slate-500"><LoaderCircle class="mx-auto mb-2 h-5 w-5 animate-spin" />Loading parcels...</td></tr></tbody></table></div>
    <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-white p-4"><span class="text-xs font-bold text-slate-500">{{ selectedIds.length }} selected for bulk movement</span><div class="flex flex-wrap items-center gap-2"><select v-model="destinationHubId" class="border border-slate-300 bg-white px-3 py-2 text-sm" :disabled="!selectedIds.length"><option value="">Destination hub</option><option v-for="hub in hubs.filter((item) => String(item.id) !== String(sourceHubId))" :key="hub.id" :value="hub.id">{{ hub.name }}</option></select><button class="inline-flex items-center gap-2 bg-slate-950 px-4 py-2 text-sm font-black text-white disabled:opacity-40" :disabled="busy || !selectedIds.length || !destinationHubId" @click="createTransfer"><LoaderCircle v-if="busy" class="h-4 w-4 animate-spin" /><Send v-else class="h-4 w-4" />Create bulk transfer</button></div></footer>
  </section>
</template>
