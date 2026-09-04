<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useRiderStore } from '../../stores/rider';
import RiderDetailDrawer from '../../components/fleet/RiderDetailDrawer.vue';
import AssignOrdersModal from '../../components/fleet/AssignOrdersModal.vue';
import { axios } from '../../lib/echo';
import {
  Truck,
  ShieldCheck,
  CheckCircle2,
  XCircle,
  FileText,
  UserCheck,
  UserX,
  Power,
  Plus,
  X,
  Search,
  RefreshCw,
  ExternalLink,
  Clock
} from 'lucide-vue-next';

const store = useRiderStore();
const route = useRoute();

const activeTab = ref('fleet'); // 'fleet' or 'applications'
const filters = reactive({
  search: '',
  archipelago_id: '',
  hub_id: '',
  status: '',
  application_status: 'approved',
  vehicle_type: '',
  page: 1,
  per_page: 10,
});

const archipelagos = ref([]);
const hubs = ref([]);
const detailRider = ref(null);
const assignmentRider = ref(null);
const pending = ref(new Set());
let timer;

// Modals
const showCreateRiderModal = ref(false);
const showRejectRiderModal = ref(false);
const selectedRiderForAction = ref(null);
const rejectionReason = ref('');
const actionLoading = ref(false);
const actionMessage = ref('');
const actionError = ref('');

const newRiderForm = ref({
  name: '',
  email: '',
  password: 'password123',
  phone_number: '',
  hub_id: null,
  vehicle_type: 'motorcycle',
  plate_number: '',
  license_number: '',
  application_status: 'approved',
});

const licenseDocFile = ref(null);
const vehicleOrCrFile = ref(null);

const statusClass = {
  available: 'bg-emerald-100 text-emerald-800',
  on_delivery: 'bg-blue-100 text-blue-800',
  off_duty: 'bg-slate-100 text-slate-700',
  suspended: 'bg-red-100 text-red-800',
};

const label = (value) => value?.replaceAll('_', ' ');

const load = () => {
  filters.application_status = activeTab.value === 'fleet' ? 'approved' : 'pending_review';
  store.fetchRiders({
    ...filters,
    archipelago_id: filters.archipelago_id || undefined,
    hub_id: filters.hub_id || undefined,
    status: filters.status || undefined,
    application_status: filters.application_status,
    vehicle_type: filters.vehicle_type || undefined,
  });
};

const search = () => {
  clearTimeout(timer);
  timer = setTimeout(() => {
    filters.page = 1;
    load();
  }, 300);
};

const toggleDuty = async (rider) => {
  const old = rider.status;
  const next = old === 'available' ? 'off_duty' : 'available';
  rider.status = next;
  pending.value.add(rider.id);
  try {
    await store.updateStatus(rider.id, next);
  } catch (_) {
    rider.status = old;
  } finally {
    pending.value.delete(rider.id);
  }
};

const toggleActiveStatus = async (rider) => {
  actionLoading.value = true;
  actionMessage.value = '';
  actionError.value = '';

  try {
    const res = await axios.patch(`/riders/${rider.id}/toggle-active`, {}, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
    });
    rider.status = res.data.rider.status;
    actionMessage.value = res.data.message;
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to update rider status.';
  } finally {
    actionLoading.value = false;
  }
};

const approveRiderApplication = async (rider) => {
  if (!confirm(`Approve rider application for ${rider.user?.name}?`)) return;

  actionLoading.value = true;
  actionMessage.value = '';
  actionError.value = '';

  try {
    const res = await axios.post(`/riders/${rider.id}/approve-application`, {}, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
    });
    actionMessage.value = res.data.message;
    load();
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Approval failed.';
  } finally {
    actionLoading.value = false;
  }
};

const openRejectRiderModal = (rider) => {
  selectedRiderForAction.value = rider;
  rejectionReason.value = '';
  showRejectRiderModal.value = true;
};

const submitRiderRejection = async () => {
  if (!rejectionReason.value.trim()) return;

  actionLoading.value = true;
  actionMessage.value = '';
  actionError.value = '';

  try {
    const res = await axios.post(`/riders/${selectedRiderForAction.value.id}/reject-application`, {
      reason: rejectionReason.value,
    }, {
      headers: { Authorization: `Bearer ${localStorage.getItem('token')}` },
    });
    actionMessage.value = res.data.message;
    showRejectRiderModal.value = false;
    load();
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Rejection failed.';
  } finally {
    actionLoading.value = false;
  }
};

const handleLicenseDoc = (e) => {
  licenseDocFile.value = e.target.files[0] || null;
};

