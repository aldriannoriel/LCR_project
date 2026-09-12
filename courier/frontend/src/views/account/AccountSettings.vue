<script setup>
import { ref, onMounted } from 'vue';
import { axios } from '../../lib/echo';
import { useAuthStore } from '../../stores/auth';
import { philippineAddressService } from '../../services/philippineAddressService';
import {
  User,
  Lock,
  ShieldCheck,
  Building,
  MapPin,
  Phone,
  Mail,
  CheckCircle2,
  AlertCircle,
  Save,
  Key,
  Calendar,
  FileText
} from 'lucide-vue-next';

const auth = useAuthStore();

const activeTab = ref('profile'); // 'profile', 'security', 'verification'
const loading = ref(false);
const saving = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// Profile form
const profileForm = ref({
  first_name: '',
  last_name: '',
  middle_initial: '',
  phone_number: '',
  province: '',
  city_municipality: '',
  barangay: '',
  street_address: '',
  business_name: '',
});

// Password form
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
});

// Address cascading
const provinces = ref([]);
const cities = ref([]);
const barangays = ref([]);

const loadAddressData = async () => {
  try {
    provinces.value = await philippineAddressService.getProvinces();
    if (profileForm.value.province) {
      const foundProv = provinces.value.find((p) => p.name === profileForm.value.province);
      if (foundProv) {
        cities.value = await philippineAddressService.getCities(foundProv);
        if (profileForm.value.city_municipality) {
          const foundCity = cities.value.find((c) => c.name === profileForm.value.city_municipality);
          if (foundCity) {
            barangays.value = await philippineAddressService.getBarangays(foundCity);
          }
        }
      }
    }
  } catch (_) {}
};

const onProvinceChange = async () => {
  profileForm.value.city_municipality = '';
  profileForm.value.barangay = '';
  cities.value = [];
  barangays.value = [];

  const found = provinces.value.find((p) => p.name === profileForm.value.province);
  if (found) {
    cities.value = await philippineAddressService.getCities(found);
  }
};

const onCityChange = async () => {
  profileForm.value.barangay = '';
  barangays.value = [];

  const found = cities.value.find((c) => c.name === profileForm.value.city_municipality);
  if (found) {
    barangays.value = await philippineAddressService.getBarangays(found);
  }
};

const fetchProfile = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/user/profile', {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    auth.user = res.data;

    profileForm.value = {
      first_name: res.data.first_name || '',
      last_name: res.data.last_name || '',
      middle_initial: res.data.middle_initial || '',
      phone_number: res.data.phone_number || '',
      province: res.data.province || '',
      city_municipality: res.data.city_municipality || '',
      barangay: res.data.barangay || '',
      street_address: res.data.street_address || '',
      business_name: res.data.business_name || '',
    };

    await loadAddressData();
  } catch (err) {
    errorMessage.value = 'Failed to load profile details.';
  } finally {
    loading.value = false;
  }
};

const saveProfile = async () => {
  saving.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    const res = await axios.put('/user/profile', profileForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    auth.user = res.data.user;
    successMessage.value = 'Your profile information has been updated successfully.';
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update profile.';
  } finally {
    saving.value = false;
  }
};

const changePassword = async () => {
  saving.value = true;
  successMessage.value = '';
  errorMessage.value = '';

  try {
    await axios.put('/user/password', passwordForm.value, {
      headers: { Authorization: `Bearer ${auth.token}` },
    });
    successMessage.value = 'Password changed successfully.';
    passwordForm.value = {
      current_password: '',
      password: '',
      password_confirmation: '',
    };
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Failed to update password.';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchProfile();
});
</script>

