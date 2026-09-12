<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCourierStore } from '../stores/courier';
import {
  ArrowLeft,
  Calendar,
  CheckCircle2,
  Clock,
  MapPin,
  Package,
  Phone,
  RefreshCw,
  Truck,
  X
} from 'lucide-vue-next';

const courier = useCourierStore();
const router = useRouter();

const activeTab = ref('pending');
const showDetail = ref(false);
const selectedPickup = ref(null);
const completing = ref(false);
const completionForm = ref({ actual_parcels_collected: 0 });
const successMsg = ref('');
const errorMsg = ref('');

const tabs = [
  { key: 'pending', label: 'Pending' },
  { key: 'assigned', label: 'Assigned' },
  { key: 'in_progress', label: 'In Progress' },
  { key: 'completed', label: 'Completed' },
];

const filteredPickups = computed(() => {
  return courier.pickups.data.filter(p => p.status === activeTab.value);
});

const openDetail = async (pickup) => {
  try {
    selectedPickup.value = await courier.fetchPickupDetail(pickup.id);
    showDetail.value = true;
  } catch (e) {
    errorMsg.value = 'Failed to load details.';
  }
};

const startPickup = async () => {
  if (!selectedPickup.value) return;
  completing.value = true;
  errorMsg.value = '';
  try {
    await courier.startPickup(selectedPickup.value.id);
    successMsg.value = 'Pickup started! Proceed to collect parcels.';
    selectedPickup.value = await courier.fetchPickupDetail(selectedPickup.value.id);
    courier.fetchPickups();
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to start pickup.';
  } finally {
    completing.value = false;
  }
};

const openCompleteDialog = () => {
  completionForm.value.actual_parcels_collected = selectedPickup.value?.estimated_parcels || 1;
  selectedPickup.value._showComplete = true;
};

const confirmComplete = async () => {
  if (!selectedPickup.value) return;
  completing.value = true;
  errorMsg.value = '';
  try {
    await courier.completePickup(selectedPickup.value.id, {
      actual_parcels_collected: completionForm.value.actual_parcels_collected,
    });
    successMsg.value = `Pickup completed! ${completionForm.value.actual_parcels_collected} parcels added to inbound.`;
    showDetail.value = false;
    courier.fetchPickups();
    courier.fetchDashboard();
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to complete pickup.';
  } finally {
    completing.value = false;
  }
};

const statusColor = (status) => ({
  pending: 'bg-amber-100 text-amber-800',
  verified: 'bg-blue-100 text-blue-800',
  assigned: 'bg-purple-100 text-purple-800',
  in_progress: 'bg-teal-100 text-teal-800',
  completed: 'bg-emerald-100 text-emerald-800',
  cancelled: 'bg-slate-100 text-slate-600',
}[status] || 'bg-slate-100 text-slate-600');

const statusLabel = (status) => ({
  pending: 'Pending',
  verified: 'Verified',
  assigned: 'Assigned',
  in_progress: 'In Progress',
  completed: 'Completed',
  cancelled: 'Cancelled',
}[status] || status);

const loadPickups = () => {
  courier.fetchPickups({ status: activeTab.value === 'all' ? undefined : activeTab.value });
};

