<script setup>
import { reactive, ref } from 'vue';
import { useOrderStore } from '../../stores/order';

const props = defineProps({ order: { type: Object, required: true } });
const emit = defineEmits(['close', 'saved']);
const store = useOrderStore();
const saving = ref(false);
const error = ref('');
const form = reactive({ status: 'damaged', reason_code: 'Package Crushed', notes: '' });

const submit = async () => {
  saving.value = true; error.value = '';
  try { await store.flagOrderOverride(props.order.id, form); emit('saved'); } catch (e) { error.value = e.response?.data?.message || 'Override could not be saved.'; } finally { saving.value = false; }
};
</script>

<template>
  <div class="fixed inset-0 z-20 flex items-center justify-center bg-slate-950/50 p-4" @click.self="emit('close')"><form class="w-full max-w-lg bg-white p-6 shadow-2xl" @submit.prevent="submit"><div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-widest text-teal-700">Manual override</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Flag shipment</h2></div><button type="button" class="text-2xl text-slate-400" aria-label="Close" @click="emit('close')">&times;</button></div><label class="mt-6 block text-sm font-semibold text-slate-700">AWB number<input :value="order.awb_number" readonly class="mt-1 w-full bg-slate-100 px-3 py-2 font-mono text-slate-600" /></label><div class="mt-4 grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold text-slate-700">Flag type<select v-model="form.status" class="mt-1 w-full border border-slate-300 px-3 py-2"><option value="damaged">Damaged</option><option value="flagged">Unreadable</option></select></label><label class="text-sm font-semibold text-slate-700">Reason code<select v-model="form.reason_code" class="mt-1 w-full border border-slate-300 px-3 py-2"><option>Package Crushed</option><option>Barcode Unscannable</option><option>Missing Information</option><option>Label Tampered</option></select></label></div><label class="mt-4 block text-sm font-semibold text-slate-700">Additional notes<textarea v-model="form.notes" required rows="4" class="mt-1 w-full border border-slate-300 px-3 py-2" placeholder="Describe the issue..." /></label><p v-if="error" class="mt-3 bg-red-50 px-3 py-2 text-sm text-red-700">{{ error }}</p><div class="mt-6 flex justify-end gap-3"><button type="button" class="px-4 py-2 text-sm font-semibold text-slate-600" @click="emit('close')">Cancel</button><button :disabled="saving" class="bg-slate-950 px-5 py-2 text-sm font-semibold text-white disabled:opacity-50">{{ saving ? 'Saving...' : 'Save override' }}</button></div></form></div>
</template>