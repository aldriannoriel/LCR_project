<script setup>
import { useRoute, useRouter } from 'vue-router';
import { CheckCircle2, Mail, Clock, ArrowRight, ShieldCheck } from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();

const registeredEmail = route.query.email || 'your registered email';
const applicantName = route.query.name || 'Valued Applicant';
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-950 px-4 py-12 text-slate-100 selection:bg-teal-500 selection:text-white">
    <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900/90 p-8 text-center shadow-2xl backdrop-blur sm:p-10">
      <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-teal-500/20 text-teal-400 ring-8 ring-teal-500/10">
        <CheckCircle2 class="h-10 w-10" />
      </div>

      <h1 class="mt-6 text-2xl font-black tracking-tight sm:text-3xl">Registration Submitted</h1>
      
      <div class="mt-4 rounded-xl border border-teal-500/20 bg-teal-500/5 p-4 text-sm text-teal-200">
        <p class="font-semibold">
          After submitting your registration, please wait for the administrator's approval, which will be sent to your email.
        </p>
      </div>

      <div class="mt-6 space-y-3 text-left text-xs text-slate-400 bg-slate-800/60 p-4 rounded-xl border border-white/5">
        <div class="flex items-center gap-2 text-slate-300">
          <Mail class="h-4 w-4 text-teal-400" />
          <span>Notification will be sent to: <strong class="text-white">{{ registeredEmail }}</strong></span>
        </div>
        <div class="flex items-center gap-2 text-slate-300">
          <Clock class="h-4 w-4 text-amber-400" />
          <span>Typical verification time: <strong class="text-white">Within 24-48 business hours</strong></span>
        </div>
        <div class="flex items-center gap-2 text-slate-300">
          <ShieldCheck class="h-4 w-4 text-blue-400" />
          <span>Submitted ID & permit files are stored securely.</span>
        </div>
      </div>

      <div class="mt-8 space-y-3">
        <button
          @click="router.push('/login')"
          class="flex w-full items-center justify-center gap-2 rounded-xl bg-teal-500 py-3 text-sm font-bold uppercase tracking-wider text-slate-950 shadow-lg shadow-teal-500/20 transition hover:bg-teal-400"
        >
          <span>Return to Sign In</span>
          <ArrowRight class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>

