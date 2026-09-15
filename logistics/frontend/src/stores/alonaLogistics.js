import { defineStore } from 'pinia';
import { axios, echo } from '../lib/echo';

const adminRoles = ['admin', 'super_admin', 'logistics_admin'];

export const useAlonaLogisticsStore = defineStore('alonaLogistics', {
  state: () => ({
    metrics: { pendingManifests: 0, activeRiders: 0, outForDelivery: 0, failedDeliveries: 0 },
    riders: [],
    zones: [],
    manifests: [],
    activity: [],
    loading: false,
    realtimeConnected: false,
  }),

  actions: {
    auth() {
      const token = localStorage.getItem('token');
      if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },

    async loadDashboard() {
      this.auth();
      this.loading = true;
      try {
        const response = await axios.get('/alona/dashboard');
        this.metrics = response.data.metrics;
        this.activity = response.data.activity || [];
      } finally {
        this.loading = false;
      }
    },

    async loadZones() {
      this.auth();
      this.zones = (await axios.get('/alona/zones')).data.data || [];
    },

    async loadRiders(params = {}) {
      this.auth();
      this.riders = (await axios.get('/alona/riders', { params })).data.data || [];
    },

    async loadManifests(params = {}) {
      this.auth();
      this.manifests = (await axios.get('/alona/manifests', { params })).data.data || [];
    },

    async approveManifest(id) {
      this.auth();
      await axios.post(`/alona/manifests/${id}/approve`);
      await this.loadManifests({ status: 'PENDING_APPROVAL' });
      await this.loadDashboard();
    },

    subscribe() {
      if (!adminRoles.some((role) => JSON.parse(localStorage.getItem('user_roles') || '[]').includes(role))) return;

      try {
        echo.private('alona.parcels').listen('.parcel.status.changed', ({ parcel }) => {
          this.activity.unshift({ entity_type: 'parcel', entity_id: parcel.id, new_status: parcel.status, created_at: new Date().toISOString() });
          this.activity = this.activity.slice(0, 30);
          this.loadDashboard();
        });
        echo.private('alona.manifests').listen('.manifest.updated', () => {
          this.loadManifests({ status: 'PENDING_APPROVAL' });
          this.loadDashboard();
        });
        this.realtimeConnected = true;
      } catch (_) {
        this.realtimeConnected = false;
      }
    },

    unsubscribe() {
      echo.leave('alona.parcels');
      echo.leave('alona.manifests');
      this.realtimeConnected = false;
    },
  },
});
