<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { Lock, Mail, ShieldAlert, UserPlus, ArrowRight, Building2, Bike } from 'lucide-vue-next';

const email = ref('');
const password = ref('');
const errorMessage = ref('');
const isPendingApproval = ref(false);
const loading = ref(false);

const authStore = useAuthStore();
const router = useRouter();

// Quick demo accounts
const demoAccounts = [
  {
    label: 'Admin',
    email: 'admin@logistics.local',
    password: 'password123',
    icon: Building2,
    desc: 'Dashboard',
    gradient: 'from-rose-500 to-pink-600',
    bg: 'bg-rose-50',
    text: 'text-rose-600',
    border: 'border-rose-200',
  },
  {
    label: 'Courier',
    email: 'courier@logistics.local',
    password: 'password123',
    icon: Bike,
    desc: 'Deliveries',
    gradient: 'from-blue-500 to-indigo-600',
    bg: 'bg-blue-50',
    text: 'text-blue-600',
    border: 'border-blue-200',
  },
];

const handleLogin = async () => {
  errorMessage.value = '';
  isPendingApproval.value = false;
  loading.value = true;

  try {
    const redirect = await authStore.login({ email: email.value, password: password.value });
    router.push(redirect || '/dashboard');
  } catch (err) {
    if (err.response?.status === 403) {
      isPendingApproval.value = true;
      errorMessage.value = err.response.data.message || 'Your registration is currently pending administrator approval.';
    } else {
      errorMessage.value = err.response?.data?.message || 'Invalid credentials. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};

const quickLogin = (account) => {
  email.value = account.email;
  password.value = account.password;
  handleLogin();
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-950 px-4 py-12 text-slate-100 selection:bg-teal-500 selection:text-white">
    <div class="max-w-md w-full rounded-2xl border border-white/10 bg-slate-900/90 p-8 shadow-2xl backdrop-blur">

      <!-- Header -->
      <div class="text-center mb-6">
        <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-teal-500/10 text-teal-400 mb-3 border border-teal-500/20">
          <Lock class="h-6 w-6" />
        </div>
        <h2 class="text-2xl font-black tracking-tight text-white">Logistics OS</h2>
        <p class="text-sm text-slate-400 mt-1">Sign in to your account</p>
      </div>

      <!-- Quick Demo Buttons -->
      <div class="mb-6 grid grid-cols-3 gap-2">
        <button
          v-for="demo in demoAccounts"
          :key="demo.label"
          @click="quickLogin(demo)"
          class="flex flex-col items-center gap-1.5 rounded-xl border p-3 transition hover:scale-[1.02] active:scale-[0.98]"
          :class="[demo.bg, demo.border]"
        >
          <div class="h-9 w-9 rounded-lg bg-gradient-to-br p-0.5" :class="demo.gradient">
            <div class="h-full w-full rounded-md flex items-center justify-center" :class="demo.bg">
              <component :is="demo.icon" class="h-5 w-5" :class="demo.text" />
            </div>
          </div>
          <span class="text-[11px] font-bold text-slate-700">{{ demo.label }}</span>
          <span class="text-[10px] text-slate-500">{{ demo.desc }}</span>
        </button>
      </div>

      <!-- Divider -->
      <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-slate-700"></div>
        </div>
        <div class="relative flex justify-center">
          <span class="bg-slate-900 px-4 text-xs text-slate-500">or sign in manually</span>
        </div>
      </div>

      <!-- Error / Pending Notice -->
      <div
        v-if="errorMessage"
        class="mb-6 flex items-start gap-3 rounded-xl p-4 text-sm"
        :class="isPendingApproval ? 'border border-amber-500/30 bg-amber-500/10 text-amber-200' : 'border border-red-500/30 bg-red-500/10 text-red-300'"
      >
        <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0" :class="isPendingApproval ? 'text-amber-400' : 'text-red-400'" />
        <div>
          <p class="font-semibold">{{ isPendingApproval ? 'Approval Required' : 'Authentication Error' }}</p>
          <p class="mt-1 text-xs leading-relaxed">{{ errorMessage }}</p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Email Address</label>
          <div class="relative mt-1.5">
            <Mail class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
            <input
              v-model="email"
              type="email"
              required
              placeholder="user@logistics.local"
              class="w-full rounded-lg border border-slate-700 bg-slate-800/80 pl-9 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password</label>
          <div class="relative mt-1.5">
            <Lock class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
            <input
              v-model="password"
              type="password"
              required
              placeholder="••••••••"
              class="w-full rounded-lg border border-slate-700 bg-slate-800/80 pl-9 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full flex items-center justify-center gap-2 rounded-xl bg-teal-500 py-3 text-sm font-bold uppercase tracking-wider text-slate-950 shadow-lg shadow-teal-500/20 hover:bg-teal-400 transition disabled:opacity-50 mt-2"
        >
          <span>{{ loading ? 'Signing In...' : 'Sign In' }}</span>
          <ArrowRight v-if="!loading" class="h-4 w-4" />
        </button>
      </form>

      <div class="mt-6 border-t border-white/10 pt-6 text-center">
        <p class="text-xs text-slate-400">
          New to Logistics OS?
          <router-link to="/register" class="font-bold text-teal-400 hover:underline inline-flex items-center gap-1 ml-1">
            <UserPlus class="h-3.5 w-3.5" />
            Register your account
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>