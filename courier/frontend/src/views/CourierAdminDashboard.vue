<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { AlertTriangle, CheckCircle2, ClipboardList, LoaderCircle, PackageSearch, RefreshCw, Send, Truck, Users, Warehouse } from 'lucide-vue-next';
import { axios } from '../lib/echo';

const activeTab = ref('overview');
const route = useRoute();
const activeMode = ref('bulk');
const loading = ref(false);
const error = ref('');
const notice = ref('');
const orders = ref([]);
const riders = ref([]);
const pickups = ref([]);
const hubs = ref([]);
const search = ref('');
const statusFilter = ref('');
const selectedIds = ref([]);
const selectedRider = ref('');
const receivingHubId = ref('');
const expectedCount = ref('');
const busy = ref(false);

const statuses = ['received', 'in_transit', 'in_hub', 'assigned_to_rider', 'out_for_delivery', 'delivered', 'in_return_queue'];
const label = (value) => String(value || '').replaceAll('_', ' ');
const modeOrders = computed(() => {
  if (activeMode.value === 'assign') return orders.value.filter((order) => ['received', 'in_hub'].includes(order.status) && !order.rider_id);
  if (activeMode.value === 'monitor') return orders.value.filter((order) => ['assigned_to_rider', 'in_hub', 'out_for_delivery', 'delivered', 'in_return_queue'].includes(order.status) || order.rider_id);
  return orders.value;
});
const filteredOrders = computed(() => modeOrders.value.filter((order) => {
  const query = search.value.toLowerCase().trim();
  const haystack = [order.awb_number, order.recipient_name, order.recipient_address].filter(Boolean).join(' ').toLowerCase();
  return (!query || haystack.includes(query)) && (!statusFilter.value || order.status === statusFilter.value);
}));
const locationGroups = computed(() => {
  const groups = new Map();
  filteredOrders.value.forEach((order) => {
    const parts = String(order.recipient_address || '').split(',').map((part) => part.trim()).filter(Boolean);
    const municipality = parts.at(-2) || 'Municipality review';
    const barangay = parts.at(-3) || 'Barangay review';
    const key = `${municipality} / ${barangay}`;
    groups.set(key, (groups.get(key) || 0) + 1);
  });
  return [...groups.entries()].map(([label, count]) => ({ label, count })).sort((left, right) => right.count - left.count);
});
const metrics = computed(() => ({
  ready: orders.value.filter((order) => ['in_hub', 'received'].includes(order.status)).length,
  assigned: orders.value.filter((order) => order.rider_id).length,
  active: orders.value.filter((order) => order.status === 'out_for_delivery').length,
  failed: orders.value.filter((order) => ['in_return_queue', 'failed'].includes(order.status) || order.delivery_status === 'failed').length,
}));
const toggleOrder = (id) => { selectedIds.value = selectedIds.value.includes(id) ? selectedIds.value.filter((item) => item !== id) : [...selectedIds.value, id]; };
const selectVisible = () => { selectedIds.value = selectedIds.value.length === filteredOrders.value.length ? [] : filteredOrders.value.map((order) => order.id); };
const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const [ordersResponse, ridersResponse, pickupsResponse, hubsResponse] = await Promise.all([
      axios.get('/orders', { params: { per_page: 100 } }),
      axios.get('/riders', { params: { per_page: 100, application_status: 'approved' } }),
      axios.get('/pickup-requests', { params: { per_page: 100 } }),
      axios.get('/hubs'),
    ]);
    orders.value = ordersResponse.data.data || [];
    riders.value = ridersResponse.data.data || [];
    pickups.value = pickupsResponse.data.data || [];
    hubs.value = hubsResponse.data || [];
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Unable to load courier operations.';
  } finally { loading.value = false; }
};
const assignSelected = async () => {
  if (!selectedIds.value.length || !selectedRider.value) return;
  busy.value = true; error.value = ''; notice.value = '';
  try {
    await axios.post('/riders/assign-orders', { rider_id: selectedRider.value, order_ids: selectedIds.value });
    notice.value = `${selectedIds.value.length} parcel(s) assigned to the rider.`;
    selectedIds.value = []; selectedRider.value = '';
    await load();
  } catch (requestError) { error.value = requestError.response?.data?.message || 'Unable to assign parcels.'; }
  finally { busy.value = false; }
};
const receiveBulk = async () => {
  if (!selectedIds.value.length || !receivingHubId.value || !expectedCount.value) return;
  busy.value = true; error.value = ''; notice.value = '';
  try {
    const response = await axios.post('/orders/receive-bulk', { order_ids: selectedIds.value, hub_id: receivingHubId.value, expected_count: Number(expectedCount.value) });
    notice.value = `${response.data.received_count} parcel(s) received. ${response.data.discrepancy === 0 ? 'Counts match.' : `Discrepancy: ${Math.abs(response.data.discrepancy)} parcel(s).`}`;
    selectedIds.value = []; receivingHubId.value = ''; expectedCount.value = '';
    await load();
  } catch (requestError) { error.value = requestError.response?.data?.message || 'Unable to receive bulk shipment.'; }
  finally { busy.value = false; }
};
const updateStatus = async (order, nextStatus) => {
  busy.value = true; error.value = ''; notice.value = '';
  try {
    const reason = nextStatus === 'delivery_failed' ? window.prompt('Enter the delivery failure reason.') : undefined;
    if (nextStatus === 'delivery_failed' && !reason?.trim()) return;
    await axios.patch(`/orders/${order.id}/operational-status`, { status: nextStatus, reason });
    notice.value = `${order.awb_number} updated.`;
    await load();
  } catch (requestError) { error.value = requestError.response?.data?.message || 'Unable to update parcel status.'; }
  finally { busy.value = false; }
};
const tabClass = (tab) => activeTab.value === tab ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800';
const modeTitle = computed(() => ({ bulk: 'Bulk received', assign: 'Sort and assign', monitor: 'Delivery monitor' }[activeMode.value]));
const modeDescription = computed(() => ({ bulk: 'Review parcels received from Logistics Admin and confirm the courier queue.', assign: 'Review destination groups and assign ready parcels to riders.', monitor: 'Track assigned, active, completed, and returned deliveries.' }[activeMode.value]));
const syncTab = () => {
  activeTab.value = ['overview', 'pickups', 'riders', 'exceptions', 'hubs'].includes(route.query.tab) ? route.query.tab : 'overview';
  activeMode.value = route.query.mode === 'assign' ? 'assign' : route.query.mode === 'monitor' ? 'monitor' : 'bulk';
};
watch(() => route.query.tab, syncTab, { immediate: true });
onMounted(load);
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] px-4 py-6 text-slate-900 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
      <header class="flex flex-wrap items-end justify-between gap-5 border-b border-slate-200 pb-6"><div><p class="text-xs font-black uppercase tracking-[0.24em] text-blue-700">Courier administration</p><h1 class="mt-2 text-3xl font-black tracking-tight">{{ modeTitle }}</h1><p class="mt-2 text-sm text-slate-500">{{ modeDescription }}</p></div><button class="inline-flex items-center gap-2 bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700" @click="load"><RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />Refresh</button></header>
      <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article v-for="item in [{ label: 'Ready for assignment', value: metrics.ready, icon: PackageSearch, tone: 'text-blue-700 bg-blue-50' }, { label: 'Assigned parcels', value: metrics.assigned, icon: Users, tone: 'text-indigo-700 bg-indigo-50' }, { label: 'Out for delivery', value: metrics.active, icon: Truck, tone: 'text-emerald-700 bg-emerald-50' }, { label: 'Exceptions / returns', value: metrics.failed, icon: AlertTriangle, tone: 'text-rose-700 bg-rose-50' }]" :key="item.label" class="border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between"><div><p class="text-sm font-semibold text-slate-500">{{ item.label }}</p><p class="mt-3 text-3xl font-black">{{ item.value }}</p></div><div class="flex h-11 w-11 items-center justify-center" :class="item.tone"><component :is="item.icon" class="h-5 w-5" /></div></div></article></section>
      <nav class="mt-8 flex gap-1 overflow-x-auto border-b border-slate-200"><button v-for="tab in [{ id: 'overview', mode: 'bulk', label: 'Bulk received' }, { id: 'overview', mode: 'assign', label: 'Sort & assign' }, { id: 'overview', mode: 'monitor', label: 'Delivery monitor' }, { id: 'riders', label: 'Riders' }, { id: 'exceptions', label: 'Exceptions' }]" :key="`${tab.id}-${tab.mode || ''}`" class="shrink-0 border-b-2 px-4 py-3 text-sm font-bold" :class="activeTab === tab.id && (!tab.mode || activeMode === tab.mode) ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800'" @click="activeTab = tab.id; activeMode = tab.mode || activeMode">{{ tab.label }}</button></nav>
      <div v-if="notice" class="mt-5 flex items-center gap-2 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800"><CheckCircle2 class="h-4 w-4" />{{ notice }}</div><div v-if="error" class="mt-5 border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800">{{ error }}</div>
      <section v-if="activeTab === 'overview' && activeMode === 'bulk'" class="mt-5 grid gap-3 border border-blue-100 bg-blue-50 p-4 md:grid-cols-[1fr_1fr_1.2fr_auto]"><label class="text-xs font-black uppercase tracking-wider text-blue-800">Receiving hub<select v-model="receivingHubId" class="mt-2 w-full border border-blue-200 bg-white px-3 py-2 text-sm"><option value="">Select hub</option><option v-for="hub in hubs" :key="hub.id" :value="hub.id">{{ hub.name }}</option></select></label><label class="text-xs font-black uppercase tracking-wider text-blue-800">Expected count<input v-model="expectedCount" type="number" min="1" class="mt-2 w-full border border-blue-200 bg-white px-3 py-2 text-sm" placeholder="e.g. 25" /></label><div class="flex items-end text-xs text-blue-800">Select the parcels in this incoming bulk, then confirm the received batch.</div><button class="inline-flex items-center justify-center gap-2 bg-blue-700 px-4 py-2 text-sm font-bold text-white disabled:opacity-40" :disabled="busy || !selectedIds.length || !receivingHubId || !expectedCount" @click="receiveBulk"><ClipboardList class="h-4 w-4" />Receive bulk</button></section>
      <section v-if="activeTab === 'overview' && activeMode === 'assign'" class="mt-5 grid gap-2 border border-teal-100 bg-teal-50 p-4 sm:grid-cols-2 lg:grid-cols-4"><article v-for="group in locationGroups.slice(0, 8)" :key="group.label" class="border border-teal-100 bg-white px-3 py-2"><p class="text-xs font-black text-teal-800">{{ group.label }}</p><p class="mt-1 text-xs text-slate-500">{{ group.count }} parcel(s) ready</p></article><p v-if="!locationGroups.length" class="text-sm text-slate-600">No ready parcels need sorting or assignment.</p></section>

      <section v-if="activeTab === 'overview'" class="mt-6 overflow-hidden border border-slate-200 bg-white shadow-sm"><header class="grid gap-3 border-b border-slate-200 bg-slate-50 p-4 md:grid-cols-[1.5fr_1fr]"><input v-model="search" class="border border-slate-300 bg-white px-3 py-2 text-sm" placeholder="Search tracking number or recipient" /><select v-model="statusFilter" class="border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">All parcel statuses</option><option v-for="status in statuses" :key="status" :value="status">{{ label(status) }}</option></select></header><div class="overflow-x-auto"><table class="w-full min-w-[1050px] text-left text-sm"><thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3"><input type="checkbox" :checked="filteredOrders.length > 0 && selectedIds.length === filteredOrders.length" @change="selectVisible" /></th><th class="px-5 py-3">Parcel</th><th class="px-5 py-3">Municipality / barangay</th><th class="px-5 py-3">Hub</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Action</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="order in filteredOrders" :key="order.id"><td class="px-5 py-4"><input type="checkbox" :checked="selectedIds.includes(order.id)" @change="toggleOrder(order.id)" /></td><td class="px-5 py-4"><p class="font-mono font-black text-blue-700">{{ order.awb_number }}</p><p class="mt-1 text-xs text-slate-500">{{ order.recipient_name }}</p></td><td class="max-w-xs px-5 py-4 text-xs text-slate-600">{{ order.recipient_address || 'Address needs review' }}</td><td class="px-5 py-4 text-xs font-bold"><Warehouse class="mr-1 inline h-3.5 w-3.5 text-teal-700" />{{ order.current_hub?.name || order.hub?.name || 'Unassigned' }}</td><td class="px-5 py-4"><span class="bg-slate-100 px-2 py-1 text-[11px] font-black capitalize">{{ label(order.status) }}</span></td><td class="px-5 py-4"><select class="border border-slate-300 bg-white px-2 py-1.5 text-xs" :disabled="busy" @change="updateStatus(order, $event.target.value); $event.target.value = ''"><option value="">Update</option><option value="in_hub">At destination hub</option><option value="out_for_delivery">Out for delivery</option><option value="delivered">Delivered</option><option value="delivery_failed">Delivery failed</option></select></td></tr><tr v-if="!loading && !filteredOrders.length"><td colspan="6" class="p-12 text-center text-sm text-slate-500">No parcels found.</td></tr><tr v-if="loading"><td colspan="6" class="p-12 text-center text-sm text-slate-500"><LoaderCircle class="mx-auto mb-2 h-5 w-5 animate-spin" />Loading courier queue...</td></tr></tbody></table></div><footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 p-4"><span class="text-xs font-bold text-slate-500">{{ selectedIds.length }} parcels selected</span><div class="flex gap-2"><select v-model="selectedRider" class="border border-slate-300 bg-white px-3 py-2 text-sm" :disabled="!selectedIds.length"><option value="">Assign rider</option><option v-for="rider in riders.filter((item) => item.status !== 'suspended')" :key="rider.id" :value="rider.id">{{ rider.user?.name }} · {{ rider.hub?.name }}</option></select><button class="inline-flex items-center gap-2 bg-blue-700 px-4 py-2 text-sm font-bold text-white disabled:opacity-40" :disabled="busy || !selectedRider || !selectedIds.length" @click="assignSelected"><Send class="h-4 w-4" />Assign selected</button></div></footer></section>
      <section v-else-if="activeTab === 'pickups'" class="mt-6 border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xl font-black">Pickup requests</h2><div class="mt-4 divide-y divide-slate-100"><article v-for="pickup in pickups" :key="pickup.id" class="flex flex-wrap items-center justify-between gap-3 py-4"><div><p class="font-mono text-xs font-black text-teal-700">{{ pickup.request_code }}</p><p class="mt-1 font-bold">{{ pickup.contact_person }}</p><p class="text-xs text-slate-500">{{ pickup.barangay }}, {{ pickup.city_municipality }} · {{ pickup.estimated_parcels }} parcels</p></div><span class="bg-slate-100 px-2 py-1 text-xs font-black capitalize">{{ label(pickup.status) }}</span></article><p v-if="!pickups.length" class="p-8 text-center text-sm text-slate-500">No pickup requests.</p></div></section>
      <section v-else-if="activeTab === 'riders'" class="mt-6 border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xl font-black">Courier riders</h2><div class="mt-4 grid gap-3 md:grid-cols-2"><article v-for="rider in riders" :key="rider.id" class="border border-slate-200 p-4"><div class="flex items-start justify-between"><div><p class="font-black">{{ rider.user?.name }}</p><p class="mt-1 text-xs text-slate-500">{{ rider.user?.email }}</p></div><span class="bg-slate-100 px-2 py-1 text-xs font-black capitalize">{{ label(rider.status) }}</span></div><p class="mt-3 text-xs text-slate-600">{{ rider.hub?.name || 'No hub' }} · {{ rider.vehicle_type }}</p></article></div></section>
      <section v-else-if="activeTab === 'exceptions'" class="mt-6 border border-rose-200 bg-white p-5 shadow-sm"><h2 class="text-xl font-black">Exceptions and returns</h2><div class="mt-4 divide-y divide-slate-100"><article v-for="order in orders.filter((item) => ['in_return_queue', 'damaged', 'flagged'].includes(item.status) || item.delivery_status === 'failed')" :key="order.id" class="flex flex-wrap items-center justify-between gap-3 py-4"><div><p class="font-mono text-xs font-black text-rose-700">{{ order.awb_number }}</p><p class="mt-1 font-bold">{{ order.recipient_name }}</p><p class="text-xs text-slate-500">{{ order.delivery_failure_reason || 'Requires review' }}</p></div><span class="bg-rose-50 px-2 py-1 text-xs font-black capitalize text-rose-700">{{ label(order.status) }}</span></article><p v-if="!orders.filter((item) => ['in_return_queue', 'damaged', 'flagged'].includes(item.status) || item.delivery_status === 'failed').length" class="p-8 text-center text-sm text-slate-500">No active exceptions.</p></div></section>
      <section v-else class="mt-6 border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xl font-black">Registered hubs</h2><div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3"><article v-for="hub in hubs" :key="hub.id" class="border border-slate-200 p-4"><p class="font-black">{{ hub.name }}</p><p class="mt-1 text-xs text-slate-500">{{ hub.code }}</p><p class="mt-3 text-xs font-bold text-slate-600">Delivery hub for courier operations</p></article></div></section>
    </div>
  </main>
</template>
