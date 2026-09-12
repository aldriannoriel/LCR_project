<script setup>
import { axios } from '../../lib/echo';
const props = defineProps({ filters: { type: Object, required: true } });
const exportReport = async (path) => { const token = localStorage.getItem('token'); if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`; const response = await axios.get(path, { params: props.filters, responseType: 'blob' }); const url = URL.createObjectURL(response.data); window.open(url, '_blank'); };
</script>

<template><div class="flex flex-wrap gap-2"><button class="border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:border-teal-500" @click="exportReport('/reports/export/csv')">Export CSV</button><button class="bg-slate-950 px-4 py-2 text-sm font-bold text-white hover:bg-teal-800" @click="exportReport('/reports/export/pdf')">Export PDF</button></div></template>
