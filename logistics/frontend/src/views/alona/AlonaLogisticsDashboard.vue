<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Activity, AlertTriangle, LayoutDashboard, MapPinned, PackageCheck, RefreshCw, Truck, Users } from 'lucide-vue-next';
import { useAlonaLogisticsStore } from '../../stores/alonaLogisticsStore';
import AlonaManifestInspectorModal from '../../components/alona/AlonaManifestInspectorModal.vue';
import AlonaRiderApprovalManager from '../../components/alona/AlonaRiderApprovalManager.vue';
import AlonaZoneManager from '../../components/alona/AlonaZoneManager.vue';
import ParcelControlCenter from '../../components/admin/ParcelControlCenter.vue';

const store = useAlonaLogisticsStore();
const activeTab = ref('overview');
const selectedManifest = ref(null);

const countBy = (statuses) => store.parcels.filter((parcel) => statuses.includes(parcel.status)).length;
const metrics = computed(() => [
  { label: 'Total active riders', value: store.riders.filter((rider) => ['ACTIVE', 'active', 'ON_DUTY', 'on_duty'].includes(rider.status)).length, icon: Users, tone: 'text-blue-700 bg-blue-50' },
  { label: 'Pending manifests', value: store.manifests.filter((manifest) => ['PENDING_APPROVAL', 'SUBMITTED'].includes(manifest.status)).length, icon: PackageCheck, tone: 'text-amber-700 bg-amber-50' },
  { label: 'Parcels out for delivery', value: countBy(['OUT_FOR_DELIVERY']), icon: Truck, tone: 'text-emerald-700 bg-emerald-50' },
  { label: 'Failed deliveries', value: countBy(['DELIVERY_FAILED']), icon: AlertTriangle, tone: 'text-rose-700 bg-rose-50' },
]);
const tabs = [
  { id: 'overview', label: 'Operations overview', icon: LayoutDashboard },
  { id: 'parcels', label: 'Parcel control center', icon: PackageCheck },
  { id: 'riders', label: 'Riders & agencies', icon: Users },
  { id: 'zones', label: 'Territory zones', icon: MapPinned },
  { id: 'manifests', label: 'Manifest inspector', icon: PackageCheck },
];

const refresh = async () => {
  try { await store.fetchLogisticsData(); } catch (_) {}
};
const statusLabel = (value) => String(value || '').replaceAll('_', ' ');

onMounted(async () => {
  await refresh();
  await store.initRealtimeSubscriptions();
});
onBeforeUnmount(() => store.stopRealtimeSubscriptions());
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] px-4 py-6 text-slate-900 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
      <header class="flex flex-wrap items-end justify-between gap-5 border-b border-slate-200 pb-6">
        <div><p class="text-xs font-black uppercase tracking-[0.24em] text-teal-700">Alona control room</p><h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">Logistics operations</h1><p class="mt-2 max-w-2xl text-sm text-slate-500">A working command center for fleet readiness, territories, seller manifests, and parcel movement.</p></div>
        <div class="flex items-center gap-3"><span class="flex items-center gap-2 text-xs font-bold text-slate-500"><span class="h-2 w-2 rounded-full" :class="store.realtimeConnected ? 'bg-emerald-500' : 'bg-slate-300'" />{{ store.realtimeConnected ? 'Realtime connected' : 'Realtime unavailable' }}</span><button class="inline-flex items-center gap-2 bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-700" @click="refresh"><RefreshCw class="h-4 w-4" :class="store.loading ? 'animate-spin' : ''" />Refresh</button></div>
      </header>

      <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article v-for="item in metrics" :key="item.label" class="border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold text-slate-500">{{ item.label }}</p><p class="mt-3 text-3xl font-black">{{ item.value }}</p></div><div class="flex h-11 w-11 items-center justify-center" :class="item.tone"><component :is="item.icon" class="h-5 w-5" /></div></div></article></section>

      <nav class="mt-8 flex gap-1 overflow-x-auto border-b border-slate-200" aria-label="Logistics workspace tabs"><button v-for="tab in tabs" :key="tab.id" class="flex shrink-0 items-center gap-2 border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === tab.id ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-900'" @click="activeTab = tab.id"><component :is="tab.icon" class="h-4 w-4" />{{ tab.label }}</button></nav>

      <section v-if="activeTab === 'overview'" class="mt-6 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <article class="border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Approval queue</p><h2 class="mt-1 text-xl font-black">Seller manifests</h2></div><button class="text-sm font-bold text-teal-700 hover:text-teal-900" @click="activeTab = 'manifests'">View all</button></div><div class="mt-5 space-y-3"><button v-for="manifest in store.manifests.slice(0, 5)" :key="manifest.id" class="flex w-full items-center justify-between gap-4 border border-slate-200 p-4 text-left hover:border-teal-400" @click="selectedManifest = manifest"><div><p class="font-mono text-sm font-black">{{ manifest.manifest_number || manifest.manifest_code }}</p><p class="mt-1 text-xs text-slate-500">{{ manifest.seller?.name || 'Seller account' }} · {{ manifest.parcels_count ?? manifest.total_parcels ?? 0 }} parcels</p></div><span class="text-xs font-black uppercase text-amber-700">{{ statusLabel(manifest.status) }}</span></button><p v-if="!store.manifests.length" class="border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500">No manifests are waiting for review.</p></div></article>
        <article class="border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Live pulse</p><h2 class="mt-1 text-xl font-black">Parcel movement</h2></div><Activity class="h-5 w-5 text-teal-700" /></div><div class="mt-5 space-y-4"><div v-for="status in ['AT_SORTING_CENTER', 'OUT_FOR_DELIVERY', 'DELIVERY_FAILED', 'DELIVERED', 'RETURNED']" :key="status" class="flex items-center justify-between border-b border-slate-100 pb-3"><span class="text-sm font-semibold text-slate-600">{{ statusLabel(status) }}</span><span class="font-mono text-lg font-black">{{ countBy([status]) }}</span></div></div></article>
      </section>

      <ParcelControlCenter v-else-if="activeTab === 'parcels'" />

      <section v-else-if="activeTab === 'riders'" class="mt-6"><AlonaRiderApprovalManager /></section>
      <section v-else-if="activeTab === 'zones'" class="mt-6"><AlonaZoneManager /></section>
      <section v-else class="mt-6 border border-slate-200 bg-white shadow-sm"><header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-4"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Seller dispatch</p><h2 class="mt-1 text-xl font-black">Bulk manifest inspector</h2></div><span class="text-sm font-bold text-slate-500">{{ store.manifests.length }} manifests</span></header><div class="divide-y divide-slate-100"><button v-for="manifest in store.manifests" :key="manifest.id" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left hover:bg-teal-50/30" @click="selectedManifest = manifest"><div><p class="font-mono text-sm font-black">{{ manifest.manifest_number || manifest.manifest_code }}</p><p class="mt-1 text-xs text-slate-500">{{ manifest.seller?.name || 'Seller account' }} · {{ manifest.zone?.name || 'Multiple zones' }}</p></div><span class="inline-flex items-center gap-2 text-sm font-bold text-teal-700">Inspect <PackageCheck class="h-4 w-4" /></span></button><p v-if="!store.manifests.length" class="p-12 text-center text-sm text-slate-500">No manifests available.</p></div></section>

      <p v-if="store.error" class="mt-5 border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{{ store.error }}</p>
    </div>
    <AlonaManifestInspectorModal :manifest="selectedManifest" @close="selectedManifest = null" @approved="refresh" />
  </main>
</template>
