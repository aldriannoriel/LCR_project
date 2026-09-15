<script setup>
import { computed, onMounted, ref } from 'vue';
import { CheckCircle2, ChevronRight, FileCheck2, LoaderCircle, MapPinned, Search, ShieldAlert, Trash2, X } from 'lucide-vue-next';
import { useAlonaRiderStore } from '../../stores/alonaRiderStore';
import AlonaNationwideCityPicker from './AlonaNationwideCityPicker.vue';

const store = useAlonaRiderStore();
const activeTab = ref('pending');
const search = ref('');
const documentRider = ref(null);
const rejectionRider = ref(null);
const rejectionReason = ref('');
const assignmentRider = ref(null);
const selectedRegion = ref(null);
const selectedProvince = ref(null);
const selectedCity = ref(null);
const selectedCityCodes = ref([]);
const selectedBarangayCodes = ref([]);
const assignmentLocations = ref([]);
const busy = ref(null);
const notice = ref('');

const pendingFiltered = computed(() => filterRiders(store.pendingRiders));
const approvedFiltered = computed(() => filterRiders(store.approvedRiders));
const selectedCities = computed(() => store.phCities.filter((city) => selectedCityCodes.value.includes(city.code)));
const filterRiders = (riders) => riders.filter((rider) => !search.value || [rider.full_name, rider.email, rider.phone, rider.courier_type].filter(Boolean).join(' ').toLowerCase().includes(search.value.toLowerCase()));
const documentEntries = (rider) => Object.entries(rider?.document_urls || {}).filter(([, url]) => typeof url === 'string' && url.length);
const locationLabel = (location) => location.city_municipality_name || location.province_name || location.region_name;
const locationKey = (location) => location.psgc_code;

const run = async (key, action) => { busy.value = key; notice.value = ''; try { await action(); notice.value = 'Rider record updated.'; } catch (error) { notice.value = error.message || 'The requested action could not be completed.'; } finally { busy.value = null; } };
const approve = (rider) => run(`approve-${rider.id}`, async () => { await store.approveRider(rider.id); });
const reject = (rider) => run(`reject-${rider.id}`, async () => { await store.rejectRider(rider.id, rejectionReason.value); rejectionReason.value = ''; rejectionRider.value = null; });

const openAssignment = async (rider) => {
  assignmentRider.value = rider;
  assignmentLocations.value = [...(rider.locations || [])];
  selectedRegion.value = null;
  selectedProvince.value = null;
  selectedCity.value = null;
  selectedCityCodes.value = [];
  selectedBarangayCodes.value = [];
  if (!store.phRegions.length) await store.loadRegions();
};
const chooseRegion = async (region) => { selectedRegion.value = region; selectedProvince.value = null; selectedCityCodes.value = []; await store.loadProvinces(region); };
const chooseProvince = async (province) => { selectedProvince.value = province; selectedCity.value = null; selectedCityCodes.value = []; selectedBarangayCodes.value = []; await store.loadCities(province); };
const chooseCity = async (city) => { selectedCity.value = city; selectedBarangayCodes.value = []; await store.loadBarangays(city); };
const toggleCity = (city) => { selectedCityCodes.value = selectedCityCodes.value.includes(city.code) ? selectedCityCodes.value.filter((code) => code !== city.code) : [...selectedCityCodes.value, city.code]; };
const toggleBarangay = (barangay) => { selectedBarangayCodes.value = selectedBarangayCodes.value.includes(barangay.code) ? selectedBarangayCodes.value.filter((code) => code !== barangay.code) : [...selectedBarangayCodes.value, barangay.code]; };
const selectAllCities = () => { selectedCityCodes.value = selectedCityCodes.value.length === store.phCities.length ? [] : store.phCities.map((city) => city.code); };
const addSelectedCities = () => {
  if (!selectedRegion.value || !selectedProvince.value) return;
  const additions = selectedCities.value.map((city) => ({ region_code: selectedRegion.value.code, region_name: selectedRegion.value.name, province_code: selectedProvince.value.code, province_name: selectedProvince.value.name, city_municipality_code: city.code, city_municipality_name: city.name, psgc_code: city.code }));
  const existing = new Set(assignmentLocations.value.map(locationKey));
  assignmentLocations.value = [...assignmentLocations.value, ...additions.filter((location) => !existing.has(locationKey(location)))];
  selectedCityCodes.value = [];
};
const addProvince = () => {
  if (!selectedRegion.value || !selectedProvince.value) return;
  const location = { region_code: selectedRegion.value.code, region_name: selectedRegion.value.name, province_code: selectedProvince.value.code, province_name: selectedProvince.value.name, city_municipality_code: null, city_municipality_name: null, psgc_code: selectedProvince.value.code };
  if (!assignmentLocations.value.some((item) => item.psgc_code === location.psgc_code)) assignmentLocations.value.push(location);
};
const addRegion = () => {
  if (!selectedRegion.value) return;
  const location = { region_code: selectedRegion.value.code, region_name: selectedRegion.value.name, province_code: null, province_name: null, city_municipality_code: null, city_municipality_name: null, psgc_code: selectedRegion.value.code };
  if (!assignmentLocations.value.some((item) => item.psgc_code === location.psgc_code)) assignmentLocations.value.push(location);
};
const removeLocation = (location) => { assignmentLocations.value = assignmentLocations.value.filter((item) => item.psgc_code !== location.psgc_code); };
const saveLocations = () => run(`locations-${assignmentRider.value.id}`, async () => { await store.saveAssignedLocations(assignmentRider.value.id, assignmentLocations.value); assignmentRider.value.locations = [...assignmentLocations.value]; });

