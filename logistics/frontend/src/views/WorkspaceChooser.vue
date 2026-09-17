<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { BriefcaseBusiness, Truck, Warehouse } from 'lucide-vue-next';
import { useAuthStore } from '../stores/auth';

const auth = useAuthStore();
const router = useRouter();
const roles = computed(() => auth.userRoles);
const canCourier = computed(() => roles.value.some((role) => ['courier_admin', 'admin', 'super_admin'].includes(role)));
const canLogistics = computed(() => roles.value.some((role) => ['logistics_admin', 'admin', 'super_admin'].includes(role)));
</script>

<template>
  <main class="flex min-h-screen items-center justify-center bg-[#f4f7f6] px-4 py-10 text-slate-900">
    <section class="w-full max-w-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
      <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 items-center justify-center bg-slate-950 text-white"><BriefcaseBusiness class="h-5 w-5" /></div>
        <div><p class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">Admin workspaces</p><h1 class="mt-1 text-2xl font-black">Choose a workspace</h1></div>
      </div>
      <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <button v-if="canCourier" class="border border-slate-200 p-5 text-left hover:border-blue-500" @click="router.push('/courier-admin')">
          <Truck class="h-6 w-6 text-blue-700" /><h2 class="mt-4 font-black">Courier administration</h2><p class="mt-1 text-sm text-slate-500">Receive, assign, monitor, and manage courier parcels.</p>
        </button>
        <button v-if="canLogistics" class="border border-slate-200 p-5 text-left hover:border-teal-500" @click="router.push('/alona/logistics')">
          <Warehouse class="h-6 w-6 text-teal-700" /><h2 class="mt-4 font-black">Logistics operations</h2><p class="mt-1 text-sm text-slate-500">Manage manifests, riders, zones, parcels, and disputes.</p>
        </button>
      </div>
    </section>
  </main>
</template>