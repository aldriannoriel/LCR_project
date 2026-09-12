<script setup>
defineProps({ historyItems: { type: Array, default: () => [] } });
const label = (value) => value?.replaceAll('_', ' ');
</script>

<template>
  <div class="relative"><div v-if="historyItems.length" class="absolute bottom-4 left-2.5 top-4 w-px bg-slate-200" /><ol v-if="historyItems.length" class="space-y-6"><li v-for="(item, index) in historyItems" :key="item.id || index" class="relative flex gap-4"><span class="relative z-10 mt-1 h-5 w-5 shrink-0 rounded-full border-4 border-white bg-teal-600 shadow" /><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center justify-between gap-2"><span class="inline-flex bg-slate-100 px-2 py-1 text-xs font-bold capitalize text-slate-700">{{ label(item.status) }}</span><time class="text-xs text-slate-400">{{ item.created_at ? new Date(item.created_at).toLocaleString() : '—' }}</time></div><p class="mt-2 text-sm text-slate-600">{{ item.notes || 'No notes recorded.' }}</p><p class="mt-1 text-xs text-slate-400">{{ item.performer?.name || 'System operator' }}<span v-if="item.hub"> · {{ item.hub.name }}</span></p></div></li></ol><p v-else class="py-8 text-center text-sm text-slate-400">No tracking history recorded yet.</p></div>
</template>