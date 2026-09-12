import { defineStore } from 'pinia';
import { axios } from '../lib/echo';

export const useOrderStore = defineStore('orders', {
  state: () => ({
    orders: { data: [], current_page: 1, last_page: 1, total: 0 },
    hubs: [],
    loading: false,
    error: '',
    activeHubId: null,
  }),
  actions: {
    setAuthHeader() {
      const token = localStorage.getItem('token');
      if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    },
    async fetchOrders(params = {}) {
      this.setAuthHeader();
      this.loading = true;
      this.error = '';
      try {
        const response = await axios.get('/orders', { params });
        this.orders = response.data;
      } catch (error) {
        this.error = error.response?.data?.message || 'Unable to load orders.';
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async fetchHubs() {
      this.setAuthHeader();
      const response = await axios.get('/hubs');
      this.hubs = response.data;
      if (!this.activeHubId) this.activeHubId = this.hubs[0]?.id || null;
    },
    async scanInboundOrder(awbNumber, hubId = this.activeHubId) {
      this.setAuthHeader();
      return axios.post('/orders/intake', { awb_number: awbNumber, hub_id: hubId });
    },
    async flagOrderOverride(orderId, payload) {
      this.setAuthHeader();
      return axios.post(`/orders/${orderId}/override`, payload);
    },
    async bulkConfirmArrivals(orderIds, hubId) {
      this.setAuthHeader();
      return axios.post('/orders/confirm-arrivals', { order_ids: orderIds, hub_id: hubId });
    },
  },
});