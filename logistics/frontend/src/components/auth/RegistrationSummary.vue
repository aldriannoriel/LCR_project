<script setup>
import { CheckCircle2, Clock, Mail, ShieldCheck } from 'lucide-vue-next';

defineProps({
  registeredEmail: {
    type: String,
    required: true,
  },
});
</script>

<template>
  <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600 ring-8 ring-blue-50">
    <CheckCircle2 class="h-10 w-10" />
  </div>

  <h1 class="mt-6 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Registration Submitted</h1>

  <div class="mt-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
    <p class="font-semibold">
      After submitting your registration, please wait for the administrator's approval, which will be sent to your email.
    </p>
  </div>

  <div class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-left text-xs text-slate-500">
    <div class="flex items-center gap-2 text-slate-700">
      <Mail class="h-4 w-4 text-blue-600" />
      <span>Notification will be sent to: <strong class="text-slate-900">{{ registeredEmail }}</strong></span>
    </div>
    <div class="flex items-center gap-2 text-slate-700">
      <Clock class="h-4 w-4 text-amber-600" />
      <span>Typical verification time: <strong class="text-slate-900">Within 24-48 business hours</strong></span>
    </div>
    <div class="flex items-center gap-2 text-slate-700">
      <ShieldCheck class="h-4 w-4 text-blue-600" />
      <span>Submitted ID &amp; permit files are stored securely.</span>
    </div>
  </div>
</template>
