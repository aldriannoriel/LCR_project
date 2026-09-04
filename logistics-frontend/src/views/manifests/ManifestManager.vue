<script setup>
import { computed, onMounted, ref } from 'vue';
import { axios } from '../../lib/echo';
import { useOrderStore } from '../../stores/order';

const store = useOrderStore();
const groups = ref([]);
const selected = ref([]);
const loading = ref(false);
const message = ref('');
const orders = computed(() => groups.value.flatMap((group) => group.orders));
const load = async () => { store.setAuthHeader(); const response = await axios.get('/manifests/ready'); groups.value = Object.values(response.data); };
const toggleAll = (event) => { selected.value = event.target.checked ? orders.value.map((order) => order.id) : []; };
const generate = async () => { if (!selected.value.length) return; const order = orders.value.find((item) => item.id === selected.value[0]); const destination = order.route_plan?.regional_hub_id || order.routePlan?.regional_hub_id; loading.value = true; message.value = ''; try { const response = await axios.post('/manifests/generate', { order_ids: selected.value, destination_hub_id: destination }, { responseType: 'blob' }); const url = URL.createObjectURL(response.data); window.open(url, '_blank'); selected.value = []; await load(); } catch (_) { message.value = 'Manifest could not be generated. Select orders from one destination group.'; } finally { loading.value = false; } };
onMounted(load);
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] text-slate-900"><header class="border-b border-slate-200 bg-white"><div class="mx-auto max-w-7xl px-6 py-6"><p class="text-xs font-black uppercase tracking-[0.22em] text-teal-700">Dispatch operations</p><div class="mt-1 flex flex-wrap items-center justify-between gap-4"><h1 class="text-3xl font-black">Transfer manifests</h1><button :disabled="!selected.length || loading" class="bg-slate-950 px-5 py-3 text-sm font-bold text-white disabled:opacity-40" @click="generate">{{ loading ? 'Generating...' : `Generate manifest (${selected.length})` }}</button></div></div></header><div class="mx-auto max-w-7xl px-6 py-8"><p v-if="message" class="mb-5 bg-red-50 px-4 py-3 text-sm text-red-700">{{ message }}</p><section v-for="group in groups" :key="group.destination_hub.id" class="mb-6 border border-slate-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><p class="text-xs font-bold uppercase tracking-widest text-teal-700">Destination</p><h2 class="text-xl font-bold">{{ group.destination_hub.name }}</h2></div><span class="text-sm font-semibold text-slate-500">{{ group.orders.length }} ready</span></div><div class="overflow-x-auto"><table class="w-full min-w-[700px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="w-12 px-5 py-3"><input type="checkbox" @change="toggleAll" /></th><th class="px-5 py-3">AWB</th><th class="px-5 py-3">Sender</th><th class="px-5 py-3">Recipient</th><th class="px-5 py-3">Origin hub</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="order in group.orders" :key="order.id"><td class="px-5 py-4"><input v-model="selected" type="checkbox" :value="order.id" /></td><td class="px-5 py-4 font-mono font-bold">{{ order.awb_number }}</td><td class="px-5 py-4">{{ order.sender_name }}</td><td class="px-5 py-4">{{ order.recipient_name }}</td><td class="px-5 py-4 text-slate-500">{{ order.hub?.name }}</td></tr></tbody></table></div></section><p v-if="!groups.length" class="border border-dashed border-slate-300 p-12 text-center text-slate-500">No routed orders are ready for dispatch.</p></div></main>
</template>