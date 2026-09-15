<script setup>
import { computed, ref, watch } from 'vue';
import { CheckCircle2, LoaderCircle, PackageCheck, X } from 'lucide-vue-next';
import { useAlonaLogisticsStore } from '../../stores/alonaLogisticsStore';

const props = defineProps({ manifest: { type: Object, default: null } });
const emit = defineEmits(['close', 'approved']);
const store = useAlonaLogisticsStore();
const detail = ref(null);
const selected = ref([]);
const loading = ref(false);
const approving = ref(false);
const parcels = computed(() => detail.value?.parcels || []);
const allSelected = computed(() => parcels.value.length > 0 && selected.value.length === parcels.value.length);

watch(() => props.manifest, async (manifest) => {
  selected.value = [];
  detail.value = manifest;
  if (!manifest) return;
  loading.value = true;
  try { detail.value = await store.fetchManifestDetails(manifest.id); } finally { loading.value = false; }
}, { immediate: true });

const toggleAll = () => { selected.value = allSelected.value ? [] : parcels.value.map((parcel) => parcel.id); };
const approve = async () => { approving.value = true; try { await store.approveManifest(props.manifest.id); emit('approved'); emit('close'); } finally { approving.value = false; } };
</script>

<template>
  <div v-if="manifest" class="fixed inset-0 z-50"><div class="absolute inset-0 bg-slate-950/45" @click="emit('close')" /><aside class="absolute right-0 top-0 flex h-full w-full max-w-2xl flex-col bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-6 py-5"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Manifest inspector</p><h2 class="mt-1 text-2xl font-black">{{ detail?.manifest_number || detail?.manifest_code || manifest.manifest_number }}</h2><p class="mt-1 text-sm text-slate-500">{{ detail?.seller?.name || 'Seller package manifest' }} · {{ parcels.length }} parcels</p></div><button class="rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900" aria-label="Close inspector" @click="emit('close')"><X class="h-5 w-5" /></button></header><div v-if="loading" class="flex flex-1 items-center justify-center text-sm text-slate-500"><LoaderCircle class="mr-2 h-5 w-5 animate-spin" />Loading parcel details...</div><template v-else><div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-3"><label class="flex items-center gap-3 text-sm font-bold"><input type="checkbox" :checked="allSelected" class="h-4 w-4 accent-teal-700" @change="toggleAll" />Select all parcels</label><span class="text-xs font-bold text-slate-500">{{ selected.length }} selected</span></div><div class="flex-1 overflow-y-auto"><div v-for="parcel in parcels" :key="parcel.id" class="flex items-start gap-3 border-b border-slate-100 px-6 py-4"><input v-model="selected" type="checkbox" :value="parcel.id" class="mt-1 h-4 w-4 accent-teal-700" /><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-mono text-sm font-black">{{ parcel.tracking_number }}</p><span class="bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-600">{{ parcel.status }}</span></div><p class="mt-2 text-sm font-bold">{{ parcel.recipient_name }}</p><p class="mt-1 text-xs text-slate-500">{{ parcel.recipient_address }}</p></div></div><p v-if="!parcels.length" class="p-12 text-center text-sm text-slate-500">This manifest has no itemized parcels.</p></div><footer class="border-t border-slate-200 p-5"><button class="inline-flex w-full items-center justify-center gap-2 bg-teal-700 px-4 py-3 text-sm font-black text-white hover:bg-teal-800 disabled:opacity-50" :disabled="!selected.length || approving" @click="approve"><LoaderCircle v-if="approving" class="h-4 w-4 animate-spin" /><CheckCircle2 v-else class="h-4 w-4" />Approve selected pickups</button></footer></template></aside></div>
</template>