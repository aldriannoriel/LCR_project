<script setup>
import { Bike, CheckCircle2, Package } from 'lucide-vue-next';

defineProps({
  modelValue: {
    type: String,
    default: '',
  },
});

defineEmits(['update:modelValue']);
</script>

<template>
  <div class="mb-8 grid gap-3 sm:grid-cols-2">
    <button
      type="button"
      class="relative rounded-xl border-2 p-5 text-left transition hover:scale-[1.01] active:scale-[0.99]"
      :class="modelValue === 'logistics' ? 'border-teal-500 bg-teal-500/10' : 'border-slate-700 bg-slate-900/80 hover:border-slate-600'"
      @click="$emit('update:modelValue', 'logistics')"
    >
      <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-teal-500 to-emerald-600">
          <Package class="h-6 w-6 text-white" />
        </div>
        <div>
          <p class="font-bold text-white">Logistics / Seller</p>
          <p class="mt-0.5 text-xs text-slate-400">Business pickups & shipments</p>
        </div>
      </div>
      <div v-if="modelValue === 'logistics'" class="absolute right-4 top-4">
        <CheckCircle2 class="h-5 w-5 text-teal-400" />
      </div>
    </button>

    <button
      type="button"
      class="relative rounded-xl border-2 p-5 text-left transition hover:scale-[1.01] active:scale-[0.99]"
      :class="modelValue === 'courier' ? 'border-blue-500 bg-blue-500/10' : 'border-slate-700 bg-slate-900/80 hover:border-slate-600'"
      @click="$emit('update:modelValue', 'courier')"
    >
      <div class="flex items-start gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600">
          <Bike class="h-6 w-6 text-white" />
        </div>
        <div>
          <p class="font-bold text-white">Courier / Rider</p>
          <p class="mt-0.5 text-xs text-slate-400">Deliveries & earn money</p>
        </div>
      </div>
      <div v-if="modelValue === 'courier'" class="absolute right-4 top-4">
        <CheckCircle2 class="h-5 w-5 text-blue-400" />
      </div>
    </button>
  </div>
</template>
