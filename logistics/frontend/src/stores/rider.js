import { defineStore } from 'pinia';
import { axios } from '../lib/echo';

export const useRiderStore = defineStore('riders', {
  state: () => ({ riders: { data: [], current_page: 1, last_page: 1, total: 0 }, selected: null, loading: false, error: '' }),
  actions: {
    auth() { const token = localStorage.getItem('token'); if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`; },
    async fetchRiders(params = {}) { this.auth(); this.loading = true; try { this.riders = (await axios.get('/riders', { params })).data; } catch (e) { this.error = e.response?.data?.message || 'Unable to load riders.'; throw e; } finally { this.loading = false; } },
    async fetchDetails(id) { this.auth(); this.selected = (await axios.get(`/riders/${id}/details`)).data; return this.selected; },
    async updateStatus(id, status) { this.auth(); return (await axios.patch(`/riders/${id}/status`, { status })).data; },
    async assignOrders(payload) { this.auth(); return (await axios.post('/riders/assign-orders', payload)).data; },
  },
});