<script setup>
import { Calendar, Check, CheckCircle2, Clock, FileCheck, MapPin, PackagePlus, Phone, Truck } from 'lucide-vue-next';

defineProps({
  pickup: {
    type: Object,
    required: true,
  },
});

defineEmits(['verify', 'assign', 'complete']);
</script>

<template>
  <div class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
    <div>
      <div class="flex items-start justify-between">
        <div>
          <span class="rounded border border-teal-200 bg-teal-50 px-2 py-0.5 font-mono text-xs font-bold text-teal-700">
            {{ pickup.request_code }}
          </span>
          <h3 class="mt-2 text-base font-bold text-slate-900">
            {{ pickup.seller?.business_name || pickup.contact_person }}
          </h3>
        </div>

        <span v-if="pickup.status === 'completed'" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
          <CheckCircle2 class="h-3 w-3" /> Collected
        </span>
        <span v-else-if="pickup.status === 'assigned'" class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">
          <Truck class="h-3 w-3" /> Dispatched
        </span>
        <span v-else-if="pickup.status === 'verified'" class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-1 text-xs font-bold text-teal-800">
          <FileCheck class="h-3 w-3" /> Verified
        </span>
        <span v-else class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">
          <Clock class="h-3 w-3" /> Pending Review
        </span>
      </div>

      <div class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-xs text-slate-600">
        <div class="flex items-center gap-2"><MapPin class="h-3.5 w-3.5 shrink-0 text-slate-400" /><span class="truncate">{{ pickup.barangay }}, {{ pickup.city_municipality }}, {{ pickup.province }}</span></div>
        <div class="flex items-center gap-2"><Phone class="h-3.5 w-3.5 shrink-0 text-slate-400" /><span>{{ pickup.contact_number }} ({{ pickup.contact_person }})</span></div>
        <div class="flex items-center gap-2"><Calendar class="h-3.5 w-3.5 text-slate-400" /><span>Schedule: <strong>{{ pickup.scheduled_date }}</strong> ({{ pickup.time_slot }})</span></div>
        <div class="flex items-center gap-2 font-semibold text-slate-800"><PackagePlus class="h-3.5 w-3.5 shrink-0 text-teal-600" /><span>Est. Volume: {{ pickup.estimated_parcels }} parcels</span></div>
        <div v-if="pickup.rider" class="flex items-center gap-2 pt-1 font-semibold text-blue-700"><Truck class="h-3.5 w-3.5 shrink-0" /><span>Assigned Rider: {{ pickup.rider.user?.name || 'Rider #' + pickup.rider.id }} ({{ pickup.rider.vehicle_type }})</span></div>
      </div>
    </div>

    <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 pt-3">
      <button v-if="pickup.status === 'pending'" class="inline-flex items-center gap-1 rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-teal-500" @click="$emit('verify', pickup)">
        <Check class="h-3.5 w-3.5" /> Verify & Approve
      </button>
      <button v-if="pickup.status === 'verified'" class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-blue-500" @click="$emit('assign', pickup)">
        <Truck class="h-3.5 w-3.5" /> Assign Rider
      </button>
      <button v-if="pickup.status === 'assigned'" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-500" @click="$emit('complete', pickup)">
        <CheckCircle2 class="h-3.5 w-3.5" /> Confirm Collected
      </button>
    </div>
  </div>
</template>
