<script setup>
import { ref, onMounted } from 'vue';
import { useCourierStore } from '../../stores/courier';
import { useAuthStore } from '../../stores/auth';
import {
  Bike,
  CheckCircle2,
  Lock,
  LogOut,
  Save,
  User,
  X
} from 'lucide-vue-next';

const courier = useCourierStore();
const auth = useAuthStore();

const activeTab = ref('profile');
const saving = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const profileForm = ref({ first_name: '', last_name: '', phone_number: '' });
const passwordForm = ref({ current_password: '', password: '', password_confirmation: '' });

const loadProfile = async () => {
  try {
    const data = await courier.fetchProfile();
    profileForm.value = {
      first_name: data.user.first_name || '',
      last_name: data.user.last_name || '',
      phone_number: data.user.phone_number || data.rider?.phone_number || '',
    };
  } catch (e) {
    errorMsg.value = 'Failed to load profile.';
  }
};

const saveProfile = async () => {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    await courier.updateProfile(profileForm.value);
    auth.user = { ...auth.user, ...profileForm.value, name: `${profileForm.value.first_name} ${profileForm.value.last_name}` };
    successMsg.value = 'Profile updated successfully.';
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to update profile.';
  } finally {
    saving.value = false;
  }
};

const changePassword = async () => {
  if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    errorMsg.value = 'New passwords do not match.';
    return;
  }
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    await courier.changePassword(passwordForm.value);
    successMsg.value = 'Password changed successfully.';
    passwordForm.value = { current_password: '', password: '', password_confirmation: '' };
  } catch (e) {
    errorMsg.value = e.response?.data?.message || 'Failed to change password.';
  } finally {
    saving.value = false;
  }
};

const tabs = [
  { key: 'profile', label: 'Profile', icon: User },
  { key: 'security', label: 'Security', icon: Lock },
  { key: 'vehicle', label: 'Vehicle', icon: Bike },
];

onMounted(loadProfile);
</script>

<template>
  <div class="p-4 md:p-6 space-y-4">
    <!-- Header -->
    <div>
      <h1 class="text-xl font-black text-slate-900">Settings</h1>
      <p class="text-xs text-slate-500 mt-0.5">Manage your account and preferences</p>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 bg-white rounded-xl border border-slate-200 p-1.5">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-xs font-bold transition-all"
        :class="activeTab === tab.key ? 'bg-teal-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
      >
        <component :is="tab.icon" class="h-4 w-4" />
        {{ tab.label }}
      </button>
    </div>

    <!-- Success/Error -->
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

    <!-- Profile Tab -->
    <div v-if="activeTab === 'profile'" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-bold text-slate-900">Personal Information</h2>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="text-xs font-bold text-slate-600">First Name</label>
          <input v-model="profileForm.first_name" type="text" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
        </div>
        <div>
          <label class="text-xs font-bold text-slate-600">Last Name</label>
          <input v-model="profileForm.last_name" type="text" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
        </div>
      </div>

      <div>
        <label class="text-xs font-bold text-slate-600">Phone Number</label>
        <input v-model="profileForm.phone_number" type="tel" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
      </div>

      <button
        @click="saveProfile"
        :disabled="saving"
        class="w-full flex items-center justify-center gap-2 bg-teal-600 text-white font-bold py-3 rounded-xl hover:bg-teal-500 disabled:opacity-50 transition"
      >
        <Save class="h-4 w-4" />
        {{ saving ? 'Saving...' : 'Save Changes' }}
      </button>
    </div>

    <!-- Security Tab -->
    <div v-if="activeTab === 'security'" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-bold text-slate-900">Change Password</h2>

      <div>
        <label class="text-xs font-bold text-slate-600">Current Password</label>
        <input v-model="passwordForm.current_password" type="password" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
      </div>
      <div>
        <label class="text-xs font-bold text-slate-600">New Password</label>
        <input v-model="passwordForm.password" type="password" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
      </div>
      <div>
        <label class="text-xs font-bold text-slate-600">Confirm New Password</label>
        <input v-model="passwordForm.password_confirmation" type="password" class="mt-1 w-full border border-slate-300 rounded-lg p-3 text-sm" />
      </div>

      <button
        @click="changePassword"
        :disabled="saving"
        class="w-full flex items-center justify-center gap-2 bg-slate-900 text-white font-bold py-3 rounded-xl hover:bg-slate-800 disabled:opacity-50 transition"
      >
        <Lock class="h-4 w-4" />
        {{ saving ? 'Updating...' : 'Update Password' }}
      </button>
    </div>

    <!-- Vehicle Tab -->
    <div v-if="activeTab === 'vehicle'" class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
      <h2 class="font-bold text-slate-900">Vehicle Information</h2>

      <div class="bg-slate-50 rounded-xl p-4 space-y-3">
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-500">Vehicle Type</span>
          <span class="font-bold text-slate-900 capitalize">{{ auth.user?.rider?.vehicle_type || '—' }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-500">Plate Number</span>
          <span class="font-bold text-slate-900 font-mono">{{ auth.user?.rider?.plate_number || '—' }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-500">License Number</span>
          <span class="font-bold text-slate-900 font-mono">{{ auth.user?.rider?.license_number || '—' }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-slate-500">Hub</span>
          <span class="font-bold text-slate-900">{{ auth.user?.rider?.hub?.name || '—' }}</span>
        </div>
      </div>

      <p class="text-xs text-slate-500 text-center">Contact support to update vehicle information.</p>
    </div>

    <!-- Account Info -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
      <h2 class="font-bold text-slate-900">Account</h2>
      <div class="flex items-center justify-between text-sm">
        <span class="text-slate-500">Email</span>
        <span class="font-semibold text-slate-900">{{ auth.user?.email }}</span>
      </div>
      <div class="flex items-center justify-between text-sm">
        <span class="text-slate-500">Status</span>
        <span class="font-bold text-emerald-600">Active</span>
      </div>
    </div>
  </div>
</template>
