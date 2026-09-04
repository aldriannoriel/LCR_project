<script setup>
import { computed, onMounted, ref } from 'vue';
import { axios } from '../../lib/echo';
import { useRiderStore } from '../../stores/rider';

const props = defineProps({ rider: { type: Object, required: true } });
const emit = defineEmits(['close', 'assigned']);
const store = useRiderStore();
const orders = ref([]); const selected = ref([]); const error = ref(''); const saving = ref(false);
const limit = { motorcycle: 30, tricycle: 50, van: 100, truck: 300 };
const capacity = computed(() => limit[props.rider.vehicle_type] || 30);
const selectedWeight = computed(() => selected.value.reduce((sum, id) => sum + Number(orders.value.find((order) => order.id === id)?.weight_kg || 0), 0));
const load = async () => { store.auth(); orders.value = (await axios.get('/orders', { params: { hub_id: props.rider.hub_id, per_page: 100 } })).data.data.filter((order) => ['received', 'in_transit'].includes(order.status) && !order.rider_id); };
const submit = async () => { saving.value = true; error.value = ''; try { await store.assignOrders({ rider_id: props.rider.id, order_ids: selected.value }); emit('assigned'); } catch (e) { error.value = e.response?.data?.message || 'Assignments could not be saved.'; } finally { saving.value = false; } };
onMounted(load);
</script>

<template>
  <div class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/50 p-4" @click.self="emit('close')"><section class="w-full max-w-3xl bg-white shadow-2xl"><header class="flex justify-between border-b border-slate-200 p-6"><div><p class="text-xs font-black uppercase tracking-widest text-teal-700">Dispatch planning</p><h2 class="mt-1 text-2xl font-black">Assign deliveries to {{ rider.user?.name }}</h2></div><button class="text-2xl text-slate-400" aria-label="Close" @click="emit('close')">&times;</button></header><div class="grid gap-6 p-6 md:grid-cols-[1fr_220px]"><div class="max-h-80 overflow-y-auto border border-slate-200"><label v-for="order in orders" :key="order.id" class="flex cursor-pointer items-center gap-3 border-b border-slate-100 p-3 hover:bg-teal-50"><input v-model="selected" type="checkbox" :value="order.id" :disabled="selected.length >= capacity && !selected.includes(order.id)" /><span class="flex-1"><strong class="font-mono">{{ order.awb_number }}</strong><small class="block text-slate-500">{{ order.recipient_name }} · {{ order.weight_kg }} kg</small></span></label><p v-if="!orders.length" class="p-6 text-sm text-slate-400">No received or sorted orders at this hub.</p></div><div class="bg-slate-50 p-5"><p class="text-xs font-bold uppercase tracking-widest text-slate-500">Vehicle capacity</p><p class="mt-2 text-3xl font-black" :class="selected.length > capacity ? 'text-red-600' : 'text-slate-950'">{{ selected.length }} / {{ capacity }}</p><p class="mt-2 text-sm text-slate-500">{{ rider.vehicle_type }} · {{ selectedWeight.toFixed(2) }} kg selected</p><div class="mt-5 h-2 bg-slate-200"><div class="h-2 bg-teal-500" :style="{ width: `${Math.min(100, selected.length / capacity * 100)}%` }" /></div></div></div><p v-if="error" class="mx-6 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p><footer class="flex justify-end gap-3 border-t border-slate-200 p-6"><button class="px-4 py-2 text-sm font-bold text-slate-600" @click="emit('close')">Cancel</button><button :disabled="!selected.length || selected.length > capacity || saving" class="bg-slate-950 px-5 py-2 text-sm font-bold text-white disabled:opacity-40" @click="submit">{{ saving ? 'Assigning...' : 'Assign deliveries' }}</button></footer></section></div>
</template>