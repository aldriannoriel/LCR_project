<script setup>
import { onMounted, ref } from 'vue';
import { axios } from '../lib/echo';

const metrics = ref(null);
onMounted(async () => { const token = localStorage.getItem('token'); if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`; metrics.value = (await axios.get('/dashboard/metrics')).data; });
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] text-slate-900"><header class="border-b border-slate-200 bg-white"><div class="mx-auto max-w-7xl px-6 py-6"><p class="text-xs font-black uppercase tracking-widest text-teal-700">Performance intelligence</p><h1 class="mt-1 text-3xl font-black">Reports & analytics</h1></div></header><div class="mx-auto max-w-7xl px-6 py-8"><section class="border border-slate-200 bg-white p-6 shadow-sm"><h2 class="text-xl font-bold">Network summary</h2><div v-if="metrics" class="mt-5 grid gap-4 sm:grid-cols-3"><div class="bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Inbound today</p><p class="mt-2 text-3xl font-black">{{ metrics.total_inbound_today }}</p></div><div class="bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Delivery completion</p><p class="mt-2 text-3xl font-black">{{ Object.values(metrics.regional_volume || {}).reduce((sum, region) => sum + region.completion_rate, 0) / Math.max(1, Object.values(metrics.regional_volume || {}).length) | 0 }}%</p></div><div class="bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Pending returns</p><p class="mt-2 text-3xl font-black">{{ metrics.pending_returns }}</p></div></div><p v-else class="mt-5 text-slate-400">Loading report metrics...</p></section></div></main>
</template>
