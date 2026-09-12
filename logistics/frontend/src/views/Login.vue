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

const demoAccounts = [
  {
    label: 'Admin',
    email: 'admin@logistics.local',
    password: 'password123',
    icon: Building2,
    desc: 'Dashboard',
    gradient: 'from-blue-500 to-indigo-600',
    bg: 'bg-blue-50',
    text: 'text-blue-700',
    border: 'border-blue-200',
  },
  {
    label: 'Courier',
    email: 'courier@logistics.local',
    password: 'password123',
    icon: Bike,
    desc: 'Deliveries',
    gradient: 'from-sky-500 to-blue-600',
    bg: 'bg-sky-50',
    text: 'text-sky-700',
    border: 'border-sky-200',
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
  <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.12),_transparent_24%),linear-gradient(180deg,#f3f4f6_0%,#eef2ff_100%)] px-4 py-12 text-slate-900 selection:bg-blue-500 selection:text-white">
    <div class="w-full max-w-md rounded-[28px] border border-slate-200 bg-white/90 p-8 shadow-[0_24px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl">
      <div class="mb-6 text-center">
        <div class="mb-3 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20">
          <Lock class="h-6 w-6" />
        </div>
        <h2 class="text-2xl font-black tracking-tight text-slate-900">Logistics OS</h2>
        <p class="mt-1 text-sm text-slate-500">Sign in to your account</p>
      </div>

      <div class="mb-6 grid grid-cols-2 gap-2">
        <button
          v-for="demo in demoAccounts"
          :key="demo.label"
          @click="quickLogin(demo)"
          class="flex flex-col items-center gap-1.5 rounded-2xl border border-slate-200 bg-slate-50 p-3 transition hover:-translate-y-0.5 hover:bg-white"
        >
          <div class="h-10 w-10 rounded-xl bg-gradient-to-br p-0.5" :class="demo.gradient">
            <div class="flex h-full w-full items-center justify-center rounded-lg bg-white/90" :class="demo.bg">
              <component :is="demo.icon" class="h-5 w-5" :class="demo.text" />
            </div>
          </div>
          <span class="text-[11px] font-bold text-slate-800">{{ demo.label }}</span>
          <span class="text-[10px] text-slate-500">{{ demo.desc }}</span>
        </button>
      </div>

      <div class="relative mb-6">
        <div class="absolute inset-0 flex items-center">
          <div class="w-full border-t border-slate-200"></div>
        </div>
        <div class="relative flex justify-center">
          <span class="bg-white px-4 text-xs font-medium uppercase tracking-[0.2em] text-slate-400">or sign in manually</span>
        </div>
      </div>

      <div
        v-if="errorMessage"
        class="mb-6 flex items-start gap-3 rounded-2xl p-4 text-sm"
        :class="isPendingApproval ? 'border border-amber-200 bg-amber-50 text-amber-800' : 'border border-red-200 bg-red-50 text-red-700'"
      >
        <ShieldAlert class="mt-0.5 h-5 w-5 shrink-0" :class="isPendingApproval ? 'text-amber-600' : 'text-red-600'" />
        <div>
          <p class="font-semibold">{{ isPendingApproval ? 'Approval Required' : 'Authentication Error' }}</p>
          <p class="mt-1 text-xs leading-relaxed">{{ errorMessage }}</p>
        </div>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Email Address</label>
          <div class="relative mt-1.5">
            <Mail class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
            <input
              v-model="email"
              type="email"
              required
              placeholder="user@logistics.local"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Password</label>
          <div class="relative mt-1.5">
            <Lock class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
            <input
              v-model="password"
              type="password"
              required
              placeholder="••••••••"
              class="w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100"
            />
          </div>
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500 disabled:opacity-60"
        >
          <span>{{ loading ? 'Signing In...' : 'Sign In' }}</span>
          <ArrowRight v-if="!loading" class="h-4 w-4" />
        </button>
      </form>

      <div class="mt-6 border-t border-slate-200 pt-6 text-center">
        <p class="text-xs text-slate-500">
          New to Logistics OS?
          <router-link to="/register" class="ml-1 inline-flex items-center gap-1 font-bold text-blue-600 hover:text-blue-500">
            <UserPlus class="h-3.5 w-3.5" />
            Register your account
          </router-link>
        </p>
      </div>
    </div>
  </div>
</template>