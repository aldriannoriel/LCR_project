<script setup>
import AppLayout from './layouts/AppLayout.vue';
import CourierLayout from './layouts/CourierLayout.vue';
</script>

<template>
  <CourierLayout v-if="$route.path.startsWith('/courier')" />
  <AppLayout v-else-if="$route.meta.requiresAuth" />
  <router-view v-else />
</template>