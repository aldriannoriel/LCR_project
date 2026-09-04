<script setup>
import { ref, onMounted, watch } from 'vue';
import { axios } from '../../lib/echo';
import { useAuthStore } from '../../stores/auth';
import {
  ShieldCheck,
  CheckCircle2,
  XCircle,
  Clock,
  Eye,
  Search,
  RefreshCw,
  FileText,
  Building,
  User,
  MapPin,
  Calendar,
  Phone,
  Mail,
  AlertCircle,
  ExternalLink,
  X
} from 'lucide-vue-next';

const auth = useAuthStore();

const users = ref([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});
const activeStatus = ref('pending');
const searchQuery = ref('');
const loading = ref(false);

const selectedUser = ref(null);
const showDetailModal = ref(false);
const showRejectModal = ref(false);
const rejectionReason = ref('');
const processingAction = ref(false);
const actionMessage = ref('');
const actionError = ref('');

const fetchUsers = async (page = 1) => {
  loading.value = true;
  actionMessage.value = '';
  actionError.value = '';

  try {
    const response = await axios.get('/admin/users', {
      params: {
        status: activeStatus.value,
        search: searchQuery.value || undefined,
        page,
        per_page: 10,
      },
      headers: {
        Authorization: `Bearer ${auth.token}`,
      },
    });

    users.value = response.data.data;
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      total: response.data.total,
    };
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to load user registrations.';
  } finally {
    loading.value = false;
  }
};

const openPreviewModal = (user) => {
  selectedUser.value = user;
  showDetailModal.value = true;
};

const openRejectDialog = (user) => {
  selectedUser.value = user;
  rejectionReason.value = '';
  showRejectModal.value = true;
};

const approveUser = async (user) => {
  if (!confirm(`Are you sure you want to approve ${user.name || user.first_name}? An approval email will be sent immediately.`)) {
    return;
  }

  processingAction.value = true;
  actionError.value = '';

  try {
    await axios.post(`/admin/users/${user.id}/approve`, {}, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });

    actionMessage.value = `Account for ${user.name || user.first_name} has been approved!`;
    showDetailModal.value = false;
    await fetchUsers(pagination.value.current_page);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to approve registration.';
  } finally {
    processingAction.value = false;
  }
};

const submitRejection = async () => {
  if (!rejectionReason.value.trim()) {
    actionError.value = 'Please provide a clear reason for the rejection.';
    return;
  }

  processingAction.value = true;
  actionError.value = '';

  try {
    await axios.post(`/admin/users/${selectedUser.value.id}/reject`, {
      reason: rejectionReason.value,
    }, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });

    actionMessage.value = `Account for ${selectedUser.value.name || selectedUser.value.first_name} has been rejected. Notification sent.`;
    showRejectModal.value = false;
    showDetailModal.value = false;
    await fetchUsers(pagination.value.current_page);
  } catch (err) {
    actionError.value = err.response?.data?.message || 'Failed to reject registration.';
  } finally {
    processingAction.value = false;
  }
};

const getDocumentUrl = (userId, type) => {
  return `http://localhost:8000/api/admin/users/${userId}/documents/${type}`;
};

