import { defineStore } from 'pinia';
import { axios } from '../lib/echo';

export const useHubStore = defineStore('hubs', {
  state: () => ({ hubs: [], inventory: { data: [], current_page: 1, last_page: 1, total: 0 }, transfers: { data: [], current_page: 1, last_page: 1, total: 0 }, selectedHub: null, loading: false, error: '' }),
  actions: {
    auth() { const token = localStorage.getItem('token'); if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`; },
    async fetchGrid(params = {}) { this.auth(); this.hubs = (await axios.get('/hubs/grid', { params })).data; },
    async fetchInventory(hubId, params = {}) { this.auth(); this.loading = true; try { this.inventory = (await axios.get(`/hubs/${hubId}/inventory`, { params })).data; } finally { this.loading = false; } },
    async fetchTransfers(params = {}) { this.auth(); this.transfers = (await axios.get('/transfer-requests', { params })).data; },
    async createTransfer(payload) { this.auth(); return (await axios.post('/transfer-requests', payload)).data; },
    async updateTransferStatus(id, status) { this.auth(); return (await axios.patch(`/transfer-requests/${id}/status`, { status })).data; },
  },
});