const handleVehicleOrCr = (e) => {
  vehicleOrCrFile.value = e.target.files[0] || null;
};

const submitNewRider = async () => {
  actionLoading.value = true;
  actionError.value = '';

  try {
    const formData = new FormData();
    Object.keys(newRiderForm.value).forEach((k) => {
      if (newRiderForm.value[k] !== null) formData.append(k, newRiderForm.value[k]);
    });
    if (licenseDocFile.value) formData.append('license_doc', licenseDocFile.value);
    if (vehicleOrCrFile.value) formData.append('vehicle_or_cr', vehicleOrCrFile.value);

    await axios.post('/riders', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        Authorization: `Bearer ${localStorage.getItem('token')}`,
      },
    });

    actionMessage.value = 'New rider created successfully!';
    showCreateRiderModal.value = false;
    load();
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to create rider.';
  } finally {
    actionLoading.value = false;
  }
};

const reload = () => {
  detailRider.value = null;
  assignmentRider.value = null;
  load();
};

watch([() => filters.archipelago_id, () => filters.hub_id, () => filters.status, () => filters.vehicle_type, activeTab], () => {
  filters.page = 1;
  load();
});

onMounted(async () => {
  store.auth();
  try {
    archipelagos.value = (await axios.get('/archipelagos')).data;
    hubs.value = (await axios.get('/hubs')).data;
    if (hubs.value.length) newRiderForm.value.hub_id = hubs.value[0].id;
  } catch (_) {}
  await load();
  if (route.query.assign) {
    assignmentRider.value = store.riders.data.find((rider) => rider.status === 'available') || null;
  }
});
</script>

