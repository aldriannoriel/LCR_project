<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  icon: { type: Object, default: null },
  trend: { type: String, default: null },
  color: { type: String, default: 'teal' },
});

const colorMap = {
  teal: 'bg-teal-50 text-teal-600',
  blue: 'bg-blue-50 text-blue-600',
  emerald: 'bg-emerald-50 text-emerald-600',
  rose: 'bg-rose-50 text-rose-600',
  amber: 'bg-amber-50 text-amber-600',
  slate: 'bg-slate-100 text-slate-600',
};
</script>

<template>
  <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ label }}</p>
        <p class="text-2xl font-black text-slate-900 mt-1">{{ value }}</p>
        <p v-if="trend" class="text-xs text-slate-500 mt-1">{{ trend }}</p>
      </div>
      <div
        v-if="icon"
        class="h-12 w-12 rounded-xl flex items-center justify-center shrink-0"
        :class="colorMap[color] || colorMap.teal"
      >
        <component :is="icon" class="h-6 w-6" />
      </div>
    </div>
  </div>
</template>
