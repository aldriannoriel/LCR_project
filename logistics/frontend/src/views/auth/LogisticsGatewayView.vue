<script setup>
import { useRouter } from 'vue-router';
import { ArrowLeft, LogIn, UserPlus } from 'lucide-vue-next';
const router = useRouter();
</script>
<template>
  <main class="min-h-screen bg-[#f3f7f6] px-4 py-12 text-slate-900"><section class="mx-auto max-w-2xl"><button class="inline-flex items-center gap-2 text-sm font-bold text-teal-700" @click="router.push('/')"><ArrowLeft class="h-4 w-4" />Portal selection</button><div class="mt-12 border border-slate-200 bg-white p-8 shadow-sm sm:p-10"><p class="text-xs font-black uppercase tracking-[0.2em] text-teal-700">Logistics gateway</p><h1 class="mt-3 text-3xl font-black">Welcome to Alona Logistics</h1><p class="mt-2 text-sm text-slate-500">Sign in to continue or register for administrator approval.</p><div class="mt-8 grid gap-3 sm:grid-cols-2"><button class="flex items-center gap-3 border border-slate-300 p-4 text-left font-black hover:border-teal-600" @click="router.push('/login')"><LogIn class="h-5 w-5 text-teal-700" />Login</button><button class="flex items-center gap-3 bg-slate-950 p-4 text-left font-black text-white hover:bg-teal-700" @click="router.push('/auth/register-role')"><UserPlus class="h-5 w-5" />Register</button></div></div></section></main>
</template>