onMounted(async () => { await Promise.all([store.fetchRidersByStatus('PENDING'), store.fetchRidersByStatus('APPROVED')]); });
</script>

<template>
  <section class="overflow-hidden border border-slate-200 bg-white shadow-sm">
    <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-5 py-4"><div><p class="text-[11px] font-black uppercase tracking-[0.2em] text-teal-700">Rider onboarding</p><h2 class="mt-1 text-xl font-black">Applications & coverage directory</h2><p class="mt-1 text-sm text-slate-500">Review credentials first, then assign approved riders anywhere in the Philippines.</p></div><label class="relative w-full sm:w-64"><Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" /><input v-model="search" class="w-full border border-slate-300 py-2 pl-9 pr-3 text-sm" placeholder="Search rider" /></label></header>
    <nav class="flex gap-1 border-b border-slate-200 px-5"><button class="border-b-2 px-4 py-3 text-sm font-black" :class="activeTab === 'pending' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500'" @click="activeTab = 'pending'">Pending applications ({{ store.pendingRiders.length }})</button><button class="border-b-2 px-4 py-3 text-sm font-black" :class="activeTab === 'approved' ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500'" @click="activeTab = 'approved'">Approved riders ({{ store.approvedRiders.length }})</button><span v-if="notice" class="ml-auto self-center text-xs font-bold text-teal-700">{{ notice }}</span></nav>

    <div v-if="activeTab === 'pending'" class="overflow-x-auto"><table class="w-full min-w-[960px] text-left text-sm"><thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Applicant</th><th class="px-5 py-3">Courier type</th><th class="px-5 py-3">License</th><th class="px-5 py-3">Documents</th><th class="px-5 py-3 text-right">Review</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="rider in pendingFiltered" :key="rider.id" class="hover:bg-teal-50/30"><td class="px-5 py-4"><p class="font-bold">{{ rider.full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ rider.email }} · {{ rider.phone }}</p></td><td class="px-5 py-4"><span class="bg-slate-100 px-2 py-1 text-xs font-black">{{ rider.courier_type }}</span></td><td class="px-5 py-4 font-mono text-xs">{{ rider.license_number }}</td><td class="px-5 py-4"><button class="inline-flex items-center gap-1 text-xs font-bold text-teal-700" @click="documentRider = rider"><FileCheck2 class="h-4 w-4" />{{ documentEntries(rider).length }} documents</button></td><td class="px-5 py-4 text-right"><button class="mr-2 inline-flex items-center gap-1 bg-emerald-700 px-3 py-2 text-xs font-bold text-white disabled:opacity-50" :disabled="busy === `approve-${rider.id}`" @click="approve(rider)"><CheckCircle2 class="h-4 w-4" />Approve</button><button class="inline-flex items-center gap-1 bg-rose-700 px-3 py-2 text-xs font-bold text-white" @click="rejectionRider = rider"><ShieldAlert class="h-4 w-4" />Reject</button></td></tr><tr v-if="!pendingFiltered.length"><td colspan="5" class="p-12 text-center text-sm text-slate-500">No pending rider applications.</td></tr></tbody></table></div>

    <div v-else class="overflow-x-auto"><table class="w-full min-w-[980px] text-left text-sm"><thead class="bg-slate-50 text-[11px] font-black uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3">Rider</th><th class="px-5 py-3">Courier type</th><th class="px-5 py-3">Coverage</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Action</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="rider in approvedFiltered" :key="rider.id"><td class="px-5 py-4"><p class="font-bold">{{ rider.full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ rider.email }} · {{ rider.phone }}</p></td><td class="px-5 py-4 font-bold">{{ rider.courier_type }}</td><td class="max-w-sm px-5 py-4"><div class="flex flex-wrap gap-1"><span v-for="location in rider.locations || []" :key="location.psgc_code" class="bg-teal-50 px-2 py-1 text-[11px] font-bold text-teal-800">{{ locationLabel(location) }}</span><span v-if="!rider.locations?.length" class="text-xs text-slate-500">No coverage assigned</span></div></td><td class="px-5 py-4"><span class="bg-emerald-100 px-2 py-1 text-xs font-black text-emerald-800">{{ rider.status }}</span></td><td class="px-5 py-4 text-right"><button class="inline-flex items-center gap-1 bg-slate-950 px-3 py-2 text-xs font-bold text-white hover:bg-teal-700" @click="openAssignment(rider)"><MapPinned class="h-4 w-4" />Assign coverage</button></td></tr><tr v-if="!approvedFiltered.length"><td colspan="5" class="p-12 text-center text-sm text-slate-500">No approved riders found.</td></tr></tbody></table></div>

    <div v-if="documentRider" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="documentRider = null"><article class="w-full max-w-xl bg-white p-6 shadow-2xl"><header class="flex justify-between"><div><p class="text-xs font-black uppercase tracking-widest text-teal-700">Verification documents</p><h3 class="mt-1 text-xl font-black">{{ documentRider.full_name }}</h3></div><button aria-label="Close documents" @click="documentRider = null"><X class="h-5 w-5" /></button></header><div class="mt-5 grid gap-4 sm:grid-cols-2"><a v-for="[type, url] in documentEntries(documentRider)" :key="type" :href="url" target="_blank" rel="noreferrer" class="border border-slate-200 p-4 hover:border-teal-500"><FileCheck2 class="h-5 w-5 text-teal-700" /><p class="mt-3 text-sm font-black uppercase">{{ type.replaceAll('_', ' ') }}</p><p class="mt-1 text-xs font-bold text-teal-700">Open document</p></a><p v-if="!documentEntries(documentRider).length" class="col-span-2 border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No uploaded documents.</p></div></article></div>

    <div v-if="rejectionRider" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="rejectionRider = null"><form class="w-full max-w-md bg-white p-6 shadow-2xl" @submit.prevent="reject(rejectionRider)"><div class="flex justify-between"><div><p class="text-xs font-black uppercase tracking-widest text-rose-700">Reject application</p><h3 class="mt-1 text-xl font-black">{{ rejectionRider.full_name }}</h3></div><button type="button" aria-label="Close rejection dialog" @click="rejectionRider = null"><X class="h-5 w-5" /></button></div><label class="mt-5 block text-sm font-bold">Feedback reason<textarea v-model="rejectionReason" required rows="4" class="mt-2 w-full border border-slate-300 p-3 text-sm" placeholder="Explain what the rider needs to correct" /></label><button class="mt-4 w-full bg-rose-700 px-4 py-3 text-sm font-black text-white">Reject rider application</button></form></div>

    <div v-if="assignmentRider" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="assignmentRider = null"><article class="flex max-h-[90vh] w-full max-w-5xl flex-col bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 p-6"><div><p class="text-xs font-black uppercase tracking-widest text-teal-700">Nationwide coverage</p><h3 class="mt-1 text-xl font-black">Assign areas to {{ assignmentRider.full_name }}</h3><p class="mt-1 text-sm text-slate-500">Use a region, province, or individual cities and municipalities.</p></div><button aria-label="Close coverage picker" @click="assignmentRider = null"><X class="h-5 w-5" /></button></header><div class="grid flex-1 gap-6 overflow-y-auto p-6 lg:grid-cols-[0.8fr_1fr]">
      <div><div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1"><label class="text-xs font-black uppercase tracking-wider text-slate-500">Region<select class="mt-2 w-full border border-slate-300 p-3 text-sm" :value="selectedRegion?.code || ''" @change="chooseRegion(store.phRegions.find((region) => region.code === $event.target.value))"><option value="">Select region</option><option v-for="region in store.phRegions" :key="region.code" :value="region.code">{{ region.name }}</option></select></label><label class="text-xs font-black uppercase tracking-wider text-slate-500">Province<select class="mt-2 w-full border border-slate-300 p-3 text-sm" :value="selectedProvince?.code || ''" :disabled="!selectedRegion" @change="chooseProvince(store.phProvinces.find((province) => province.code === $event.target.value))"><option value="">Select province</option><option v-for="province in store.phProvinces" :key="province.code" :value="province.code">{{ province.name }}</option></select></label><div class="flex gap-2 lg:flex-col"><button class="flex-1 bg-slate-100 px-3 py-2 text-xs font-black text-slate-700" :disabled="!selectedRegion" @click="addRegion">Add entire region</button><button class="flex-1 bg-slate-100 px-3 py-2 text-xs font-black text-slate-700" :disabled="!selectedProvince" @click="addProvince">Add entire province</button></div></div><div class="mt-5 border border-slate-200"><div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 p-3"><p class="text-xs font-black uppercase tracking-wider">Cities / municipalities</p><button class="text-xs font-bold text-teal-700" :disabled="!store.phCities.length" @click="selectAllCities">{{ selectedCityCodes.length === store.phCities.length ? 'Clear all' : 'Select all cities' }}</button></div><div class="max-h-64 overflow-y-auto p-3"><label v-for="city in store.phCities" :key="city.code" class="flex items-center gap-3 border-b border-slate-100 px-2 py-2 text-sm"><input type="checkbox" :checked="selectedCityCodes.includes(city.code)" class="h-4 w-4 accent-teal-700" @change="toggleCity(city)" />{{ city.name }}</label><p v-if="!store.phCities.length" class="p-6 text-center text-xs text-slate-500">Choose a province to load cities.</p></div><button class="w-full border-t border-slate-200 bg-teal-700 px-4 py-3 text-xs font-black text-white disabled:opacity-40" :disabled="!selectedCities.length" @click="addSelectedCities">Add selected cities</button></div></div>
      <div><div class="flex items-center justify-between"><p class="text-xs font-black uppercase tracking-wider text-slate-500">Active coverage</p><span class="text-xs font-bold text-slate-500">{{ assignmentLocations.length }} assignments</span></div><div class="mt-3 space-y-2"><div v-for="location in assignmentLocations" :key="location.psgc_code" class="flex items-center justify-between gap-3 border border-slate-200 p-3"><div><p class="text-sm font-bold">{{ locationLabel(location) }}</p><p class="mt-1 text-xs text-slate-500">{{ location.region_name }}{{ location.province_name ? ` · ${location.province_name}` : '' }}</p></div><button class="text-rose-700" aria-label="Remove coverage" @click="removeLocation(location)"><Trash2 class="h-4 w-4" /></button></div><p v-if="!assignmentLocations.length" class="border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No coverage assigned yet.</p></div></div>
    </div><footer class="flex justify-end gap-3 border-t border-slate-200 p-5"><button class="px-4 py-2 text-sm font-bold text-slate-600" @click="assignmentRider = null">Cancel</button><button class="inline-flex items-center gap-2 bg-slate-950 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50" :disabled="busy === `locations-${assignmentRider.id}`" @click="saveLocations"><LoaderCircle v-if="busy === `locations-${assignmentRider.id}`" class="h-4 w-4 animate-spin" />Save coverage</button></footer></article></div>
    <AlonaNationwideCityPicker v-if="assignmentRider" :rider="assignmentRider" @close="assignmentRider = null" />
  </section>
</template>
