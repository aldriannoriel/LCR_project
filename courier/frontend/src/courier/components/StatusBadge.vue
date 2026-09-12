<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: { type: String, required: true },
  size: { type: String, default: 'md' }, // 'sm' | 'md' | 'lg'
});

const config = {
  // Pickup statuses
  pending: { label: 'Pending', class: 'bg-amber-100 text-amber-800 border-amber-200' },
  verified: { label: 'Verified', class: 'bg-blue-100 text-blue-800 border-blue-200' },
  assigned: { label: 'Assigned', class: 'bg-purple-100 text-purple-800 border-purple-200' },
  in_progress: { label: 'In Progress', class: 'bg-teal-100 text-teal-800 border-teal-200' },
  completed: { label: 'Completed', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  cancelled: { label: 'Cancelled', class: 'bg-slate-100 text-slate-600 border-slate-200' },
  // Delivery statuses
  out_for_delivery: { label: 'Out for Delivery', class: 'bg-blue-100 text-blue-800 border-blue-200' },
  delivered: { label: 'Delivered', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  failed: { label: 'Failed', class: 'bg-rose-100 text-rose-800 border-rose-200' },
  returned: { label: 'Returned', class: 'bg-orange-100 text-orange-800 border-orange-200' },
  // Rider statuses
  available: { label: 'Available', class: 'bg-emerald-100 text-emerald-800 border-emerald-200' },
  on_delivery: { label: 'On Delivery', class: 'bg-blue-100 text-blue-800 border-blue-200' },
  off_duty: { label: 'Off Duty', class: 'bg-slate-100 text-slate-600 border-slate-200' },
  suspended: { label: 'Suspended', class: 'bg-red-100 text-red-800 border-red-200' },
};

const sizeClass = {
  sm: 'text-[10px] px-2 py-0.5',
  md: 'text-xs px-2.5 py-1',
  lg: 'text-sm px-3 py-1.5',
};

const badge = computed(() => config[props.status] || { label: props.status, class: 'bg-slate-100 text-slate-600 border-slate-200' });
</script>

<template>
  <span
    class="inline-flex items-center rounded-full font-bold capitalize border"
    :class="[badge.class, sizeClass[size]]"
  >
    {{ badge.label }}
  </span>
</template>
