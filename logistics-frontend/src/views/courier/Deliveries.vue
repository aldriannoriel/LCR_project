<script setup>
import { ref, computed, onMounted } from 'vue';
import { useCourierStore } from '../../stores/courier';
import {
  ArrowLeft,
  CheckCircle2,
  Clock,
  MapPin,
  Package,
  Phone,
  RefreshCw,
  X,
  AlertTriangle,
  Camera
} from 'lucide-vue-next';

const courier = useCourierStore();

const activeTab = ref('pending');
const showDetail = ref(false);
const selectedOrder = ref(null);
const completing = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

// Completion form
const completeForm = ref({ recipient_name: '', notes: '', proof_image: null });

// Failed form
const showFailedModal = ref(false);
const failedForm = ref({ reason: '', notes: '' });

const tabs = [
  { key: 'pending', label: 'To Deliver' },
  { key: 'completed', label: 'Completed' },
  { key: 'failed', label: 'Failed' },
];

const filteredOrders = computed(() => courier.deliveries.data);

const openDetail = async (order) => {
  try {
    selectedOrder.value = await courier.fetchDeliveryDetail(order.id);
    completeForm.value.recipient_name = selectedOrder.value.recipient_name || '';
    showDetail.value = true;
  } catch (e) {
    errorMsg.value = 'Failed to load details.';
  }
};

const openCompleteDialog = () => {
  selectedOrder.value._showComplete = true;
};

const handleProofUpload = (e) => {
  completeForm.value.proof_image = e.target.files[0] || null;
};

const confirmComplete = async () => {
  completing.value = true;
  errorMsg.value = '';
  try {
    const formData = new FormData();
    formData.append('recipient_name', completeForm.value.recipient_name);
    if (completeForm.value.notes) formData.append('notes', completeForm.value.notes);
    if (completeForm.value.proof_image) formData.append('proof_image', completeForm.value.proof_image);

    await courier.completeDelivery(selectedOrder.value.id, formData);
    successMsg.value = 'Delivery marked as completed!';
    showDetail.value = false;
    courier.fetchDeliveries();
    courier.fetchDashboard();
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to complete delivery.';
  } finally {
    completing.value = false;
  }
};

const openFailedDialog = () => {
  showFailedModal.value = true;
};

const confirmFailed = async () => {
  if (!failedForm.value.reason) return;
  completing.value = true;
  errorMsg.value = '';
  try {
    const formData = new FormData();
    formData.append('reason', failedForm.value.reason);
    if (failedForm.value.notes) formData.append('notes', failedForm.value.notes);

    await courier.failDelivery(selectedOrder.value.id, formData);
    successMsg.value = 'Delivery marked as failed. Package will be returned.';
    showFailedModal.value = false;
    showDetail.value = false;
    courier.fetchDeliveries();
    courier.fetchDashboard();
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to mark delivery as failed.';
  } finally {
    completing.value = false;
  }
};

const statusColor = (status) => ({
  out_for_delivery: 'bg-blue-100 text-blue-800',
  delivered: 'bg-emerald-100 text-emerald-800',
  failed: 'bg-rose-100 text-rose-800',
  returned: 'bg-orange-100 text-orange-800',
}[status] || 'bg-slate-100 text-slate-600');

const loadDeliveries = () => {
  courier.fetchDeliveries({ status: activeTab.value });
};

