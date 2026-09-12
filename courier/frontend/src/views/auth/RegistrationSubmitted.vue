<script setup>
import { useRoute, useRouter } from 'vue-router';
import { ArrowRight } from 'lucide-vue-next';
import RegistrationSummary from '../../components/auth/RegistrationSummary.vue';

const route = useRoute();
const router = useRouter();

const registeredEmail = route.query.email || 'your registered email';
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[radial-gradient(circle_at_top_left,_rgba(37,99,235,0.12),_transparent_24%),linear-gradient(180deg,#f3f4f6_0%,#eef2ff_100%)] px-4 py-12 text-slate-900 selection:bg-blue-500 selection:text-white">
    <div class="w-full max-w-lg rounded-[28px] border border-slate-200 bg-white/90 p-8 text-center shadow-[0_24px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:p-10">
      <RegistrationSummary :registered-email="registeredEmail" />

      <div class="mt-8 space-y-3">
        <button
          @click="router.push('/login')"
          class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 text-sm font-bold uppercase tracking-[0.18em] text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500"
        >
          <span>Return to Sign In</span>
          <ArrowRight class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>

