<script setup>
import { onMounted, ref } from 'vue';
import { CheckCircle2, LoaderCircle, MapPinned, X } from 'lucide-vue-next';
import { axios } from '../../lib/echo';

const props = defineProps({ rider: { type: Object, required: true } });
const emit = defineEmits(['close', 'saved']);
const hubs = ref([]);
const selectedHubId = ref(props.rider.hub_id || '');
const loading = ref(false);
const saving = ref(false);
const error = ref('');

const save = async () => {
  if (!selectedHubId.value) {
    error.value = 'Select a registered hub before saving.';
    return;
  }
  saving.value = true;
  error.value = '';
  try {
    const response = await axios.put(`/riders/${props.rider.id}/hub`, { hub_id: selectedHubId.value });
    emit('saved', response.data.rider);
    emit('close');
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Unable to update the rider hub.';
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  loading.value = true;
  try {
    hubs.value = (await axios.get('/hubs')).data;
  } catch (_) {
    error.value = 'Unable to load registered hubs.';
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @click.self="emit('close')">
    <article class="w-full max-w-xl overflow-hidden rounded-[28px] bg-white shadow-[0_30px_80px_rgba(15,23,42,0.25)]">
      <header class="flex items-start justify-between border-b border-slate-200 p-6">
        <div><p class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.2em] text-blue-700"><MapPinned class="h-4 w-4" /> Rider hub assignment</p><h2 class="mt-2 text-2xl font-black text-slate-900">Change hub for {{ rider.user?.name }}</h2><p class="mt-1 text-sm text-slate-500">A rider can be assigned to one registered hub at a time.</p></div>
        <button aria-label="Close hub assignment" class="text-slate-400 hover:text-slate-700" @click="emit('close')"><X class="h-6 w-6" /></button>
      </header>
      <div class="p-6"><label class="block text-xs font-black uppercase tracking-wider text-slate-500">Registered hub<select v-model="selectedHubId" class="mt-2 w-full rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-800" :disabled="loading"><option value="">Select registered hub</option><option v-for="hub in hubs" :key="hub.id" :value="hub.id">{{ hub.name }}{{ hub.code ? ` (${hub.code})` : '' }}</option></select></label><p v-if="!hubs.length && !loading" class="mt-4 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">No registered hubs found.</p></div>
      <p v-if="error" class="border-t border-rose-200 bg-rose-50 px-6 py-3 text-sm font-semibold text-rose-700">{{ error }}</p>
      <footer class="flex justify-end gap-3 border-t border-slate-200 p-5"><button class="rounded-xl px-4 py-2 text-sm font-bold text-slate-600 hover:bg-slate-50" @click="emit('close')">Cancel</button><button class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50" :disabled="saving || loading" @click="save"><LoaderCircle v-if="saving" class="h-4 w-4 animate-spin" /><CheckCircle2 v-else class="h-4 w-4" />{{ saving ? 'Saving...' : 'Save hub assignment' }}</button></footer>
    </article>
  </div>
</template>
