import { defineStore } from 'pinia';
import { axios } from '../lib/echo';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        roles: JSON.parse(localStorage.getItem('user_roles') || '[]'),
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        userRoles: (state) => state.user?.roles?.map(r => r.name.toLowerCase().replaceAll(' ', '_')) || state.roles,
        hasRole: (state) => (roles) => (Array.isArray(roles) ? roles : [roles]).some((role) => state.user?.roles?.some((item) => item.name === role) || state.roles.includes(role)),
    },
    actions: {
        async login(credentials) {
            const response = await axios.post('/login', credentials);
            this.token = response.data.access_token;
            this.user = response.data.user;
            this.roles = this.user.roles?.map((role) => role.name.toLowerCase().replaceAll(' ', '_')) || [];

            localStorage.setItem('token', this.token);
            localStorage.setItem('user_roles', JSON.stringify(this.roles));
            axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        },
        async logout() {
            if (this.token) {
                try {
                    await axios.post('/logout');
                } catch (e) {}
            }
            this.user = null;
            this.token = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user_roles');
            delete axios.defaults.headers.common['Authorization'];
        }
    }
});