const viewDocumentInNewTab = async (userId, type) => {
  try {
    const response = await axios.get(`/admin/users/${userId}/documents/${type}`, {
      responseType: 'blob',
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    const fileUrl = URL.createObjectURL(response.data);
    window.open(fileUrl, '_blank');
  } catch (err) {
    alert('Unable to load document. File might be missing or inaccessible.');
  }
};

watch(activeStatus, () => {
  pagination.value.current_page = 1;
  fetchUsers(1);
});

onMounted(() => {
  fetchUsers(1);
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-7xl">
      <!-- Page Header -->
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
        <div>
          <div class="flex items-center gap-2">
            <ShieldCheck class="h-6 w-6 text-teal-600" />
            <h1 class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl">
              User Registration Approvals
            </h1>
          </div>
          <p class="mt-1 text-sm text-slate-500">
            Review identity documents, permits, and verify new merchant and customer accounts.
          </p>
        </div>

        <button
          @click="fetchUsers(pagination.current_page)"
          class="flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition"
        >
          <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
          Refresh
        </button>
      </div>

      <!-- Alerts -->
      <div v-if="actionMessage" class="mt-6 flex items-center gap-2 rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800">
        <CheckCircle2 class="h-5 w-5 text-emerald-600" />
        {{ actionMessage }}
      </div>

      <div v-if="actionError" class="mt-6 flex items-center gap-2 rounded-lg bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800">
        <AlertCircle class="h-5 w-5 text-rose-600" />
        {{ actionError }}
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap gap-2">
          <button
            @click="activeStatus = 'pending'"
            class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeStatus === 'pending' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Pending Verification
          </button>
          <button
            @click="activeStatus = 'approved'"
            class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeStatus === 'approved' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Approved
          </button>
          <button
            @click="activeStatus = 'rejected'"
            class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeStatus === 'rejected' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            Rejected
          </button>
          <button
            @click="activeStatus = 'all'"
            class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wider transition"
            :class="activeStatus === 'all' ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
          >
            All Accounts
          </button>
        </div>

        <div class="relative min-w-[280px]">
          <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <input
            v-model="searchQuery"
            @keyup.enter="fetchUsers(1)"
            type="text"
            placeholder="Search by name, email, or business..."
            class="w-full rounded-lg border border-slate-300 bg-slate-50 pl-9 pr-4 py-2 text-sm text-slate-900 focus:border-teal-500 focus:bg-white focus:outline-none"
          />
        </div>
      </div>

      <!-- Users Datatable -->
      <div class="mt-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
              <tr>
                <th class="px-5 py-3.5">Applicant Details</th>
                <th class="px-5 py-3.5">Contact Info</th>
                <th class="px-5 py-3.5">Age / Birthday</th>
                <th class="px-5 py-3.5">Location</th>
                <th class="px-5 py-3.5">Documents</th>
                <th class="px-5 py-3.5">Status</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in users" :key="u.id" class="hover:bg-slate-50/80 transition">
                <td class="px-5 py-4">
                  <div class="font-bold text-slate-900">{{ u.first_name ? `${u.first_name} ${u.middle_initial || ''} ${u.last_name}` : u.name }}</div>
                  <div v-if="u.business_name" class="flex items-center gap-1 text-xs text-slate-500 mt-0.5">
                    <Building class="h-3 w-3" />
                    <span>{{ u.business_name }}</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <div class="text-slate-700 font-medium">{{ u.email }}</div>
                  <div class="text-xs text-slate-500 mt-0.5">{{ u.phone_number || '—' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="text-slate-800 font-semibold">{{ u.age ? `${u.age} yrs old` : '—' }}</div>
                  <div class="text-xs text-slate-400">{{ u.birthdate || '—' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="text-slate-700">{{ u.city_municipality || '—' }}</div>
                  <div class="text-xs text-slate-400">{{ u.province || '—' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2">
                    <span
                      v-if="u.id_document_path"
                      class="inline-flex items-center gap-1 rounded bg-teal-50 px-2 py-0.5 text-xs font-semibold text-teal-700 border border-teal-200"
                    >
                      <FileText class="h-3 w-3" /> ID
                    </span>
                    <span
                      v-if="u.business_permit_path"
                      class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 border border-blue-200"
                    >
                      <Building class="h-3 w-3" /> Permit
                    </span>
                    <span v-if="!u.id_document_path && !u.business_permit_path" class="text-xs text-slate-400">None</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span
                    v-if="u.approval_status === 'approved'"
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800"
                  >
                    <CheckCircle2 class="h-3 w-3" /> Approved
                  </span>
                  <span
                    v-else-if="u.approval_status === 'rejected'"
                    class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800"
                  >
                    <XCircle class="h-3 w-3" /> Rejected
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800"
                  >
                    <Clock class="h-3 w-3" /> Pending Review
                  </span>
                </td>
                <td class="px-5 py-4 text-right space-x-2">
                  <button
                    @click="openPreviewModal(u)"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 shadow-sm"
                  >
                    <Eye class="h-3.5 w-3.5" /> View
                  </button>
                  <button
                    v-if="u.approval_status === 'pending'"
                    @click="approveUser(u)"
                    :disabled="processingAction"
                    class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-500 shadow-sm disabled:opacity-50"
                  >
                    <CheckCircle2 class="h-3.5 w-3.5" /> Approve
                  </button>
                  <button
                    v-if="u.approval_status === 'pending'"
                    @click="openRejectDialog(u)"
                    :disabled="processingAction"
                    class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-500 shadow-sm disabled:opacity-50"
                  >
                    <XCircle class="h-3.5 w-3.5" /> Reject
                  </button>
                </td>
              </tr>
              <tr v-if="!users.length && !loading">
                <td colspan="7" class="p-8 text-center text-slate-500">
                  No registrations found for current filter.
                </td>
              </tr>
              <tr v-if="loading">
                <td colspan="7" class="p-8 text-center text-slate-500">
                  <div class="flex items-center justify-center gap-2">
                    <RefreshCw class="h-5 w-5 animate-spin text-teal-600" />
                    <span>Loading applicant records...</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Footer -->
        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4 text-xs font-semibold text-slate-600">
          <span>Total: {{ pagination.total }} registrations</span>
          <div class="flex gap-2">
            <button
              :disabled="pagination.current_page <= 1"
              @click="fetchUsers(pagination.current_page - 1)"
              class="rounded border border-slate-300 bg-white px-3 py-1.5 disabled:opacity-40 hover:bg-slate-50"
            >
              Previous
            </button>
            <span class="px-2 py-1.5">Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span>
            <button
              :disabled="pagination.current_page >= pagination.last_page"
              @click="fetchUsers(pagination.current_page + 1)"
              class="rounded border border-slate-300 bg-white px-3 py-1.5 disabled:opacity-40 hover:bg-slate-50"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Review & Preview Modal -->
    <div
      v-if="showDetailModal && selectedUser"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showDetailModal = false"
    >
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
          <div>
            <span class="inline-flex rounded-full bg-teal-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-teal-700 border border-teal-200">
              Registration Verification #{{ selectedUser.id }}
            </span>
            <h2 class="mt-2 text-2xl font-black text-slate-900">
              {{ selectedUser.first_name ? `${selectedUser.first_name} ${selectedUser.middle_initial || ''} ${selectedUser.last_name}` : selectedUser.name }}
            </h2>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="h-6 w-6" />
          </button>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
          <!-- Personal & Contact Info -->
          <div class="space-y-4 rounded-xl bg-slate-50 p-4 border border-slate-200">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant Details</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">Full Name:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.first_name ? `${selectedUser.first_name} ${selectedUser.middle_initial || ''} ${selectedUser.last_name}` : selectedUser.name }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Sex:</span>
                <span class="font-semibold text-slate-900 capitalize">{{ selectedUser.sex || '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Birthday & Age:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.birthdate || '—' }} ({{ selectedUser.age || '—' }} yrs old)</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Email:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.email }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Phone:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.phone_number || '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Business / Entity:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.business_name || 'Individual Shipper' }}</span>
              </div>
            </div>
          </div>

          <!-- Address -->
          <div class="space-y-4 rounded-xl bg-slate-50 p-4 border border-slate-200">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Delivery / Billing Address</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-slate-500">Province:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.province || '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">City / Municipality:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.city_municipality || '—' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-slate-500">Barangay:</span>
                <span class="font-semibold text-slate-900">{{ selectedUser.barangay || '—' }}</span>
              </div>
              <div class="pt-2 border-t border-slate-200">
                <span class="text-slate-500 text-xs">Street Details:</span>
                <p class="font-medium text-slate-800 mt-1">{{ selectedUser.street_address || '—' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Document Verification Box -->
        <div class="mt-6 rounded-xl border border-slate-200 p-4 bg-slate-50">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Submitted Documents</h3>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <!-- Government ID -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <FileText class="h-5 w-5 text-teal-600" />
                  <span class="font-bold text-sm text-slate-900">Primary Government ID</span>
                </div>
                <button
                  v-if="selectedUser.id_document_path"
                  @click="viewDocumentInNewTab(selectedUser.id, 'id')"
                  class="flex items-center gap-1 text-xs font-bold text-teal-600 hover:text-teal-700"
                >
                  <span>Open Full</span>
                  <ExternalLink class="h-3 w-3" />
                </button>
              </div>
              <p class="mt-2 text-xs text-slate-500">Path: {{ selectedUser.id_document_path || 'No ID on file' }}</p>
            </div>

            <!-- Business Permit -->
            <div class="rounded-lg border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <Building class="h-5 w-5 text-blue-600" />
                  <span class="font-bold text-sm text-slate-900">Business / DTI Permit</span>
                </div>
                <button
                  v-if="selectedUser.business_permit_path"
                  @click="viewDocumentInNewTab(selectedUser.id, 'permit')"
                  class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700"
                >
                  <span>Open Full</span>
                  <ExternalLink class="h-3 w-3" />
                </button>
              </div>
              <p class="mt-2 text-xs text-slate-500">Path: {{ selectedUser.business_permit_path || 'None uploaded' }}</p>
            </div>
          </div>
        </div>

        <div v-if="selectedUser.rejection_reason" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800">
          <strong>Prior Rejection Reason:</strong> {{ selectedUser.rejection_reason }}
        </div>

        <!-- Modal Actions -->
        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button
            @click="showDetailModal = false"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Close
          </button>
          <button
            v-if="selectedUser.approval_status !== 'rejected'"
            @click="openRejectDialog(selectedUser)"
            :disabled="processingAction"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
          >
            Reject Application
          </button>
          <button
            v-if="selectedUser.approval_status !== 'approved'"
            @click="approveUser(selectedUser)"
            :disabled="processingAction"
            class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-500 disabled:opacity-50"
          >
            Approve Application
          </button>
        </div>
      </div>
    </div>

    <!-- Rejection Reason Modal -->
    <div
      v-if="showRejectModal && selectedUser"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"
      @click.self="showRejectModal = false"
    >
      <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
          <div>
            <h2 class="text-xl font-black text-slate-900">Reject Registration</h2>
            <p class="text-xs text-slate-500 mt-1">
              Provide a clear reason that will be sent via email to {{ selectedUser.email }}.
            </p>
          </div>
          <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-4">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
            Reason for Rejection <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="rejectionReason"
            rows="4"
            required
            placeholder="e.g. The government ID is blurry and expired. Please upload a clear copy of a valid government ID or DTI certificate."
            class="mt-2 w-full rounded-lg border border-slate-300 p-3 text-sm text-slate-900 focus:border-rose-500 focus:outline-none"
          ></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button
            @click="showRejectModal = false"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Cancel
          </button>
          <button
            @click="submitRejection"
            :disabled="processingAction || !rejectionReason.trim()"
            class="rounded-lg bg-rose-600 px-5 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50"
          >
            {{ processingAction ? 'Submitting...' : 'Confirm Rejection & Send Email' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

