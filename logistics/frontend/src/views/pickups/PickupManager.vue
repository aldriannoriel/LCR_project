<script setup>
import { ref, onMounted, watch } from 'vue';
import { axios } from '../../lib/echo';
import { useAuthStore } from '../../stores/auth';
import { philippineAddressService } from '../../services/philippineAddressService';
import PickupCard from '../../components/pickups/PickupCard.vue';
import {
  PackagePlus,
  Truck,
  CheckCircle2,
  Clock,
  MapPin,
  Calendar,
  User,
  Phone,
  Search,
  Plus,
  Filter,
  X,
  Building,
  Check,
  Ban,
  ArrowRight,
  Loader2,
  RefreshCw,
  FileCheck
} from 'lucide-vue-next';

const auth = useAuthStore();

const pickups = ref([]);
const hubs = ref([]);
const availableRiders = ref([]);
const activeTab = ref('all');
const searchQuery = ref('');
const loading = ref(false);

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Modal states
const showCreateModal = ref(false);
const showVerifyModal = ref(false);
const showAssignModal = ref(false);
const showCompleteModal = ref(false);
const selectedPickup = ref(null);
const processing = ref(false);
const actionMessage = ref('');
const actionError = ref('');

// Create Form
const createForm = ref({
  contact_person: '',
  contact_number: '',
  province: '',
  city_municipality: '',
  barangay: '',
  pickup_address: '',
  scheduled_date: new Date().toISOString().split('T')[0],
  time_slot: 'morning',
  estimated_parcels: 5,
  package_type: 'parcels',
  special_instructions: '',
  hub_id: null,
});

// Address cascading
const provinces = ref([]);
const cities = ref([]);
const barangays = ref([]);
const loadingProvinces = ref(false);
const loadingCities = ref(false);
const loadingBarangays = ref(false);

// Actions Form Data
const verifyForm = ref({
  hub_id: null,
  verification_notes: '',
});

const assignForm = ref({
  rider_id: null,
});

const completeForm = ref({
  actual_parcels_collected: 5,
  create_inbound_parcels: true,
});

const loadProvinces = async () => {
  loadingProvinces.value = true;
  try {
    provinces.value = await philippineAddressService.getProvinces();
  } finally {
    loadingProvinces.value = false;
  }
};

const onProvinceChange = async () => {
  createForm.value.city_municipality = '';
  createForm.value.barangay = '';
  cities.value = [];
  barangays.value = [];

  const found = provinces.value.find((p) => p.name === createForm.value.province);
  if (found) {
    loadingCities.value = true;
    try {
      cities.value = await philippineAddressService.getCities(found);
    } finally {
      loadingCities.value = false;
    }
  }
};

const onCityChange = async () => {
  createForm.value.barangay = '';
  barangays.value = [];

  const found = cities.value.find((c) => c.name === createForm.value.city_municipality);
  if (found) {
    loadingBarangays.value = true;
    try {
      barangays.value = await philippineAddressService.getBarangays(found);
    } finally {
      loadingBarangays.value = false;
    }
  }
};

const fetchPickups = async (page = 1) => {
  loading.value = true;
  actionMessage.value = '';
  actionError.value = '';

  try {
    const res = await axios.get('/pickup-requests', {
      params: {
        status: activeTab.value !== 'all' ? activeTab.value : undefined,
        search: searchQuery.value || undefined,
        page,
        per_page: 12,
      },
      headers: { Authorization: `Bearer ${auth.token}` },
    });

    pickups.value = res.data.data;
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      total: res.data.total,
    };
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to load pickup requests.';
  } finally {
    loading.value = false;
  }
};

const fetchHubs = async () => {
  try {
    const res = await axios.get('/hubs', { headers: { Authorization: `Bearer ${auth.token}` } });
    hubs.value = res.data;
  } catch (_) {}
};

