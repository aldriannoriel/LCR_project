<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { axios } from '../lib/echo';
import SummaryCards from '../components/dashboard/SummaryCards.vue';
import NationalVolumeMap from '../components/dashboard/NationalVolumeMap.vue';
import ActivityFeed from '../components/dashboard/ActivityFeed.vue';
const metrics = reactive({}); const loading = ref(true); let timer;
const load = async () => { const token = localStorage.getItem('token'); if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`; try { Object.assign(metrics, (await axios.get('/dashboard/metrics')).data); } finally { loading.value = false; } };
onMounted(() => { load(); timer = setInterval(load, 60000); }); onBeforeUnmount(() => clearInterval(timer));
</script>

<template><main class="min-h-screen bg-[#f4f7f6] text-slate-900"><header class="border-b border-slate-200 bg-white"><div class="mx-auto max-w-7xl px-6 py-6"><p class="text-xs font-black uppercase tracking-[0.22em] text-teal-700">Executive overview</p><div class="mt-1 flex flex-wrap items-end justify-between gap-3"><h1 class="text-3xl font-black tracking-tight">Network pulse</h1><p class="text-sm text-slate-500">Updated {{ new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</p></div></div></header><div class="mx-auto max-w-7xl space-y-6 px-6 py-8"><SummaryCards :metrics="metrics" :loading="loading" /><div class="grid gap-6 lg:grid-cols-[1.35fr_0.65fr]"><NationalVolumeMap :regions="metrics.regional_volume" /><ActivityFeed /></div></div></main></template>