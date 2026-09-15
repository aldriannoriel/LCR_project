<script setup>
import { computed, onMounted, ref } from 'vue';
import { AlertTriangle, CheckCircle2, MessageSquare, Plus, Send, X } from 'lucide-vue-next';
import { axios } from '../../lib/echo';

const disputes = ref([]);
const selected = ref(null);
const messages = ref([]);
const loading = ref(false);
const error = ref('');
const message = ref('');
const showCreate = ref(false);
const form = ref({ parcel_id: '', category: 'DELIVERY_FAILED', priority: 'NORMAL', description: '' });
const roles = computed(() => JSON.parse(localStorage.getItem('user_roles') || '[]'));
const isAdmin = computed(() => roles.value.some((role) => ['admin', 'super_admin', 'logistics_admin', 'Admin', 'Super Admin', 'Logistics Admin'].includes(role)));

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    disputes.value = (await axios.get('/alona/disputes')).data.data || [];
    if (selected.value) {
      const refreshed = disputes.value.find((item) => item.id === selected.value.id);
      if (refreshed) await openDispute(refreshed);
    }
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Unable to load disputes.';
  } finally {
    loading.value = false;
  }
};

const openDispute = async (dispute) => {
  selected.value = dispute;
  messages.value = (await axios.get(`/alona/disputes/${dispute.id}`)).data.messages || [];
};

const createDispute = async () => {
  if (!form.value.parcel_id || !form.value.description) return;
  await axios.post('/alona/disputes', form.value);
  form.value = { parcel_id: '', category: 'DELIVERY_FAILED', priority: 'NORMAL', description: '' };
  showCreate.value = false;
  await load();
};

const sendMessage = async () => {
  if (!message.value.trim() || !selected.value) return;
  const response = await axios.post(`/alona/disputes/${selected.value.id}/messages`, { body: message.value.trim() });
  messages.value.push(response.data);
  message.value = '';
  await load();
};

const updateStatus = async (status) => {
  await axios.patch(`/alona/disputes/${selected.value.id}`, { status });
  selected.value.status = status;
  await load();
};

