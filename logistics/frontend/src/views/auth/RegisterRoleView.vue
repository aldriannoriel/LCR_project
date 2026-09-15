<script setup>
import { useRouter } from 'vue-router';
import { ArrowLeft, BriefcaseBusiness, Truck } from 'lucide-vue-next';
const router = useRouter();
</script>
<template>
  <main class="min-h-screen bg-[#f3f7f6] px-4 py-12 text-slate-900"><section class="mx-auto max-w-3xl"><button class="inline-flex items-center gap-2 text-sm font-bold text-teal-700" @click="router.push('/auth/logistics')"><ArrowLeft class="h-4 w-4" />Back</button><h1 class="mt-10 text-3xl font-black">Register for Alona Logistics</h1><p class="mt-2 text-sm text-slate-500">Choose the account type that matches your work.</p><div class="mt-8 grid gap-4 sm:grid-cols-2"><button class="border border-slate-200 bg-white p-6 text-left shadow-sm hover:border-teal-500" @click="router.push('/auth/register/rider')"><Truck class="h-8 w-8 text-teal-700" /><h2 class="mt-6 text-xl font-black">Register as Rider</h2><p class="mt-2 text-sm text-slate-500">Submit your driving credentials and vehicle documents.</p></button><button class="border border-slate-200 bg-white p-6 text-left shadow-sm hover:border-teal-500" @click="router.push('/auth/register/staff')"><BriefcaseBusiness class="h-8 w-8 text-blue-700" /><h2 class="mt-6 text-xl font-black">Register as Staff</h2><p class="mt-2 text-sm text-slate-500">Apply for an Alona logistics staff account.</p></button></div></section></main>
</template>