onMounted(loadDeliveries);
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-black text-slate-900">My Deliveries</h1>
        <p class="text-xs text-slate-500 mt-0.5">Your assigned delivery orders</p>
      </div>
      <button @click="loadDeliveries" class="p-2 hover:bg-slate-200 rounded-lg transition">
        <RefreshCw class="h-5 w-5 text-slate-600" :class="courier.loading ? 'animate-spin' : ''" />
      </button>
    </div>

    <!-- Alerts -->
    <div v-if="successMsg" class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-xl p-4">
      <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0" />
      <p class="text-sm font-semibold text-emerald-800 flex-1">{{ successMsg }}</p>
      <button @click="successMsg = ''"><X class="h-4 w-4 text-emerald-600" /></button>
    </div>
    <div v-if="errorMsg" class="flex items-center gap-2 bg-rose-50 border border-rose-200 rounded-xl p-4">
      <X class="h-5 w-5 text-rose-600 shrink-0" />
      <p class="text-sm font-semibold text-rose-800 flex-1">{{ errorMsg }}</p>
      <button @click="errorMsg = ''"><X class="h-4 w-4 text-rose-600" /></button>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 md:mx-0 md:px-0">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key; loadDeliveries()"
        class="shrink-0 px-4 py-2 rounded-xl text-xs font-bold transition-all"
        :class="activeTab === tab.key
          ? 'bg-blue-600 text-white shadow'
          : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Delivery Cards -->
    <div v-if="!courier.loading" class="space-y-3">
      <div
        v-for="order in filteredOrders"
        :key="order.id"
        @click="openDetail(order)"
        class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm hover:shadow-md transition-all cursor-pointer active:scale-[0.99]"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0 flex-1">
            <span class="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
              {{ order.awb_number }}
            </span>
            <p class="font-bold text-slate-900 mt-2 truncate">{{ order.recipient_name }}</p>
            <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
              <MapPin class="h-3 w-3 shrink-0" />
              <span class="truncate">{{ order.recipient_address }}</span>
            </p>
          </div>
          <span
            class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold capitalize"
            :class="statusColor(order.delivery_status || order.status)"
          >
            {{ (order.delivery_status || order.status).replace('_', ' ') }}
          </span>
        </div>

        <div class="mt-3 flex items-center gap-4 text-xs text-slate-600 border-t border-slate-100 pt-3">
          <span v-if="order.weight_kg" class="flex items-center gap-1">
            <Package class="h-3.5 w-3.5 text-slate-400" />
            {{ order.weight_kg }} kg
          </span>
          <span v-if="order.assigned_at" class="flex items-center gap-1">
            <Clock class="h-3.5 w-3.5 text-slate-400" />
            {{ new Date(order.assigned_at).toLocaleDateString() }}
          </span>
        </div>
      </div>

      <div v-if="!filteredOrders.length" class="text-center py-12 text-slate-400">
        <Package class="h-12 w-12 mx-auto mb-3 text-slate-300" />
        <p class="font-bold">No deliveries</p>
        <p class="text-xs mt-1">No {{ activeTab === 'pending' ? 'pending' : activeTab }} deliveries</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-else class="flex justify-center py-12">
      <RefreshCw class="h-8 w-8 text-blue-600 animate-spin" />
    </div>

    <!-- Detail Drawer -->
    <Teleport to="body">
      <div v-if="showDetail && selectedOrder" class="fixed inset-0 z-50 flex justify-end">
        <div class="absolute inset-0 bg-slate-950/40" @click="showDetail = false" />
        <div class="relative w-full max-w-md bg-white h-full overflow-y-auto shadow-2xl">
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-slate-200 p-4 flex items-center justify-between z-10">
            <button @click="showDetail = false" class="p-2 -ml-2 hover:bg-slate-100 rounded-lg">
              <ArrowLeft class="h-5 w-5" />
            </button>
            <h2 class="font-black text-slate-900">Delivery Details</h2>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold capitalize" :class="statusColor(selectedOrder.delivery_status || selectedOrder.status)">
              {{ (selectedOrder.delivery_status || selectedOrder.status).replace('_', ' ') }}
            </span>
          </div>

          <!-- Content -->
          <div class="p-4 space-y-4">
            <!-- AWB -->
            <div class="bg-blue-50 rounded-xl p-4">
              <p class="font-mono text-sm font-bold text-blue-700">{{ selectedOrder.awb_number }}</p>
              <p class="text-xs text-blue-500 mt-1">Assigned {{ selectedOrder.assigned_at ? new Date(selectedOrder.assigned_at).toLocaleString() : '' }}</p>
            </div>

            <!-- Recipient -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Recipient</h3>
              <div class="bg-white border border-slate-200 rounded-xl p-4">
                <p class="font-bold text-slate-900">{{ selectedOrder.recipient_name }}</p>
                <div class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                  <Phone class="h-4 w-4 text-slate-400" />
                  <span>{{ selectedOrder.recipient_phone || 'No phone' }}</span>
                </div>
              </div>
            </div>

            <!-- Address -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Delivery Address</h3>
              <div class="bg-white border border-slate-200 rounded-xl p-4">
                <p class="text-sm text-slate-900">{{ selectedOrder.recipient_address }}</p>
              </div>
            </div>

            <!-- Package Info -->
            <div class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Package</h3>
              <div class="bg-white border border-slate-200 rounded-xl p-4 space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-500">Weight</span>
                  <span class="font-semibold">{{ selectedOrder.weight_kg || 1 }} kg</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-500">Hub</span>
                  <span class="font-semibold">{{ selectedOrder.hub?.name || '—' }}</span>
                </div>
              </div>
            </div>

            <!-- Failure Reason -->
            <div v-if="selectedOrder.delivery_failure_reason" class="space-y-2">
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Failure Reason</h3>
              <div class="bg-rose-50 border border-rose-200 rounded-xl p-4">
                <p class="text-sm text-rose-800">{{ selectedOrder.delivery_failure_reason }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div v-if="selectedOrder.status === 'out_for_delivery'" class="pt-4 space-y-3 sticky bottom-0 bg-white border-t border-slate-200 -mx-4 px-4 py-4">
              <button
                @click="openCompleteDialog"
                class="w-full flex items-center justify-center gap-2 bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-500 transition"
              >
                <CheckCircle2 class="h-5 w-5" />
                Mark as Delivered
              </button>
              <button
                @click="openFailedDialog"
                class="w-full flex items-center justify-center gap-2 bg-rose-600 text-white font-bold py-3 rounded-xl hover:bg-rose-500 transition"
              >
                <AlertTriangle class="h-5 w-5" />
                Mark as Failed
              </button>

              <!-- Complete Dialog -->
              <div v-if="selectedOrder._showComplete" class="bg-slate-50 rounded-xl p-4 space-y-3 border border-slate-200">
                <h3 class="font-bold text-slate-900">Confirm Delivery</h3>
                <div>
                  <label class="text-xs font-bold text-slate-600">Received By (Name)</label>
                  <input v-model="completeForm.recipient_name" type="text" placeholder="Name of person receiving" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-600">Notes (Optional)</label>
                  <input v-model="completeForm.notes" type="text" placeholder="Any delivery notes" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
                </div>
                <div>
                  <label class="text-xs font-bold text-slate-600">Photo Proof (Optional)</label>
                  <div class="mt-1 border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:border-teal-500 transition cursor-pointer">
                    <input type="file" accept="image/*" @change="handleProofUpload" class="hidden" id="proof-upload" />
                    <label for="proof-upload" class="cursor-pointer flex flex-col items-center gap-2">
                      <Camera class="h-8 w-8 text-slate-400" />
                      <span class="text-xs text-slate-500">{{ completeForm.proof_image ? completeForm.proof_image.name : 'Tap to upload photo' }}</span>
                    </label>
                  </div>
                </div>
                <div class="flex gap-2">
                  <button @click="selectedOrder._showComplete = false" class="flex-1 py-2.5 border border-slate-300 rounded-lg font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                  </button>
                  <button @click="confirmComplete" :disabled="completing" class="flex-1 py-2.5 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-500 disabled:opacity-50">
                    {{ completing ? 'Saving...' : 'Confirm Delivery' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Failed Modal -->
      <div v-if="showFailedModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-950/60" @click="showFailedModal = false" />
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
          <div class="p-6 space-y-4">
            <div class="flex items-center gap-3">
              <div class="h-12 w-12 rounded-full bg-rose-100 flex items-center justify-center">
                <AlertTriangle class="h-6 w-6 text-rose-600" />
              </div>
              <div>
                <h3 class="font-black text-slate-900">Delivery Failed</h3>
                <p class="text-xs text-slate-500">Package will be returned to hub</p>
              </div>
            </div>

            <div>
              <label class="text-xs font-bold text-slate-600">Reason *</label>
              <select v-model="failedForm.reason" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" required>
                <option value="">Select reason</option>
                <option value="refused">Customer Refused</option>
                <option value="unreachable">Unreachable / No Answer</option>
                <option value="wrong_address">Wrong Address</option>
                <option value="damaged">Package Damaged</option>
                <option value="other">Other</option>
              </select>
            </div>

            <div>
              <label class="text-xs font-bold text-slate-600">Additional Notes</label>
              <textarea v-model="failedForm.notes" rows="2" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" placeholder="Optional details..." />
            </div>

            <div class="flex gap-3 pt-2">
              <button @click="showFailedModal = false" class="flex-1 py-3 border border-slate-300 rounded-xl font-semibold text-slate-700 hover:bg-slate-50">
                Cancel
              </button>
              <button
                @click="confirmFailed"
                :disabled="completing || !failedForm.reason"
                class="flex-1 py-3 bg-rose-600 text-white rounded-xl font-bold hover:bg-rose-500 disabled:opacity-50"
              >
                {{ completing ? 'Submitting...' : 'Confirm Failed' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
