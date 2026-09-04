<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useCourierStore } from '../../stores/courier';
import { useAuthStore } from '../../stores/auth';
import { echo } from '../../lib/echo';
import { Bike, Package, X } from 'lucide-vue-next';

const courier = useCourierStore();
const auth = useAuthStore();

const notification = ref(null);
const visible = ref(false);
let timeout = null;

const show = (data) => {
  notification.value = data;
  visible.value = true;
  if (timeout) clearTimeout(timeout);
  timeout = setTimeout(() => { visible.value = false; }, 8000);
};

const dismiss = () => {
  visible.value = false;
  if (timeout) clearTimeout(timeout);
};

onMounted(() => {
  if (!echo || !auth.user?.rider?.id) return;

  const riderId = auth.user.rider.id;

  echo.private(`rider.${riderId}.pickups`)
    .listen('NewPickupAssigned', (e) => {
      show({ type: 'pickup', ...e });
      courier.fetchDashboard();
    });

  echo.private(`rider.${riderId}.deliveries`)
    .listen('NewDeliveryAssigned', (e) => {
      show({ type: 'delivery', ...e });
      courier.fetchDashboard();
    });
});

onBeforeUnmount(() => {
  if (timeout) clearTimeout(timeout);
});
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -translate-y-full"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-full"
    >
      <div
        v-if="visible && notification"
        class="fixed top-0 left-0 right-0 z-[100] bg-gradient-to-r from-teal-600 to-blue-600 text-white px-4 py-3 shadow-xl cursor-pointer"
        @click="dismiss"
      >
        <div class="flex items-center justify-between max-w-lg mx-auto">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-white/20 rounded-full">
              <Package v-if="notification.type === 'pickup'" class="h-5 w-5" />
              <Bike v-else class="h-5 w-5" />
            </div>
            <div>
              <p class="font-bold text-sm">
                {{ notification.type === 'pickup' ? 'New Pickup Assigned!' : 'New Delivery Assigned!' }}
              </p>
              <p class="text-xs text-teal-100">{{ notification.message || 'Tap to view' }}</p>
            </div>
          </div>
          <button @click.stop="dismiss" class="p-1 hover:bg-white/20 rounded-full">
            <X class="h-5 w-5" />
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
