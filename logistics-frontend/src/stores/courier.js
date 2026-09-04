import { defineStore } from 'pinia';
import { axios } from '../lib/echo';

export const useCourierStore = defineStore('courier', {
  state: () => ({
    stats: null,
    pickups: { data: [], total: 0 },
    deliveries: { data: [], total: 0 },
    earnings: null,
    history: { data: [] },
    currentPickup: null,
    currentDelivery: null,
    loading: false,
    error: null,
  }),

  actions: {
    _auth() {
      const token = localStorage.getItem('token');
      if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },

    // ── Dashboard ────────────────────────────────────────────────
    async fetchDashboard() {
      this._auth();
      this.loading = true;
      try {
        this.stats = (await axios.get('/courier/dashboard')).data;
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to load dashboard.';
      } finally {
        this.loading = false;
      }
    },

    // ── Status ──────────────────────────────────────────────────
    async toggleStatus(status) {
      this._auth();
      return (await axios.post('/courier/status', { status })).data;
    },

    // ── Pickups ─────────────────────────────────────────────────
    async fetchPickups(params = {}) {
      this._auth();
      this.loading = true;
      try {
        this.pickups = (await axios.get('/courier/pickups', { params })).data;
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to load pickups.';
      } finally {
        this.loading = false;
      }
    },

    async fetchPickupDetail(id) {
      this._auth();
      this.currentPickup = (await axios.get(`/courier/pickups/${id}`)).data;
      return this.currentPickup;
    },

    async startPickup(id) {
      this._auth();
      const res = await axios.post(`/courier/pickups/${id}/start`);
      await this.fetchDashboard();
      return res.data;
    },

    async completePickup(id, payload) {
      this._auth();
      const res = await axios.post(`/courier/pickups/${id}/complete`, payload);
      await this.fetchDashboard();
      return res.data;
    },

    // ── Deliveries ──────────────────────────────────────────────
    async fetchDeliveries(params = {}) {
      this._auth();
      this.loading = true;
      try {
        this.deliveries = (await axios.get('/courier/deliveries', { params })).data;
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to load deliveries.';
      } finally {
        this.loading = false;
      }
    },

    async fetchDeliveryDetail(id) {
      this._auth();
      this.currentDelivery = (await axios.get(`/courier/deliveries/${id}`)).data;
      return this.currentDelivery;
    },

    async completeDelivery(id, payload) {
      this._auth();
      const res = await axios.post(`/courier/deliveries/${id}/complete`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      await this.fetchDashboard();
      return res.data;
    },

    async failDelivery(id, payload) {
      this._auth();
      const res = await axios.post(`/courier/deliveries/${id}/failed`, payload, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      await this.fetchDashboard();
      return res.data;
    },

    // ── Earnings ────────────────────────────────────────────────
    async fetchEarnings(params = {}) {
      this._auth();
      this.loading = true;
      try {
        this.earnings = (await axios.get('/courier/earnings', { params })).data;
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to load earnings.';
      } finally {
        this.loading = false;
      }
    },

    // ── History ─────────────────────────────────────────────────
    async fetchHistory(params = {}) {
      this._auth();
      this.loading = true;
      try {
        this.history = (await axios.get('/courier/history', { params })).data;
      } catch (e) {
        this.error = e.response?.data?.message || 'Failed to load history.';
      } finally {
        this.loading = false;
      }
    },

    // ── Profile ─────────────────────────────────────────────────
    async fetchProfile() {
      this._auth();
      return (await axios.get('/courier/profile')).data;
    },

    async updateProfile(payload) {
      this._auth();
      return (await axios.put('/courier/profile', payload)).data;
    },

    async changePassword(payload) {
      this._auth();
      return (await axios.put('/courier/password', payload)).data;
    },
  },
});