const fetchAvailableRiders = async (hubId) => {
  try {
    const res = await axios.get('/riders', {
      params: { hub_id: hubId || undefined, per_page: 50 },
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    availableRiders.value = res.data.data;
  } catch (_) {}
};

const openCreateModal = () => {
  if (auth.user) {
    createForm.value.contact_person = auth.user.name || `${auth.user.first_name || ''} ${auth.user.last_name || ''}`.trim();
    createForm.value.contact_number = auth.user.phone_number || '';
    createForm.value.province = auth.user.province || '';
    createForm.value.city_municipality = auth.user.city_municipality || '';
    createForm.value.barangay = auth.user.barangay || '';
    createForm.value.pickup_address = auth.user.street_address || '';
    createForm.value.hub_id = auth.user.hub_id || null;
  }
  showCreateModal.value = true;
};

const submitCreatePickup = async () => {
  processing.value = true;
  actionError.value = '';

  try {
    await axios.post('/pickup-requests', createForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    actionMessage.value = 'Parcel pickup request scheduled successfully!';
    showCreateModal.value = false;
    fetchPickups(1);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to schedule pickup.';
  } finally {
    processing.value = false;
  }
};

const openVerifyDialog = (pickup) => {
  selectedPickup.value = pickup;
  verifyForm.value.hub_id = pickup.hub_id || (hubs.value[0]?.id || null);
  verifyForm.value.verification_notes = '';
  showVerifyModal.value = true;
};

const submitVerification = async () => {
  processing.value = true;
  actionError.value = '';

  try {
    await axios.post(`/pickup-requests/${selectedPickup.value.id}/verify`, verifyForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    actionMessage.value = `Pickup request ${selectedPickup.value.request_code} verified!`;
    showVerifyModal.value = false;
    fetchPickups(pagination.value.current_page);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Verification failed.';
  } finally {
    processing.value = false;
  }
};

const openAssignDialog = (pickup) => {
  selectedPickup.value = pickup;
  assignForm.value.rider_id = pickup.assigned_rider_id || null;
  fetchAvailableRiders(pickup.hub_id);
  showAssignModal.value = true;
};

const submitAssignRider = async () => {
  if (!assignForm.value.rider_id) return;
  processing.value = true;
  actionError.value = '';

  try {
    await axios.post(`/pickup-requests/${selectedPickup.value.id}/assign-rider`, assignForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    actionMessage.value = `Rider assigned to pickup ${selectedPickup.value.request_code}!`;
    showAssignModal.value = false;
    fetchPickups(pagination.value.current_page);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to assign rider.';
  } finally {
    processing.value = false;
  }
};

const openCompleteDialog = (pickup) => {
  selectedPickup.value = pickup;
  completeForm.value.actual_parcels_collected = pickup.estimated_parcels || 1;
  completeForm.value.create_inbound_parcels = true;
  showCompleteModal.value = true;
};

const submitCompletePickup = async () => {
  processing.value = true;
  actionError.value = '';

  try {
    await axios.post(`/pickup-requests/${selectedPickup.value.id}/complete`, completeForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    actionMessage.value = `Pickup ${selectedPickup.value.request_code} completed and parcels added to inbound queue!`;
    showCompleteModal.value = false;
    fetchPickups(pagination.value.current_page);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to mark completed.';
  } finally {
    processing.value = false;
  }
};

watch(activeTab, () => {
  fetchPickups(1);
});

onMounted(() => {
  fetchPickups(1);
  fetchHubs();
  loadProvinces();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-7xl">
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
        <div>
          <div class="flex items-center gap-2">
            <PackagePlus class="h-6 w-6 text-teal-600" />
            <h1 class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl">
              Seller Parcel Pickups
            </h1>
          </div>
          <p class="mt-1 text-sm text-slate-500">
            Request, verify, assign riders, and confirm incoming parcel pickups from registered merchants and sellers.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="fetchPickups(pagination.current_page)"
            class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
          >
            <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
            Refresh
          </button>
          <button
            @click="openCreateModal"
            class="flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white shadow-md shadow-teal-700/20 hover:bg-teal-500 transition"
          >
            <Plus class="h-4 w-4" />
            Book Parcel Pickup
          </button>
        </div>
      </div>

      <!-- Alerts -->
      <div v-if="actionMessage" class="mt-6 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800">
        <CheckCircle2 class="h-5 w-5 text-emerald-600" />
        {{ actionMessage }}
      </div>

      <div v-if="actionError" class="mt-6 flex items-center gap-2 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800">
        <Ban class="h-5 w-5 text-rose-600" />
        {{ actionError }}
      </div>

      <!-- Filters & Search -->
      <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-2">
          <button
            @click="activeTab = 'all'"
            class="rounded-lg px-3.5 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeTab === 'all' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            All Requests
          </button>
          <button
            @click="activeTab = 'pending'"
            class="rounded-lg px-3.5 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeTab === 'pending' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Pending Verification
          </button>
          <button
            @click="activeTab = 'verified'"
            class="rounded-lg px-3.5 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeTab === 'verified' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Verified / Ready
          </button>
          <button
            @click="activeTab = 'assigned'"
            class="rounded-lg px-3.5 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeTab === 'assigned' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Rider Dispatched
          </button>
          <button
            @click="activeTab = 'completed'"
            class="rounded-lg px-3.5 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeTab === 'completed' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Collected & Inbound
          </button>
        </div>

        <div class="relative min-w-[280px]">
          <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <input
            v-model="searchQuery"
            @keyup.enter="fetchPickups(1)"
            type="text"
            placeholder="Search code, contact, seller..."
            class="w-full rounded-lg border border-slate-300 bg-slate-50 pl-9 pr-4 py-2 text-sm text-slate-900 focus:border-teal-500 focus:bg-white focus:outline-none"
          />
        </div>
      </div>

      <!-- Pickups Grid / Table -->
      <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
        <PickupCard
          v-for="p in pickups"
          :key="p.id"
          :pickup="p"
          @verify="openVerifyDialog"
          @assign="openAssignDialog"
          @complete="openCompleteDialog"
        />
      </div>

      <div v-if="!pickups.length && !loading" class="mt-8 rounded-xl border border-slate-200 bg-white p-12 text-center text-slate-500">
        <PackagePlus class="mx-auto h-12 w-12 text-slate-300 mb-3" />
        <p class="font-bold">No pickup requests found for this filter.</p>
        <p class="text-xs text-slate-400 mt-1">Book a new parcel pickup to schedule collection with local hubs.</p>
      </div>

      <!-- Pagination -->
      <div v-if="pickups.length" class="mt-6 flex items-center justify-between border-t border-slate-200 pt-4 text-xs text-slate-600">
        <span>Showing {{ pickups.length }} of {{ pagination.total }} pickup requests</span>
        <div class="flex gap-2">
          <button
            :disabled="pagination.current_page <= 1"
            @click="fetchPickups(pagination.current_page - 1)"
            class="rounded border border-slate-300 bg-white px-3 py-1.5 disabled:opacity-40 hover:bg-slate-50"
          >
            Previous
          </button>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchPickups(pagination.current_page + 1)"
            class="rounded border border-slate-300 bg-white px-3 py-1.5 disabled:opacity-40 hover:bg-slate-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <!-- Create Pickup Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showCreateModal = false"
    >
      <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 pb-4">
          <div>
            <h2 class="text-xl font-black text-slate-900">Book Seller Parcel Pickup</h2>
            <p class="text-xs text-slate-500 mt-0.5">Schedule a pickup from your warehouse or storefront.</p>
          </div>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="h-6 w-6" />
          </button>
        </div>

        <form @submit.prevent="submitCreatePickup" class="mt-6 space-y-4">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Contact Person *</label>
              <input v-model="createForm.contact_person" required type="text" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Contact Number *</label>
              <input v-model="createForm.contact_number" required type="tel" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-3">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Province *</label>
              <select v-model="createForm.province" @change="onProvinceChange" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm">
                <option value="" disabled>Select Province</option>
                <option v-for="prov in provinces" :key="prov.code" :value="prov.name">{{ prov.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">City / Municipality *</label>
              <select v-model="createForm.city_municipality" @change="onCityChange" required :disabled="!cities.length" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm disabled:opacity-40">
                <option value="" disabled>Select City</option>
                <option v-for="city in cities" :key="city.code" :value="city.name">{{ city.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Barangay *</label>
              <select v-model="createForm.barangay" required :disabled="!barangays.length" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm disabled:opacity-40">
                <option value="" disabled>Select Barangay</option>
                <option v-for="brgy in barangays" :key="brgy.code" :value="brgy.name">{{ brgy.name }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Street / Warehouse Address Details *</label>
            <textarea v-model="createForm.pickup_address" rows="2" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm"></textarea>
          </div>

          <div class="grid gap-4 sm:grid-cols-3">
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Pickup Date *</label>
              <input v-model="createForm.scheduled_date" type="date" required :min="new Date().toISOString().split('T')[0]" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Preferred Time Window *</label>
              <select v-model="createForm.time_slot" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm">
                <option value="morning">Morning (8:00 AM - 12:00 PM)</option>
                <option value="afternoon">Afternoon (1:00 PM - 5:00 PM)</option>
                <option value="evening">Evening (5:00 PM - 8:00 PM)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold uppercase text-slate-700">Estimated Parcels *</label>
              <input v-model.number="createForm.estimated_parcels" type="number" min="1" max="1000" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-bold" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Special Instructions (Optional)</label>
            <input v-model="createForm.special_instructions" type="text" placeholder="e.g. Ring warehouse bell at gate 2" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
          </div>

          <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
            <button type="button" @click="showCreateModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button type="submit" :disabled="processing" class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-bold text-white hover:bg-teal-500 disabled:opacity-50">
              {{ processing ? 'Submitting...' : 'Submit Pickup Request' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Verify Pickup Modal -->
    <div
      v-if="showVerifyModal && selectedPickup"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showVerifyModal = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-black text-slate-900">Verify & Approve Pickup</h2>
        <p class="text-xs text-slate-500 mt-1">Confirm location and assign the servicing logistics hub.</p>

        <form @submit.prevent="submitVerification" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Servicing Hub *</label>
            <select v-model="verifyForm.hub_id" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-semibold">
              <option v-for="h in hubs" :key="h.id" :value="h.id">{{ h.name }} ({{ h.code }})</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Verification Notes</label>
            <textarea v-model="verifyForm.verification_notes" rows="3" placeholder="e.g. Verified seller volume and address accessibility." class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm"></textarea>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="showVerifyModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button type="submit" :disabled="processing" class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-bold text-white hover:bg-teal-500 disabled:opacity-50">
              {{ processing ? 'Approving...' : 'Confirm Verification' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Rider Modal -->
    <div
      v-if="showAssignModal && selectedPickup"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showAssignModal = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-black text-slate-900">Assign Pickup Rider</h2>
        <p class="text-xs text-slate-500 mt-1">Select a rider to dispatch for pickup {{ selectedPickup.request_code }}.</p>

        <form @submit.prevent="submitAssignRider" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Available Rider *</label>
            <select v-model="assignForm.rider_id" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-semibold">
              <option :value="null" disabled>Select Rider</option>
              <option v-for="r in availableRiders" :key="r.id" :value="r.id">
                {{ r.user?.name || 'Rider #' + r.id }} ({{ r.vehicle_type }} - {{ r.plate_number || 'No plate' }}) - {{ r.status }}
              </option>
            </select>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="showAssignModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button type="submit" :disabled="processing || !assignForm.rider_id" class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-bold text-white hover:bg-blue-500 disabled:opacity-50">
              {{ processing ? 'Dispatching...' : 'Dispatch Rider' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Complete Pickup Modal -->
    <div
      v-if="showCompleteModal && selectedPickup"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showCompleteModal = false"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-black text-slate-900">Confirm Collected Parcels</h2>
        <p class="text-xs text-slate-500 mt-1">Record the parcels collected and automatically add them to the inbound intake queue.</p>

        <form @submit.prevent="submitCompletePickup" class="mt-4 space-y-4">
          <div>
            <label class="block text-xs font-bold uppercase text-slate-700">Actual Parcels Collected *</label>
            <input v-model.number="completeForm.actual_parcels_collected" type="number" min="1" max="1000" required class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm font-bold text-slate-900" />
          </div>

          <div class="flex items-center gap-2 pt-2">
            <input v-model="completeForm.create_inbound_parcels" type="checkbox" id="create-inbound" class="h-4 w-4 text-teal-600 rounded" />
            <label for="create-inbound" class="text-xs font-medium text-slate-700 cursor-pointer">
              Automatically create received AWB packages for hub sorting
            </label>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="showCompleteModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              Cancel
            </button>
            <button type="submit" :disabled="processing" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-bold text-white hover:bg-emerald-500 disabled:opacity-50">
              {{ processing ? 'Completing...' : 'Confirm & Complete' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
