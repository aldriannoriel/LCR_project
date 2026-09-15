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
  <div class="min-h-screen bg-[#f3f4f6] p-6 md:p-8">
    <div class="mx-auto max-w-7xl">
      <div class="flex flex-wrap items-center justify-between gap-4 rounded-[28px] border border-slate-200 bg-white/90 p-6 shadow-[0_20px_40px_rgba(15,23,42,0.05)] backdrop-blur-xl">
        <div>
          <div class="flex items-center gap-2">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
              <ShieldCheck class="h-5 w-5" />
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl">User Registration Approvals</h1>
          </div>
          <p class="mt-2 text-sm text-slate-500">Review registrations, approve rider applications, then assign approved riders to their delivery area.</p>
        </div>

        <div class="flex items-center gap-3"><router-link to="/fleet" class="flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-teal-700"><MapPin class="h-4 w-4" /> Assign rider areas</router-link><button @click="fetchUsers(pagination.current_page)" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"><RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" /> Refresh</button></div>
      </div>

      <div v-if="actionMessage" class="mt-6 flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800"><CheckCircle2 class="h-5 w-5 text-emerald-600" /> {{ actionMessage }}</div>
      <div v-if="actionError" class="mt-6 flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800"><AlertCircle class="h-5 w-5 text-rose-600" /> {{ actionError }}</div>

      <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-[24px] border border-slate-200 bg-white p-4 shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
        <div class="flex flex-wrap gap-2">
          <button @click="activeStatus = 'pending'" class="rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] transition" :class="activeStatus === 'pending' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Pending Verification</button>
          <button @click="activeStatus = 'approved'" class="rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] transition" :class="activeStatus === 'approved' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Approved</button>
          <button @click="activeStatus = 'rejected'" class="rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] transition" :class="activeStatus === 'rejected' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">Rejected</button>
          <button @click="activeStatus = 'all'" class="rounded-xl px-4 py-2 text-xs font-bold uppercase tracking-[0.2em] transition" :class="activeStatus === 'all' ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">All Accounts</button>
        </div>

        <div class="relative min-w-[280px]">
          <Search class="absolute left-3 top-2.5 h-4 w-4 text-slate-400" />
          <input v-model="searchQuery" @keyup.enter="fetchUsers(1)" type="text" placeholder="Search by name, email, or business..." class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-4 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100" />
        </div>
      </div>

      <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-[0_18px_40px_rgba(15,23,42,0.04)]">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-[0.2em] text-slate-500">
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
              <tr v-for="u in users" :key="u.id" class="transition hover:bg-slate-50/70">
                <td class="px-5 py-4">
                  <div class="font-bold text-slate-900">{{ u.first_name ? `${u.first_name} ${u.middle_initial || ''} ${u.last_name}` : u.name }}</div>
                  <div v-if="u.business_name" class="mt-0.5 flex items-center gap-1 text-xs text-slate-500"><Building class="h-3 w-3" /><span>{{ u.business_name }}</span></div>
                </td>
                <td class="px-5 py-4">
                  <div class="font-medium text-slate-700">{{ u.email }}</div>
                  <div class="mt-0.5 text-xs text-slate-500">{{ u.phone_number || '—' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="font-semibold text-slate-800">{{ u.age ? `${u.age} yrs old` : '—' }}</div>
                  <div class="text-xs text-slate-400">{{ u.birthdate || '—' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="text-slate-700">{{ u.city_municipality || '—' }}</div>
                  <div class="text-xs text-slate-400">{{ u.province || '—' }}</div>
                </td>
                <td class="px-5 py-4">
                  <div class="flex items-center gap-2">
                    <span v-if="u.id_document_path" class="inline-flex items-center gap-1 rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700"><FileText class="h-3 w-3" /> ID</span>
                    <span v-if="u.business_permit_path" class="inline-flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700"><Building class="h-3 w-3" /> Permit</span>
                    <span v-if="!u.id_document_path && !u.business_permit_path" class="text-xs text-slate-400">None</span>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span v-if="u.approval_status === 'approved'" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800"><CheckCircle2 class="h-3 w-3" /> Approved</span>
                  <span v-else-if="u.approval_status === 'rejected'" class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-800"><XCircle class="h-3 w-3" /> Rejected</span>
                  <span v-else class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800"><Clock class="h-3 w-3" /> Pending Review</span>
                </td>
                <td class="space-x-2 px-5 py-4 text-right">
                  <button @click="openPreviewModal(u)" class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-100"><Eye class="h-3.5 w-3.5" /> View</button>
                  <button v-if="u.approval_status === 'pending'" @click="approveUser(u)" :disabled="processingAction" class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-emerald-600/20 transition hover:bg-emerald-500 disabled:opacity-60"><CheckCircle2 class="h-3.5 w-3.5" /> Approve</button>
                  <button v-if="u.approval_status === 'pending'" @click="openRejectDialog(u)" :disabled="processingAction" class="inline-flex items-center gap-1 rounded-xl bg-rose-600 px-3 py-1.5 text-xs font-bold text-white shadow-md shadow-rose-600/20 transition hover:bg-rose-500 disabled:opacity-60"><XCircle class="h-3.5 w-3.5" /> Reject</button>
                </td>
              </tr>
              <tr v-if="!users.length && !loading"><td colspan="7" class="p-8 text-center text-slate-500">No registrations found for current filter.</td></tr>
              <tr v-if="loading">
                <td colspan="7" class="p-8 text-center text-slate-500">
                  <div class="flex items-center justify-center gap-2"><RefreshCw class="h-5 w-5 animate-spin text-blue-600" /><span>Loading applicant records...</span></div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4 text-xs font-semibold text-slate-600">
          <span>Total: {{ pagination.total }} registrations</span>
          <div class="flex gap-2">
            <button :disabled="pagination.current_page <= 1" @click="fetchUsers(pagination.current_page - 1)" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 disabled:opacity-40 hover:bg-slate-100">Previous</button>
            <span class="px-2 py-1.5">Page {{ pagination.current_page }} of {{ pagination.last_page || 1 }}</span>
            <button :disabled="pagination.current_page >= pagination.last_page" @click="fetchUsers(pagination.current_page + 1)" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 disabled:opacity-40 hover:bg-slate-100">Next</button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showDetailModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @click.self="showDetailModal = false">
      <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-[28px] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
          <div>
            <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Registration Verification #{{ selectedUser.id }}</span>
            <h2 class="mt-2 text-2xl font-black text-slate-900">{{ selectedUser.first_name ? `${selectedUser.first_name} ${selectedUser.middle_initial || ''} ${selectedUser.last_name}` : selectedUser.name }}</h2>
          </div>
          <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600"><X class="h-6 w-6" /></button>
        </div>

        <div class="mt-6 grid gap-6 md:grid-cols-2">
          <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Applicant Details</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-slate-500">Full Name:</span><span class="font-semibold text-slate-900">{{ selectedUser.first_name ? `${selectedUser.first_name} ${selectedUser.middle_initial || ''} ${selectedUser.last_name}` : selectedUser.name }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Sex:</span><span class="font-semibold text-slate-900 capitalize">{{ selectedUser.sex || '—' }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Birthday & Age:</span><span class="font-semibold text-slate-900">{{ selectedUser.birthdate || '—' }} ({{ selectedUser.age || '—' }} yrs old)</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Email:</span><span class="font-semibold text-slate-900">{{ selectedUser.email }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Phone:</span><span class="font-semibold text-slate-900">{{ selectedUser.phone_number || '—' }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Business / Entity:</span><span class="font-semibold text-slate-900">{{ selectedUser.business_name || 'Individual Shipper' }}</span></div>
            </div>
          </div>

          <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Delivery / Billing Address</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-slate-500">Province:</span><span class="font-semibold text-slate-900">{{ selectedUser.province || '—' }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">City / Municipality:</span><span class="font-semibold text-slate-900">{{ selectedUser.city_municipality || '—' }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">Barangay:</span><span class="font-semibold text-slate-900">{{ selectedUser.barangay || '—' }}</span></div>
              <div class="border-t border-slate-200 pt-2"><span class="text-xs text-slate-500">Street Details:</span><p class="mt-1 font-medium text-slate-800">{{ selectedUser.street_address || '—' }}</p></div>
            </div>
          </div>
        </div>

        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
          <h3 class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Submitted Documents</h3>
          <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><FileText class="h-5 w-5 text-blue-600" /><span class="text-sm font-bold text-slate-900">Primary Government ID</span></div>
                <button v-if="selectedUser.id_document_path" @click="viewDocumentInNewTab(selectedUser.id, 'id')" class="flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700"><span>Open Full</span><ExternalLink class="h-3 w-3" /></button>
              </div>
              <p class="mt-2 text-xs text-slate-500">Path: {{ selectedUser.id_document_path || 'No ID on file' }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><Building class="h-5 w-5 text-indigo-600" /><span class="text-sm font-bold text-slate-900">Business / DTI Permit</span></div>
                <button v-if="selectedUser.business_permit_path" @click="viewDocumentInNewTab(selectedUser.id, 'permit')" class="flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-700"><span>Open Full</span><ExternalLink class="h-3 w-3" /></button>
              </div>
              <p class="mt-2 text-xs text-slate-500">Path: {{ selectedUser.business_permit_path || 'None uploaded' }}</p>
            </div>
          </div>
        </div>

        <div v-if="selectedUser.rejection_reason" class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-800"><strong>Prior Rejection Reason:</strong> {{ selectedUser.rejection_reason }}</div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
          <button @click="showDetailModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Close</button>
          <button v-if="selectedUser.approval_status !== 'rejected'" @click="openRejectDialog(selectedUser)" :disabled="processingAction" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50">Reject Application</button>
          <button v-if="selectedUser.approval_status !== 'approved'" @click="approveUser(selectedUser)" :disabled="processingAction" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50">Approve Application</button>
        </div>
      </div>
    </div>

    <div v-if="showRejectModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @click.self="showRejectModal = false">
      <div class="w-full max-w-lg rounded-[28px] bg-white p-6 shadow-[0_30px_80px_rgba(15,23,42,0.22)]">
        <div class="flex items-start justify-between border-b border-slate-200 pb-4">
          <div>
            <h2 class="text-xl font-black text-slate-900">Reject Registration</h2>
            <p class="mt-1 text-xs text-slate-500">Provide a clear reason that will be sent via email to {{ selectedUser.email }}.</p>
          </div>
          <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600"><X class="h-5 w-5" /></button>
        </div>

        <div class="mt-4">
          <label class="block text-xs font-bold uppercase tracking-[0.18em] text-slate-700">Reason for Rejection <span class="text-rose-500">*</span></label>
          <textarea v-model="rejectionReason" rows="4" required placeholder="e.g. The government ID is blurry and expired. Please upload a clear copy of a valid government ID or DTI certificate." class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-900 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-100"></textarea>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <button @click="showRejectModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
          <button @click="submitRejection" :disabled="processingAction || !rejectionReason.trim()" class="rounded-xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white hover:bg-rose-500 disabled:opacity-50">{{ processingAction ? 'Submitting...' : 'Confirm Rejection & Send Email' }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

