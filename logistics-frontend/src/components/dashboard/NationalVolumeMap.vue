<script setup>
import { computed, ref } from 'vue';

const props = defineProps({ regions: { type: Object, default: () => ({}) } });
const hovered = ref(null);
const items = computed(() => Object.values(props.regions));
const max = computed(() => Math.max(1, ...items.value.map((item) => item.active_orders || 0)));
const intensity = (region) => Math.max(0.18, (region.active_orders || 0) / max.value);
const tooltip = computed(() => hovered.value ? props.regions[hovered.value] : null);
</script>

<template>
  <section class="border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-start justify-between gap-4">
      <div><p class="text-xs font-black uppercase tracking-widest text-teal-700">National volume</p><h2 class="mt-1 text-xl font-bold">Regional flow map</h2></div>
      <span class="text-xs text-slate-400">Philippines · 3 archipelagos</span>
    </div>
    <div class="relative mx-auto mt-4 max-w-[690px]">
      <img src="/phmap.webp" alt="Map of the Philippines divided into Luzon, Visayas, and Mindanao" class="block h-auto w-full" />
      <button aria-label="Luzon order volume" class="map-hotspot left-[40%] top-[7%] h-[39%] w-[20%]" :style="{ '--heat': intensity(regions.luzon || {}) }" @mouseenter="hovered = 'luzon'" @mouseleave="hovered = null" @focus="hovered = 'luzon'" @blur="hovered = null" />
      <button aria-label="Visayas order volume" class="map-hotspot left-[48%] top-[43%] h-[23%] w-[23%]" :style="{ '--heat': intensity(regions.visayas || {}) }" @mouseenter="hovered = 'visayas'" @mouseleave="hovered = null" @focus="hovered = 'visayas'" @blur="hovered = null" />
      <button aria-label="Mindanao order volume" class="map-hotspot left-[48%] top-[65%] h-[30%] w-[27%]" :style="{ '--heat': intensity(regions.mindanao || {}) }" @mouseenter="hovered = 'mindanao'" @mouseleave="hovered = null" @focus="hovered = 'mindanao'" @blur="hovered = null" />
      <div v-if="tooltip" class="pointer-events-none absolute right-2 top-2 w-56 border border-slate-200 bg-white p-4 text-sm shadow-lg"><strong>{{ tooltip.region }}</strong><p class="mt-2 text-slate-600">{{ tooltip.active_orders }} active orders</p><p class="text-slate-600">{{ tooltip.completion_rate }}% delivered</p><p class="mt-1 text-xs text-slate-400">{{ tooltip.destination_hubs?.join(', ') || 'No hubs' }}</p></div>
    </div>
    <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500"><span v-for="item in items" :key="item.region" class="flex items-center gap-2"><i class="h-3 w-3 rounded-full" :style="{ backgroundColor: `rgba(15, 53, 92, ${intensity(item)})` }" />{{ item.region }} · {{ item.active_orders }}</span></div>
  </section>
</template>

<style scoped>
.map-hotspot { position: absolute; border: 0; background: transparent; padding: 0; }
.map-hotspot:hover { background: transparent; }
.map-hotspot:focus-visible { outline: 2px solid rgba(15, 53, 92, 0.7); outline-offset: 2px; }
</style>
