<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';

const email = ref('admin@logistics.local');
const password = ref('password123');
const errorMessage = ref('');

const authStore = useAuthStore();
const router = useRouter();

const handleLogin = async () => {
  try {
    await authStore.login({ email: email.value, password: password.value });
    router.push('/dashboard');
  } catch (err) {
    errorMessage.value = 'Invalid credentials. Please try again.';
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-900 px-4">
    <div class="max-w-md w-full bg-white p-8 rounded-xl shadow-lg">
      <h2 class="text-2xl font-bold text-gray-900 mb-2 text-center">Logistics System</h2>
      <p class="text-sm text-gray-500 text-center mb-6">Sign in to your account</p>

      <div v-if="errorMessage" class="mb-4 p-3 bg-red-100 text-red-700 rounded text-sm">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Email Address</label>
          <input 
            v-model="email" 
            type="email" 
            required 
            class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500" 
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">Password</label>
          <input 
            v-model="password" 
            type="password" 
            required 
            class="mt-1 block w-full border border-gray-300 rounded-md p-2.5 text-sm focus:ring-blue-500 focus:border-blue-500" 
          />
        </div>

        <button 
          type="submit" 
          class="w-full bg-blue-600 text-white py-2.5 rounded-md text-sm font-semibold hover:bg-blue-700 transition"
        >
          Sign In
        </button>
      </form>
    </div>
  </div>
</template>