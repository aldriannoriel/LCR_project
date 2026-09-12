import { defineStore } from 'pinia';
import { axios } from '../lib/echo';

export const useReturnStore = defineStore('returns', {
  state: () => ({ returns: { data: [], current_page: 1, last_page: 1, total: 0 }, history: [], loading: false, error: '' }),
  actions: {
    auth() { const token = localStorage.getItem('token'); if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`; },
    async fetchReturns(params = {}) { this.auth(); this.loading = true; try { this.returns = (await axios.get('/returns', { params })).data; } catch (e) { this.error = e.response?.data?.message || 'Unable to load return queue.'; throw e; } finally { this.loading = false; } },
    async intake(payload) { this.auth(); return (await axios.post('/returns/intake', payload)).data; },
    async processAction(id, payload) { this.auth(); return (await axios.post(`/returns/${id}/action`, payload)).data; },
    async fetchHistory(orderId) { this.auth(); this.history = (await axios.get(`/orders/${orderId}/history`)).data; return this.history; },
  },
});