const statusTone = (status) => ({ OPEN: 'bg-amber-100 text-amber-800', IN_REVIEW: 'bg-blue-100 text-blue-800', RESOLVED: 'bg-emerald-100 text-emerald-800', CLOSED: 'bg-slate-100 text-slate-700' }[status] || 'bg-slate-100 text-slate-700');
onMounted(load);
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] px-4 py-6 text-slate-900 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
      <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
        <div><p class="text-xs font-black uppercase tracking-[0.24em] text-teal-700">Communication & support</p><h1 class="mt-2 text-3xl font-black tracking-tight">Delivery disputes</h1><p class="mt-1 text-sm text-slate-500">Keep sellers, riders, and logistics staff on the same parcel case.</p></div>
        <button class="inline-flex items-center gap-2 bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-700" @click="showCreate = true"><Plus class="h-4 w-4" /> Open dispute</button>
      </header>

      <p v-if="error" class="mt-5 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</p>
      <section class="mt-6 grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
        <div class="border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><p class="text-xs font-black uppercase tracking-widest text-teal-700">Case queue</p><h2 class="mt-1 text-xl font-black">Open conversations</h2></div><AlertTriangle class="h-5 w-5 text-amber-600" /></div>
          <div v-if="loading" class="p-8 text-center text-sm text-slate-500">Loading cases...</div>
          <button v-for="dispute in disputes" :key="dispute.id" class="block w-full border-b border-slate-100 px-5 py-4 text-left hover:bg-slate-50" :class="selected?.id === dispute.id ? 'border-l-4 border-l-teal-600 bg-teal-50/40' : ''" @click="openDispute(dispute)">
            <div class="flex items-start justify-between gap-3"><span class="font-mono text-sm font-bold">Case #{{ dispute.id }}</span><span class="px-2 py-1 text-[10px] font-black uppercase" :class="statusTone(dispute.status)">{{ dispute.status.replace('_', ' ') }}</span></div>
            <p class="mt-2 text-sm font-bold">Parcel {{ dispute.parcel?.tracking_number || dispute.parcel_id }}</p><p class="mt-1 text-xs text-slate-500">{{ dispute.category }} · {{ dispute.priority }}</p>
          </button>
          <p v-if="!loading && !disputes.length" class="p-10 text-center text-sm text-slate-500">No disputes have been opened.</p>
        </div>

        <div class="border border-slate-200 bg-white shadow-sm">
          <div v-if="selected" class="flex items-center justify-between border-b border-slate-200 px-5 py-4"><div><p class="text-xs font-black uppercase tracking-widest text-teal-700">Parcel {{ selected.parcel?.tracking_number || selected.parcel_id }}</p><h2 class="mt-1 text-xl font-black">{{ selected.category.replace('_', ' ') }}</h2></div><select v-if="isAdmin" :value="selected.status" class="border border-slate-300 px-2 py-2 text-xs font-bold" @change="updateStatus($event.target.value)"><option v-for="status in ['OPEN', 'IN_REVIEW', 'RESOLVED', 'CLOSED']" :key="status" :value="status">{{ status.replace('_', ' ') }}</option></select><MessageSquare v-else class="h-5 w-5 text-teal-700" /></div>
          <div v-if="selected" class="flex min-h-[420px] flex-col">
            <div class="border-b border-slate-100 px-5 py-4 text-sm text-slate-600">{{ selected.description }}</div>
            <div class="flex-1 space-y-3 overflow-y-auto p-5"><div v-for="item in messages" :key="item.id" class="border-l-2 border-teal-500 pl-3"><p class="text-sm font-bold">{{ item.sender?.name || 'Participant' }}</p><p class="mt-1 text-sm text-slate-700">{{ item.body }}</p><p class="mt-1 text-[11px] text-slate-400">{{ new Date(item.created_at).toLocaleString() }}</p></div><p v-if="!messages.length" class="py-8 text-center text-sm text-slate-500">No messages yet.</p></div>
            <form class="flex gap-2 border-t border-slate-200 p-4" @submit.prevent="sendMessage"><input v-model="message" class="min-w-0 flex-1 border border-slate-300 px-3 py-2 text-sm" placeholder="Write a case update..." /><button class="inline-flex items-center gap-2 bg-teal-700 px-4 py-2 text-sm font-bold text-white hover:bg-teal-800"><Send class="h-4 w-4" />Send</button></form>
          </div>
          <div v-else class="flex min-h-[420px] items-center justify-center p-10 text-center text-sm text-slate-500">Select a dispute to view its conversation.</div>
        </div>
      </section>

      <div v-if="showCreate" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4" @click.self="showCreate = false"><form class="w-full max-w-lg bg-white p-6 shadow-2xl" @submit.prevent="createDispute"><div class="flex items-center justify-between"><h2 class="text-xl font-black">Open parcel dispute</h2><button type="button" aria-label="Close" @click="showCreate = false"><X class="h-5 w-5" /></button></div><div class="mt-5 space-y-4"><label class="block text-sm font-bold">Parcel ID<input v-model="form.parcel_id" required type="number" class="mt-1 w-full border border-slate-300 px-3 py-2 font-mono" /></label><div class="grid gap-4 sm:grid-cols-2"><label class="block text-sm font-bold">Category<select v-model="form.category" class="mt-1 w-full border border-slate-300 px-3 py-2"><option>DELIVERY_FAILED</option><option>DAMAGED</option><option>LOST</option><option>DELAYED</option><option>OTHER</option></select></label><label class="block text-sm font-bold">Priority<select v-model="form.priority" class="mt-1 w-full border border-slate-300 px-3 py-2"><option>LOW</option><option>NORMAL</option><option>HIGH</option><option>URGENT</option></select></label></div><label class="block text-sm font-bold">Description<textarea v-model="form.description" required rows="4" class="mt-1 w-full border border-slate-300 px-3 py-2" /></label></div><button class="mt-6 inline-flex items-center gap-2 bg-slate-950 px-4 py-2.5 text-sm font-bold text-white"><CheckCircle2 class="h-4 w-4" />Create case</button></form></div>
    </div>
  </main>
</template>