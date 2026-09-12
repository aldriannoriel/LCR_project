<script setup>
import { computed, onMounted, ref } from 'vue';
import { geoCentroid, geoMercator, geoPath } from 'd3-geo';

const props = defineProps({ regions: { type: Object, default: () => ({}) } });
const mapData = ref(null);
const hovered = ref(null);
const loadError = ref(false);
const regionKeys = ['luzon', 'visayas', 'mindanao'];
const regionColors = { luzon: '#2563eb', visayas: '#0f766e', mindanao: '#d97706' };
const regionLabels = { luzon: 'Luzon', visayas: 'Visayas', mindanao: 'Mindanao' };

const features = computed(() => mapData.value?.features || []);
const projection = computed(() => mapData.value ? geoMercator().fitSize([720, 620], mapData.value) : null);
const items = computed(() => regionKeys.map((key) => props.regions[key] || { region: regionLabels[key], active_orders: 0, completion_rate: 0 }));
const max = computed(() => Math.max(1, ...items.value.map((item) => item.active_orders || 0)));
const intensity = (region) => Math.max(0.18, (region.active_orders || 0) / max.value);
const tooltip = computed(() => hovered.value ? props.regions[hovered.value] : null);

const regionKey = (feature) => {
  const [longitude, latitude] = geoCentroid(feature);
  if (latitude >= 13 || (longitude < 121.5 && latitude >= 10)) return 'luzon';
  if (latitude <= 10.5) return 'mindanao';
  return 'visayas';
};

const pathFor = (feature) => projection.value ? geoPath(projection.value)(feature) : null;
const fillFor = (feature) => regionColors[regionKey(feature)];
const opacityFor = (feature) => 0.28 + intensity(props.regions[regionKey(feature)] || {}) * 0.5;

onMounted(async () => {
  try {
    const response = await fetch('/philippines-country.json');
    if (!response.ok) throw new Error('Map data request failed');
    mapData.value = await response.json();
  } catch (_) {
    loadError.value = true;
  }
});
</script>

<template>
  <section class="border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div><p class="text-xs font-black uppercase tracking-widest text-teal-700">National volume</p><h2 class="mt-1 text-xl font-bold">Regional flow map</h2></div>
      <span class="text-xs text-slate-400">Philippines · 3 archipelagos</span>
    </div>
    <div class="relative mx-auto mt-4 max-w-[690px] overflow-hidden border border-slate-100 bg-sky-50/50">
      <svg v-if="mapData" viewBox="0 0 720 620" class="block h-auto w-full" role="img" aria-label="Map of the Philippines divided into Luzon, Visayas, and Mindanao">
        <rect width="720" height="620" fill="#f0f9ff" />
        <path v-for="feature in features" :key="feature.id || pathFor(feature)" :d="pathFor(feature)" :fill="fillFor(feature)" :fill-opacity="opacityFor(feature)" stroke="#ffffff" stroke-width="0.7" @mouseenter="hovered = regionKey(feature)" @mouseleave="hovered = null" />
        <text x="420" y="110" class="map-label">Luzon</text>
        <text x="440" y="305" class="map-label">Visayas</text>
        <text x="430" y="475" class="map-label">Mindanao</text>
      </svg>
      <div v-else-if="loadError" class="flex min-h-[360px] items-center justify-center p-8 text-center text-sm text-slate-500">Map data is unavailable.</div>
      <div v-else class="flex min-h-[360px] items-center justify-center p-8 text-sm text-slate-400">Loading map...</div>
      <div v-if="tooltip" class="pointer-events-none absolute right-2 top-2 w-56 border border-slate-200 bg-white p-4 text-sm shadow-lg"><strong>{{ tooltip.region }}</strong><p class="mt-2 text-slate-600">{{ tooltip.active_orders }} active orders</p><p class="text-slate-600">{{ tooltip.completion_rate }}% delivered</p><p class="mt-1 text-xs text-slate-400">{{ tooltip.destination_hubs?.join(', ') || 'No hubs' }}</p></div>
    </div>
    <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500"><span v-for="(item, index) in items" :key="item.region || regionKeys[index]" class="flex items-center gap-2"><i class="h-3 w-3 rounded-full" :style="{ backgroundColor: regionColors[regionKeys[index]] }" />{{ item.region }} · {{ item.active_orders }}</span></div>
  </section>
</template>

<style scoped>
.map-label { fill: #0f172a; font-size: 18px; font-weight: 800; paint-order: stroke; stroke: white; stroke-width: 5px; stroke-linejoin: round; }
path { cursor: pointer; transition: opacity 160ms ease, filter 160ms ease; }
path:hover { filter: brightness(1.08); }
</style>
