<script setup>
import { ref } from 'vue';
import { Check, MapPinned } from 'lucide-vue-next';
import { useAlonaLogisticsStore } from '../../stores/alonaLogisticsStore';

const store = useAlonaLogisticsStore();
const saving = ref(null);
const saveAssignment = async (zone, event) => {
  saving.value = zone.id;
  try { await store.assignZoneRider(zone.id, event.target.value || null); } finally { saving.value = null; }
};
</script>

<template>
  <section id="territories" class="border border-slate-200 bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Coverage design</p><h2 class="mt-1 text-xl font-black">Territory zone map</h2><p class="mt-1 text-sm text-slate-500">Assign a default rider to each delivery area.</p></div><MapPinned class="h-5 w-5 text-teal-700" /></div><div class="mt-5 grid gap-4 md:grid-cols-3"><article v-for="zone in store.zones" :key="zone.id" class="border border-slate-200 p-4 transition hover:border-teal-400"><div class="flex items-start justify-between gap-3"><div><h3 class="font-black">{{ zone.name || zone.zone_name }}</h3><p class="mt-1 text-xs text-slate-500">{{ zone.city_municipality || zone.province || zone.zip_code || 'Alona service area' }}</p></div><span class="h-2.5 w-2.5 bg-emerald-500" /></div><label class="mt-5 block text-xs font-black uppercase tracking-wider text-slate-500">Default rider<select class="mt-2 w-full border border-slate-300 bg-white px-3 py-2 text-sm font-bold outline-none focus:border-teal-600" :value="zone.default_rider_id || ''" :disabled="saving === zone.id" @change="saveAssignment(zone, $event)"><option value="">Unassigned</option><option v-for="rider in store.riders" :key="rider.id" :value="rider.id">{{ rider.full_name }}</option></select></label><p class="mt-3 flex items-center gap-1 text-xs font-semibold text-slate-500"><Check class="h-3.5 w-3.5 text-emerald-600" />{{ zone.default_rider?.full_name || zone.defaultRider?.full_name || 'Ready for assignment' }}</p></article><p v-if="!store.zones.length" class="border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 md:col-span-3">No coverage zones configured.</p></div></section>
</template>