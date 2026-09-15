<script setup>
import { computed, onMounted, ref } from 'vue';
import { Check, LoaderCircle, Search, X } from 'lucide-vue-next';
import { useAlonaRiderStore } from '../../stores/alonaRiderStore';

const props = defineProps({ rider: { type: Object, required: true } });
const emit = defineEmits(['close', 'saved']);
const store = useAlonaRiderStore();
const search = ref('');
const selectedCodes = ref([]);
const loading = ref(false);
const saving = ref(false);
const visibleCities = computed(() => store.phAllCities.filter((city) => !search.value || city.name.toLowerCase().includes(search.value.toLowerCase())));
const selectedCount = computed(() => selectedCodes.value.length);

const toggleCity = (city) => { selectedCodes.value = selectedCodes.value.includes(city.code) ? selectedCodes.value.filter((code) => code !== city.code) : [...selectedCodes.value, city.code]; };
const selectVisible = () => {
  const visibleCodes = visibleCities.value.map((city) => city.code);
  selectedCodes.value = visibleCodes.every((code) => selectedCodes.value.includes(code))
    ? selectedCodes.value.filter((code) => !visibleCodes.includes(code))
    : [...new Set([...selectedCodes.value, ...visibleCodes])];
};
const save = async () => {
  saving.value = true;
  try {
    const locations = store.phAllCities.filter((city) => selectedCodes.value.includes(city.code)).map((city) => ({
      region_code: city.region_code || city.region?.code || 'PH',
      region_name: city.region_name || city.region?.name || 'Philippines',
      province_code: city.province_code || city.province?.code || null,
      province_name: city.province_name || city.province?.name || null,
      city_municipality_code: city.code,
      city_municipality_name: city.name,
      psgc_code: city.code,
    }));
    await store.saveAssignedLocations(props.rider.id, locations);
    props.rider.locations = locations;
    emit('saved');
    emit('close');
  } finally { saving.value = false; }
};

onMounted(async () => { loading.value = true; try { await store.loadAllCities(); } finally { loading.value = false; } });
</script>

<template>
  <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/55 p-4" @click.self="emit('close')"><article class="flex max-h-[88vh] w-full max-w-2xl flex-col bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 p-5"><div><p class="text-xs font-black uppercase tracking-widest text-teal-700">Nationwide city assignment</p><h3 class="mt-1 text-xl font-black">All Philippine cities & municipalities</h3><p class="mt-1 text-sm text-slate-500">Assign any number of cities to {{ rider.full_name }}.</p></div><button aria-label="Close nationwide city picker" @click="emit('close')"><X class="h-5 w-5" /></button></header><div class="border-b border-slate-200 bg-slate-50 p-4"><div class="flex gap-3"><label class="relative flex-1"><Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" /><input v-model="search" class="w-full border border-slate-300 py-2 pl-9 pr-3 text-sm" placeholder="Search any city or municipality" /></label><button class="border border-slate-300 bg-white px-3 text-xs font-black" @click="selectVisible">Select visible</button></div><p class="mt-2 text-xs font-bold text-slate-500">{{ selectedCount }} cities selected · {{ store.phAllCities.length }} total locations</p></div><div class="flex-1 overflow-y-auto p-4"><div v-if="loading" class="flex items-center justify-center p-12 text-sm text-slate-500"><LoaderCircle class="mr-2 h-5 w-5 animate-spin" />Loading PSGC city directory...</div><div v-else class="grid gap-2 sm:grid-cols-2"><label v-for="city in visibleCities" :key="city.code" class="flex items-center gap-3 border border-slate-200 p-3 text-sm hover:border-teal-400"><input type="checkbox" :checked="selectedCodes.includes(city.code)" class="h-4 w-4 accent-teal-700" @change="toggleCity(city)" /><span class="font-semibold">{{ city.name }}</span></label><p v-if="!visibleCities.length" class="col-span-2 p-10 text-center text-sm text-slate-500">No city or municipality matches the search.</p></div></div><footer class="flex justify-end gap-3 border-t border-slate-200 p-4"><button class="px-4 py-2 text-sm font-bold text-slate-600" @click="emit('close')">Cancel</button><button class="inline-flex items-center gap-2 bg-teal-700 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50" :disabled="saving || loading" @click="save"><LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" /><Check v-else class="h-4 w-4" />Save city coverage</button></footer></article></div>
</template>