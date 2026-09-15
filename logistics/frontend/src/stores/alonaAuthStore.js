import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { getRegions, getAllProvinces, getProvinces, getMunicipalities, getBarangays } from '../utils/psgc';

const apiRoot = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const baseUrl = import.meta.env.VITE_ALONA_ONBOARDING_API_URL || `${apiRoot}/alona`;

const request = async (path, options = {}) => {
  const response = await fetch(`${baseUrl}${path}`, { ...options, headers: { Accept: 'application/json', ...(options.headers || {}) } });
  const payload = await response.json().catch(() => null);
  if (!response.ok) throw new Error(payload?.message || 'Request could not be completed.');
  return payload?.data ?? payload;
};

export const useAlonaAuthStore = defineStore('alonaAuth', () => {
  const loading = ref(false);
  const error = ref('');
  const submission = ref(null);
  const regions = ref([]);
  const provinces = ref([]);
  const municipalities = ref([]);
  const barangays = ref([]);
  const hubs = ref([]);
  const selectedRegion = ref(null);
  const selectedProvince = ref(null);
  const selectedMunicipality = ref(null);
  const hasSubmission = computed(() => Boolean(submission.value));

  const loadRegions = async () => { regions.value = await getRegions(); return regions.value; };
  const loadAllProvinces = async () => { provinces.value = await getAllProvinces(); municipalities.value = []; barangays.value = []; return provinces.value; };
  const loadProvinces = async (region) => { selectedRegion.value = region; selectedProvince.value = null; selectedMunicipality.value = null; municipalities.value = []; barangays.value = []; provinces.value = region ? await getProvinces(region.code) : []; return provinces.value; };
  const loadMunicipalities = async (province) => { selectedProvince.value = province; selectedMunicipality.value = null; barangays.value = []; municipalities.value = province ? await getMunicipalities(province.code) : []; return municipalities.value; };
  const loadBarangays = async (municipality) => { selectedMunicipality.value = municipality; barangays.value = municipality ? await getBarangays(municipality.code) : []; return barangays.value; };
  const loadHubs = async () => {
    const response = await fetch(`${apiRoot}/registration/hubs`, { headers: { Accept: 'application/json' } });
    const payload = await response.json().catch(() => []);
    if (!response.ok) throw new Error(payload?.message || 'Unable to load hubs.');
    hubs.value = payload?.data ?? payload;
    return hubs.value;
  };

  const submit = async (path, formData, root = baseUrl) => {
    loading.value = true;
    error.value = '';
    try {
      const response = await fetch(`${root}${path}`, { method: 'POST', body: formData, headers: { Accept: 'application/json' } });
      const payload = await response.json().catch(() => null);
      if (!response.ok) {
        const validationError = new Error(payload?.message || 'Request could not be completed.');
        validationError.errors = payload?.errors || {};
        throw validationError;
      }
      submission.value = payload?.data ?? payload;
      return submission.value;
    } catch (requestError) { error.value = requestError.message; throw requestError; } finally { loading.value = false; }
  };
  const registerRider = (formData) => submit('/register', formData, apiRoot);
  const registerStaff = (formData) => submit('/register', formData, apiRoot);

  const fetchUsersByStatus = async (status) => request(`/admin/get_users.php?status=${encodeURIComponent(status)}`);
  const reviewUser = async (userId, action, rejectionReason = '') => request('/admin/review_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${localStorage.getItem('token') || ''}` }, body: JSON.stringify({ user_id: userId, action, rejection_reason: rejectionReason || null }) });
  const saveAssignedLocations = async (riderId, locations) => request('/admin/assign_rider_locations.php', { method: 'POST', headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${localStorage.getItem('token') || ''}` }, body: JSON.stringify({ rider_id: riderId, locations }) });

  const resetLocationState = () => { selectedRegion.value = null; selectedProvince.value = null; selectedMunicipality.value = null; provinces.value = []; municipalities.value = []; barangays.value = []; };
  return { loading, error, submission, hasSubmission, regions, provinces, municipalities, barangays, hubs, selectedRegion, selectedProvince, selectedMunicipality, loadRegions, loadAllProvinces, loadProvinces, loadMunicipalities, loadBarangays, loadHubs, registerRider, registerStaff, fetchUsersByStatus, reviewUser, saveAssignedLocations, resetLocationState };
});