onMounted(loadPickups);
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black text-slate-900">My Pickups</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your assigned pickup requests</p>
      </div>
      <button @click="loadPickups" class="p-2 hover:bg-slate-200 rounded-lg transition">
        <RefreshCw class="h-5 w-5 text-slate-600" :class="courier.loading ? 'animate-spin' : ''" />
      </button>
    </div>

    <!-- Success/Error Alerts -->
    <div v-if="successMsg" class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl p-4">
      <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
      <p class="text-sm font-semibold text-emerald-800">{{ successMsg }}</p>
      <button @click="successMsg = ''" class="ml-auto"><X class="h-4 w-4 text-emerald-600" /></button>
    </div>
    <div v-if="errorMsg" class="flex items-center gap-2 bg-rose-50 border border-rose-200 rounded-xl p-4">
      <X class="h-5 w-5 text-rose-600 shrink-0" />
      <p class="text-sm font-semibold text-rose-800">{{ errorMsg }}</p>
      <button @click="errorMsg = ''" class="ml-auto"><X class="h-4 w-4 text-rose-600" /></button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 md:mx-0 md:px-0">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key; loadPickups()"
        class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all"
        :class="activeTab === tab.key
          ? 'bg-teal-600 text-white shadow'
          : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Pickup Cards -->
    <div v-if="!courier.loading" class="space-y-3">
      <div
        v-for="pickup in filteredPickups"
        :key="pickup.id"
        @click="openDetail(pickup)"
        class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer active:scale-[0.99]"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <span class="font-mono text-xs font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded">
              {{ pickup.request_code }}
            </span>
            <p class="font-bold text-slate-900 mt-2">{{ pickup.contact_person }}</p>
            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
              <MapPin class="h-3 w-3" />
              {{ pickup.barangay }}, {{ pickup.city_municipality }}
            </p>
          </div>
          <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold capitalize" :class="statusColor(pickup.status)">
            {{ statusLabel(pickup.status) }}
          </span>
        </div>

        <div class="mt-3 flex items-center gap-4 text-xs text-slate-600 border-t border-slate-100 pt-3">
          <span class="flex items-center gap-1">
            <Calendar class="h-3.5 w-3.5 text-slate-400" />
            {{ pickup.scheduled_date }} ({{ pickup.time_slot }})
          </span>
          <span class="flex items-center gap-1 font-bold text-teal-700">
            <Package class="h-3.5 w-3.5" />
            {{ pickup.estimated_parcels }} parcels
          </span>
        </div>
      </div>

      <div v-if="!filteredPickups.length" class="text-center py-12 text-slate-400">
        <Package class="h-12 w-12 mx-auto mb-3 text-slate-300" />
        <p class="font-bold">No pickups</p>
        <p class="text-xs mt-1">You have no {{ activeTab.replace('_', ' ') }} pickups</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-else class="flex justify-center py-12">
      <RefreshCw class="h-8 w-8 text-teal-600 animate-spin" />
    </div>

    <!-- Pickup Detail Drawer -->
    <Teleport to="body">
      <div
        v-if="showDetail && selectedPickup"
        class="fixed inset-0 z-50 flex justify-end"
        @click.self="showDetail = false"
      >
        <div class="absolute inset-0 bg-slate-950/40" @click="showDetail = false" />
        <div class="relative w-full max-w-md bg-white h-full overflow-y-auto shadow-2xl">
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-center justify-between z-10">
            <button @click="showDetail = false" class="p-2 -ml-2 hover:bg-slate-100 rounded-lg">
              <ArrowLeft class="h-5 w-5" />
            </button>
            <h2 class="font-black text-slate-900">Pickup Details</h2>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize" :class="statusColor(selectedPickup.status)">
              {{ statusLabel(selectedPickup.status) }}
            </span>
          </div>

          <!-- Content -->
          <div class="p-4 space-y-4">
            <!-- Code & Seller -->
            <div class="bg-slate-50 rounded-xl p-4">
              <p class="font-mono text-sm font-bold text-teal-700">{{ selectedPickup.request_code }}</p>
              <p class="font-bold text-slate-900 mt-1">{{ selectedPickup.contact_person }}</p>
              <p class="text-xs text-slate-500">{{ selectedPickup.seller?.business_name || '' }}</p>
            </div>

            <!-- Address -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Pickup Address</h3>
              <div class="bg-white border border-slate-200 rounded-xl p-4 space-y-1">
                <p class="text-sm font-semibold text-slate-900">{{ selectedPickup.pickup_address }}</p>
                <p class="text-xs text-slate-500">{{ selectedPickup.barangay }}, {{ selectedPickup.city_municipality }}, {{ selectedPickup.province }}</p>
              </div>
            </div>

            <!-- Contact -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Contact</h3>
              <div class="bg-white border border-slate-200 rounded-xl p-4 flex items-center gap-3">
                <Phone class="h-5 w-5 text-slate-400" />
                <span class="font-semibold text-slate-900">{{ selectedPickup.contact_number }}</span>
              </div>
            </div>

            <!-- Schedule -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Schedule</h3>
              <div class="bg-white border border-slate-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-slate-900">{{ selectedPickup.scheduled_date }}</p>
                <p class="text-xs text-slate-500 capitalize mt-0.5">{{ selectedPickup.time_slot }} slot</p>
                <div class="mt-2 flex items-center gap-2 text-xs">
                  <Package class="h-4 w-4 text-teal-600" />
                  <span class="font-bold text-teal-700">Est. {{ selectedPickup.estimated_parcels }} parcels</span>
                </div>
              </div>
            </div>

            <!-- Special Instructions -->
            <div v-if="selectedPickup.special_instructions" class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Instructions</h3>
              <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                <p class="text-sm text-amber-800">{{ selectedPickup.special_instructions }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 space-y-3 sticky bottom-0 bg-white border-t border-slate-200 -mx-4 px-4 py-4">
              <button
                v-if="selectedPickup.status === 'assigned'"
                @click="startPickup"
                :disabled="completing"
                class="w-full flex items-center justify-center gap-2 bg-teal-600 text-white font-bold py-3 rounded-xl hover:bg-teal-500 disabled:opacity-50 transition"
              >
                <Truck class="h-5 w-5" />
                {{ completing ? 'Accepting...' : 'Accept Pickup' }}
              </button>

              <button
                v-if="selectedPickup.status === 'in_progress'"
                @click="openCompleteDialog"
                class="w-full flex items-center justify-center gap-2 bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-500 transition"
              >
                <CheckCircle2 class="h-5 w-5" />
                Scan & Confirm Parcel
              </button>

              <!-- Complete Dialog -->
              <div v-if="selectedPickup._showComplete" class="bg-slate-50 rounded-xl p-4 space-y-3 border border-slate-200">
                <h3 class="font-bold text-slate-900">Confirm Collected Parcels</h3>
                <div>
                  <label class="text-xs font-bold text-slate-600">Actual Parcels Collected</label>
                  <input
                    v-model.number="completionForm.actual_parcels_collected"
                    type="number"
                    min="1"
                    class="mt-1 w-full border border-slate-300 rounded-lg p-3 font-bold text-lg"
                  />
                </div>
                <div class="flex gap-2">
                  <button
                    @click="selectedPickup._showComplete = false"
                    class="flex-1 py-2.5 border border-slate-300 rounded-lg font-semibold text-slate-700 hover:bg-slate-100"
                  >
                    Cancel
                  </button>
                  <button
                    @click="confirmComplete"
                    :disabled="completing"
                    class="flex-1 py-2.5 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-500 disabled:opacity-50"
                  >
                    {{ completing ? 'Saving...' : 'Confirm' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
