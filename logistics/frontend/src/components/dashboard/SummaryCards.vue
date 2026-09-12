<script setup>
import { computed } from 'vue';
const props = defineProps({ metrics: { type: Object, default: () => ({}) }, loading: Boolean });
const cards = computed(() => [
  { key: 'total_inbound_today', label: 'Inbound today', value: props.metrics.total_inbound_today ?? 0, icon: '↓', tone: 'teal' },
  { key: 'out_for_delivery', label: 'Out for delivery', value: props.metrics.out_for_delivery ?? 0, icon: '→', tone: 'blue' },
  { key: 'hub_capacity', label: 'Average hub capacity', value: `${Number(props.metrics.hub_capacity?.utilization_percentage || 0).toFixed(1)}%`, icon: '◌', tone: 'amber' },
  { key: 'pending_returns', label: 'Pending returns', value: props.metrics.pending_returns ?? 0, icon: '↺', tone: 'rose' },
]);
const trend = (key, value) => { const previous = Number(props.metrics.trends?.[key] || 0); const current = Number(value) || 0; if (!previous) return null; return Math.round((current - previous) / previous * 100); };
</script>

<template>
  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"><article v-for="card in cards" :key="card.key" class="border border-slate-200 bg-white p-5 shadow-sm"><div v-if="loading" class="animate-pulse"><div class="h-3 w-24 bg-slate-200" /><div class="mt-4 h-9 w-20 bg-slate-200" /><div class="mt-4 h-3 w-32 bg-slate-100" /></div><template v-else><div class="flex items-center justify-between"><span class="text-xs font-black uppercase tracking-widest text-slate-500">{{ card.label }}</span><span class="text-xl text-teal-700">{{ card.icon }}</span></div><p class="mt-4 text-4xl font-black tracking-tight">{{ card.value }}</p><p v-if="trend(card.key, card.key === 'hub_capacity' ? card.value.replace('%', '') : card.value) !== null" class="mt-3 text-xs font-bold" :class="trend(card.key, card.key === 'hub_capacity' ? card.value.replace('%', '') : card.value) >= 0 ? 'text-emerald-600' : 'text-red-600'">{{ trend(card.key, card.key === 'hub_capacity' ? card.value.replace('%', '') : card.value) >= 0 ? '+' : '' }}{{ trend(card.key, card.key === 'hub_capacity' ? card.value.replace('%', '') : card.value) }}% vs yesterday</p><p v-else class="mt-3 text-xs text-slate-400">Compared with yesterday</p></template></article></div>
</template>