<template>
  <main class="min-h-screen bg-[#f4f7f6] text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto max-w-7xl px-6 py-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-teal-700">Fleet operations</p>
            <h1 class="text-3xl font-black mt-1">Rider fleet & applications</h1>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="load"
              class="flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
              <RefreshCw class="h-4 w-4" :class="store.loading ? 'animate-spin' : ''" />
              Refresh
            </button>
            <button
              @click="showCreateRiderModal = true"
              class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-teal-700/20 hover:bg-teal-500 transition"
            >
              <Plus class="h-4 w-4" />
              Onboard New Rider
            </button>
          </div>
        </div>

        <!-- Tab Switching -->
        <div class="mt-6 flex gap-4 border-b border-slate-200">
          <button
            @click="activeTab = 'fleet'"
            class="pb-3 text-sm font-bold border-b-2 transition"
            :class="activeTab === 'fleet' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
          >
            Active Fleet Directory ({{ activeTab === 'fleet' ? store.riders.total : '—' }})
          </button>
          <button
            @click="activeTab = 'applications'"
            class="pb-3 text-sm font-bold border-b-2 transition flex items-center gap-1.5"
            :class="activeTab === 'applications' ? 'border-teal-600 text-teal-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
          >
            <ShieldCheck class="h-4 w-4" />
            Rider Applications & Approvals ({{ activeTab === 'applications' ? store.riders.total : '—' }})
          </button>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-7xl px-6 py-8">
      <!-- Alerts -->
      <div v-if="actionMessage" class="mb-6 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800">
        <CheckCircle2 class="h-5 w-5 text-emerald-600" />
        {{ actionMessage }}
      </div>

      <div v-if="actionError" class="mb-6 flex items-center gap-2 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800">
        <XCircle class="h-5 w-5 text-rose-600" />
        {{ actionError }}
      </div>

      <section class="border border-slate-200 bg-white shadow-sm rounded-xl overflow-hidden">
        <!-- Filters Toolbar -->
        <div class="grid gap-3 border-b border-slate-200 p-5 md:grid-cols-2 lg:grid-cols-5">
          <input
            v-model="filters.search"
            @input="search"
            placeholder="Search rider, phone, or plate"
            class="border border-slate-300 px-3 py-2 text-sm outline-none focus:border-teal-500 rounded-lg"
          />
          <select v-model="filters.archipelago_id" class="border border-slate-300 px-3 py-2 text-sm rounded-lg">
            <option value="">All archipelagos</option>
            <option v-for="item in archipelagos" :key="item.id" :value="item.id">{{ item.name }}</option>
          </select>
          <select v-model="filters.hub_id" class="border border-slate-300 px-3 py-2 text-sm rounded-lg">
            <option value="">All hubs</option>
            <option v-for="hub in hubs" :key="hub.id" :value="hub.id">{{ hub.name }}</option>
          </select>
          <select v-model="filters.status" class="border border-slate-300 px-3 py-2 text-sm rounded-lg">
            <option value="">All duty status</option>
            <option v-for="status in ['available','on_delivery','off_duty','suspended']" :key="status" :value="status">
              {{ label(status) }}
            </option>
          </select>
          <select v-model="filters.vehicle_type" class="border border-slate-300 px-3 py-2 text-sm rounded-lg">
            <option value="">All vehicles</option>
            <option v-for="type in ['motorcycle','tricycle','van','truck']" :key="type">{{ type }}</option>
          </select>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full min-w-[950px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
              <tr>
                <th class="px-5 py-3.5">Rider Details</th>
                <th class="px-5 py-3.5">Hub Assignment</th>
                <th class="px-5 py-3.5">Vehicle & Plate</th>
                <th class="px-5 py-3.5">License & Docs</th>
                <th class="px-5 py-3.5">Status</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-if="store.loading">
                <td colspan="6" class="p-10 text-center text-slate-400">Loading rider directory...</td>
              </tr>

              <tr v-for="rider in store.riders.data" v-else :key="rider.id" class="hover:bg-slate-50/80 transition">
                <td class="px-5 py-4">
                  <div class="font-bold text-slate-900">{{ rider.user?.name }}</div>
                  <div class="text-xs text-slate-500 mt-0.5">{{ rider.phone_number || rider.user?.email }}</div>
                </td>
                <td class="px-5 py-4 text-slate-600">
                  <span class="font-medium">{{ rider.hub?.name || 'Unassigned' }}</span>
                </td>
                <td class="px-5 py-4 capitalize">
                  <div class="font-semibold text-slate-800">{{ rider.vehicle_type }}</div>
                  <div class="font-mono text-xs text-slate-500">{{ rider.plate_number || 'No plate' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="text-xs text-slate-700">License: <strong>{{ rider.license_number || 'On File' }}</strong></div>
                  <div class="flex items-center gap-1.5 mt-1">
                    <span v-if="rider.license_doc_path" class="text-[10px] bg-teal-50 text-teal-700 px-1.5 py-0.5 rounded border border-teal-200 font-bold">
                      License doc
                    </span>
                    <span v-if="rider.vehicle_or_cr_path" class="text-[10px] bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded border border-blue-200 font-bold">
                      OR/CR
                    </span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2">
                    <button
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-bold capitalize rounded-full transition disabled:opacity-50"
                      :class="statusClass[rider.status]"
                      :disabled="pending.has(rider.id) || rider.status === 'suspended'"
                      @click="toggleDuty(rider)"
                    >
                      <span class="h-2 w-2 rounded-full bg-current" />
                      {{ label(rider.status) }}
                    </button>
                  </div>
                </td>

                <td class="space-x-2 px-5 py-4 text-right">
                  <!-- Actions for Pending Applications -->
                  <template v-if="activeTab === 'applications'">
                    <button
                      @click="approveRiderApplication(rider)"
                      :disabled="actionLoading"
                      class="inline-flex items-center gap-1 rounded bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-emerald-500 shadow-sm"
                    >
                      <CheckCircle2 class="h-3.5 w-3.5" /> Approve
                    </button>
                    <button
                      @click="openRejectRiderModal(rider)"
                      :disabled="actionLoading"
                      class="inline-flex items-center gap-1 rounded bg-rose-600 px-2.5 py-1 text-xs font-bold text-white hover:bg-rose-500 shadow-sm"
                    >
                      <XCircle class="h-3.5 w-3.5" /> Disapprove
                    </button>
                  </template>

                  <!-- Actions for Active Fleet -->
                  <template v-else>
                    <button
                      @click="toggleActiveStatus(rider)"
                      class="inline-flex items-center gap-1 rounded border px-2.5 py-1 text-xs font-semibold shadow-sm transition"
                      :class="rider.status === 'suspended' ? 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' : 'border-rose-300 bg-rose-50 text-rose-800 hover:bg-rose-100'"
                    >
                      <Power class="h-3 w-3" />
                      {{ rider.status === 'suspended' ? 'Activate' : 'Deactivate' }}
                    </button>
                    <button class="font-semibold text-teal-700 hover:underline text-xs" @click="detailRider = rider">
                      Details
                    </button>
                    <button
                      v-if="rider.status === 'available'"
                      class="font-semibold text-blue-700 hover:underline text-xs"
                      @click="assignmentRider = rider"
                    >
                      Assign Deliveries
                    </button>
                  </template>
                </td>
              </tr>

              <tr v-if="!store.loading && !store.riders.data.length">
                <td colspan="6" class="p-10 text-center text-slate-400">
                  {{ activeTab === 'applications' ? 'No pending rider applications.' : 'No riders match current filters.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Footer Pagination -->
        <footer class="flex justify-between border-t border-slate-200 px-5 py-4 text-xs font-medium text-slate-600">
          <span>Page {{ store.riders.current_page }} of {{ store.riders.last_page }}</span>
          <div class="flex gap-2">
            <button
              :disabled="store.riders.current_page <= 1"
              class="border px-3 py-1.5 rounded disabled:opacity-40 hover:bg-slate-50"
              @click="filters.page--; load()"
            >
              Previous
            </button>
            <button
              :disabled="store.riders.current_page >= store.riders.last_page"
              class="border px-3 py-1.5 rounded disabled:opacity-40 hover:bg-slate-50"
              @click="filters.page++; load()"
            >
              Next
            </button>
          </div>
        </footer>
      </section>
    </div>

    <!-- Drawers & Modals -->
    <div v-if="detailRider" class="fixed inset-0 z-20 bg-slate-950/30 backdrop-blur-xs" @click.self="detailRider = null">
      <RiderDetailDrawer :rider="detailRider" @close="detailRider = null" />
    </div>

    <AssignOrdersModal
      v-if="assignmentRider"
      :rider="assignmentRider"
      @close="assignmentRider = null"
      @assigned="reload"
    />

    <!-- Create Rider Modal -->
    <div
      v-if="showCreateRiderModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showCreateRiderModal = false"
    >
      <div class="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
          <h2 class="text-xl font-black text-slate-900">Onboard / Register New Rider</h2>
          <button @click="showCreateRiderModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="h-6 w-6" />
          </button>
        </div>

        <form @submit.prevent="submitNewRider" class="mt-4 space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Full Name *</label>
              <input v-model="newRiderForm.name" required type="text" placeholder="Juan Rider" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Email Address *</label>
              <input v-model="newRiderForm.email" required type="email" placeholder="rider@logistics.local" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Contact Number *</label>
              <input v-model="newRiderForm.phone_number" required type="tel" placeholder="0917-xxx-xxxx" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Hub Assignment *</label>
              <select v-model="newRiderForm.hub_id" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-semibold">
                <option v-for="h in hubs" :key="h.id" :value="h.id">{{ h.name }}</option>
              </select>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-3">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Vehicle Type *</label>
              <select v-model="newRiderForm.vehicle_type" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm">
                <option value="motorcycle">Motorcycle (max 30)</option>
                <option value="tricycle">Tricycle (max 50)</option>
                <option value="van">Van (max 100)</option>
                <option value="truck">Truck (max 300)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Plate Number</label>
              <input v-model="newRiderForm.plate_number" type="text" placeholder="ABC-1234" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm uppercase" />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Driver's License #</label>
              <input v-model="newRiderForm.license_number" type="text" placeholder="N01-12-345678" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm uppercase" />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2 border-t border-slate-200 pt-4">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Upload Driver's License</label>
              <input type="file" accept="image/*,application/pdf" @change="handleLicenseDoc" class="mt-1 w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 hover:file:bg-slate-200" />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Upload Vehicle OR / CR</label>
              <input type="file" accept="image/*,application/pdf" @change="handleVehicleOrCr" class="mt-1 w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-slate-100 hover:file:bg-slate-200" />
            </div>
          </div>

          <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
            <button type="button" @click="showCreateRiderModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button type="submit" :disabled="actionLoading" class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-bold text-white hover:bg-teal-500 disabled:opacity-50">
              {{ actionLoading ? 'Saving...' : 'Register Rider' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject Rider Application Modal -->
    <div
      v-if="showRejectRiderModal && selectedRiderForAction"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showRejectRiderModal = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-black text-slate-900">Disapprove Rider Application</h2>
        <p class="text-xs text-slate-500 mt-1">Provide a reason for rejecting {{ selectedRiderForAction.user?.name }}.</p>

        <form @submit.prevent="submitRiderRejection" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Reason *</label>
            <textarea v-model="rejectionReason" rows="3" required placeholder="e.g. Expired driver's license or invalid vehicle registration." class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm"></textarea>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="showRejectRiderModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button type="submit" :disabled="actionLoading || !rejectionReason.trim()" class="rounded-lg bg-rose-600 px-5 py-2 text-sm font-bold text-white hover:bg-rose-500 disabled:opacity-50">
              {{ actionLoading ? 'Submitting...' : 'Confirm Disapproval' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</template>