<template>
  <div class="min-h-screen bg-slate-100 p-6 md:p-8">
    <div class="mx-auto max-w-4xl">
      <!-- Header -->
      <div class="border-b border-slate-200 pb-6">
        <h1 class="text-2xl font-black tracking-tight text-slate-900 md:text-3xl">Account & Profile Settings</h1>
        <p class="mt-1 text-sm text-slate-500">Manage your profile, credentials, and verification information.</p>
      </div>

      <!-- Alerts -->
      <div v-if="successMessage" class="mt-6 flex items-center gap-2 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-semibold text-emerald-800">
        <CheckCircle2 class="h-5 w-5 text-emerald-600" />
        {{ successMessage }}
      </div>

      <div v-if="errorMessage" class="mt-6 flex items-center gap-2 rounded-xl bg-rose-50 border border-rose-200 p-4 text-sm font-semibold text-rose-800">
        <AlertCircle class="h-5 w-5 text-rose-600" />
        {{ errorMessage }}
      </div>

      <!-- Settings Layout -->
      <div class="mt-6 grid gap-6 md:grid-cols-[240px_1fr]">
        <!-- Nav Tabs -->
        <div class="space-y-1">
          <button
            @click="activeTab = 'profile'"
            class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold text-left transition"
            :class="activeTab === 'profile' ? 'bg-slate-900 text-white shadow' : 'bg-white text-slate-700 hover:bg-slate-50'"
          >
            <User class="h-4 w-4" />
            Profile Details
          </button>
          <button
            @click="activeTab = 'security'"
            class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold text-left transition"
            :class="activeTab === 'security' ? 'bg-slate-900 text-white shadow' : 'bg-white text-slate-700 hover:bg-slate-50'"
          >
            <Lock class="h-4 w-4" />
            Security & Password
          </button>
          <button
            @click="activeTab = 'verification'"
            class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-bold text-left transition"
            :class="activeTab === 'verification' ? 'bg-slate-900 text-white shadow' : 'bg-white text-slate-700 hover:bg-slate-50'"
          >
            <ShieldCheck class="h-4 w-4" />
            Verification Status
          </button>
        </div>

        <!-- Main Tab Content -->
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <!-- Profile Tab -->
          <div v-if="activeTab === 'profile'">
            <h2 class="text-base font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
              Personal & Contact Information
            </h2>

            <form @submit.prevent="saveProfile" class="mt-6 space-y-4">
              <div class="grid gap-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                  <label class="block text-xs font-bold uppercase text-slate-700">First Name *</label>
                  <input v-model="profileForm.first_name" required type="text" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-xs font-bold uppercase text-slate-700">Last Name *</label>
                  <input v-model="profileForm.last_name" required type="text" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
                </div>
                <div class="sm:col-span-1">
                  <label class="block text-xs font-bold uppercase text-slate-700">M.I.</label>
                  <input v-model="profileForm.middle_initial" type="text" maxlength="2" class="mt-1 w-full text-center uppercase rounded-lg border border-slate-300 p-2.5 text-sm" />
                </div>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-700">Email Address (Read-only)</label>
                  <input :value="auth.user?.email" readonly type="email" class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-50 p-2.5 text-sm text-slate-500 cursor-not-allowed" />
                </div>
                <div>
                  <label class="block text-xs font-bold uppercase text-slate-700">Contact Number *</label>
                  <input v-model="profileForm.phone_number" required type="tel" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold uppercase text-slate-700">Business / Merchant Name</label>
                <input v-model="profileForm.business_name" type="text" placeholder="e.g. Apex Trading" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
              </div>

              <!-- Address Section -->
              <div class="pt-4 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Address Information</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                  <div>
                    <label class="block text-xs font-bold uppercase text-slate-700">Province</label>
                    <select v-model="profileForm.province" @change="onProvinceChange" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm">
                      <option value="">Select Province</option>
                      <option v-for="prov in provinces" :key="prov.code" :value="prov.name">{{ prov.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold uppercase text-slate-700">City / Municipality</label>
                    <select v-model="profileForm.city_municipality" @change="onCityChange" :disabled="!cities.length" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm disabled:opacity-40">
                      <option value="">Select City</option>
                      <option v-for="city in cities" :key="city.code" :value="city.name">{{ city.name }}</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-bold uppercase text-slate-700">Barangay</label>
                    <select v-model="profileForm.barangay" :disabled="!barangays.length" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm disabled:opacity-40">
                      <option value="">Select Barangay</option>
                      <option v-for="brgy in barangays" :key="brgy.code" :value="brgy.name">{{ brgy.name }}</option>
                    </select>
                  </div>
                  <div class="sm:col-span-3">
                    <label class="block text-xs font-bold uppercase text-slate-700">Street / House Details</label>
                    <textarea v-model="profileForm.street_address" rows="2" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm"></textarea>
                  </div>
                </div>
              </div>

              <div class="mt-6 flex justify-end pt-4 border-t border-slate-100">
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-teal-500 shadow-sm disabled:opacity-50"
                >
                  <Save class="h-4 w-4" />
                  {{ saving ? 'Saving Changes...' : 'Save Profile' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Security Tab -->
          <div v-if="activeTab === 'security'">
            <h2 class="text-base font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
              Change Account Password
            </h2>

            <form @submit.prevent="changePassword" class="mt-6 space-y-4 max-w-md">
              <div>
                <label class="block text-xs font-bold uppercase text-slate-700">Current Password *</label>
                <input v-model="passwordForm.current_password" required type="password" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
              </div>

              <div>
                <label class="block text-xs font-bold uppercase text-slate-700">New Password * (Min. 8 characters)</label>
                <input v-model="passwordForm.password" required type="password" minlength="8" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
              </div>

              <div>
                <label class="block text-xs font-bold uppercase text-slate-700">Confirm New Password *</label>
                <input v-model="passwordForm.password_confirmation" required type="password" minlength="8" class="mt-1 w-full rounded-lg border border-slate-300 p-2.5 text-sm" />
              </div>

              <div class="pt-4">
                <button
                  type="submit"
                  :disabled="saving"
                  class="flex items-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-sm font-bold text-white hover:bg-slate-800 shadow-sm disabled:opacity-50"
                >
                  <Key class="h-4 w-4" />
                  {{ saving ? 'Updating...' : 'Update Password' }}
                </button>
              </div>
            </form>
          </div>

          <!-- Verification Tab -->
          <div v-if="activeTab === 'verification'">
            <h2 class="text-base font-bold uppercase tracking-wider text-slate-900 border-b border-slate-100 pb-3">
              Verification Credentials
            </h2>

            <div class="mt-6 space-y-4">
              <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                <div>
                  <p class="text-sm font-bold text-slate-900">Account Approval Status</p>
                  <p class="text-xs text-slate-500">Administrative approval granting operational platform access.</p>
                </div>
                <span
                  v-if="auth.user?.approval_status === 'approved'"
                  class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 px-3 py-1 rounded-full text-xs font-bold"
                >
                  <CheckCircle2 class="h-3.5 w-3.5" /> Approved
                </span>
                <span
                  v-else
                  class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-bold"
                >
                  Pending Review
                </span>
              </div>

              <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Submitted Verification Records</h3>
                <div class="flex items-center justify-between text-xs text-slate-700">
                  <span>Primary Government ID:</span>
                  <span class="font-mono">{{ auth.user?.id_document_path || 'On file' }}</span>
                </div>
                <div class="flex items-center justify-between text-xs text-slate-700">
                  <span>Business / DTI Permit:</span>
                  <span class="font-mono">{{ auth.user?.business_permit_path || 'None uploaded' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
