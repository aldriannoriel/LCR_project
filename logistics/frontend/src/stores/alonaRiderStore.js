import { computed, ref } from 'vue';
import { defineStore } from 'pinia';

const apiRoot = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';
const apiBaseUrl = import.meta.env.VITE_ALONA_ONBOARDING_API_URL || `${apiRoot}/alona`;
const psgcBaseUrl = import.meta.env.VITE_PSGC_API_URL || 'https://psgc.gitlab.io/api';

const requestJson = async (path, options = {}) => {
  const token = localStorage.getItem('token');
  const response = await fetch(`${apiBaseUrl}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(options.headers || {}),
    },
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok) throw new Error(payload?.message || `Request failed with status ${response.status}.`);
  return payload?.data ?? payload;
};

const fetchPsgc = async (path) => {
  const response = await fetch(`${psgcBaseUrl}${path}`);
  if (!response.ok) throw new Error(`PSGC lookup failed with status ${response.status}.`);
  return response.json();
};

export const useAlonaRiderStore = defineStore('alonaRider', () => {
  const pendingRiders = ref([]);
  const approvedRiders = ref([]);
  const selectedRider = ref(null);
  const phRegions = ref([]);
  const phProvinces = ref([]);
  const phCities = ref([]);
  const phAllCities = ref([]);
  const phBarangays = ref([]);
  const loading = ref(false);
  const error = ref('');

  const allRiders = computed(() => [...pendingRiders.value, ...approvedRiders.value]);

  const fetchRidersByStatus = async (status) => {
    loading.value = true;
    error.value = '';
    try {
      const riders = await requestJson(`/get_riders.php?status=${encodeURIComponent(status)}`);
      if (status === 'PENDING') pendingRiders.value = riders;
      if (status === 'APPROVED') approvedRiders.value = riders;
      return riders;
    } catch (requestError) {
      error.value = requestError.message;
      throw requestError;
    } finally {
      loading.value = false;
    }
  };

  const registerRider = async (riderData) => requestJson('/rider_register.php', {
    method: 'POST',
    body: JSON.stringify({
      full_name: riderData.full_name,
      email: riderData.email,
      phone: riderData.phone,
      license_number: riderData.license_number,
      courier_type: riderData.courier_type || 'IN_HOUSE',
      document_urls: riderData.document_urls || {},
    }),
  });

  const replaceRider = (updatedRider) => {
    const replace = (items) => {
      const index = items.value.findIndex((rider) => String(rider.id) === String(updatedRider.id));
      if (index >= 0) items.value[index] = { ...items.value[index], ...updatedRider };
    };
    replace(pendingRiders);
    replace(approvedRiders);
    selectedRider.value = selectedRider.value?.id === updatedRider.id ? { ...selectedRider.value, ...updatedRider } : selectedRider.value;
  };

  const approveRider = async (riderId) => {
    const rider = await requestJson('/rider_review.php', { method: 'POST', body: JSON.stringify({ rider_id: riderId, action: 'APPROVE' }) });
    replaceRider(rider);
    pendingRiders.value = pendingRiders.value.filter((item) => String(item.id) !== String(riderId));
    approvedRiders.value.unshift(rider);
    return rider;
  };

  const rejectRider = async (riderId, rejectionReason) => {
    if (!rejectionReason?.trim()) throw new Error('A rejection reason is required.');
    const rider = await requestJson('/rider_review.php', { method: 'POST', body: JSON.stringify({ rider_id: riderId, action: 'REJECT', rejection_reason: rejectionReason.trim() }) });
    replaceRider(rider);
    pendingRiders.value = pendingRiders.value.filter((item) => String(item.id) !== String(riderId));
    return rider;
  };

  const saveAssignedLocations = async (riderId, locations) => {
    if (!Array.isArray(locations)) throw new Error('locations must be an array.');
    const saved = await requestJson('/assign_rider_locations.php', { method: 'POST', body: JSON.stringify({ rider_id: riderId, locations }) });
    const rider = allRiders.value.find((item) => String(item.id) === String(riderId));
    if (rider) rider.locations = saved;
    return saved;
  };

  const loadRegions = async () => { phRegions.value = await fetchPsgc('/regions.json'); return phRegions.value; };
  const loadProvinces = async (region) => { phProvinces.value = region ? await fetchPsgc(`/regions/${region.code}/provinces.json`) : []; phCities.value = []; phBarangays.value = []; return phProvinces.value; };
  const loadCities = async (province) => { phCities.value = province ? await fetchPsgc(`/provinces/${province.code}/cities-municipalities.json`) : []; phBarangays.value = []; return phCities.value; };
  const loadAllCities = async () => {
    if (phAllCities.value.length) return phAllCities.value;
    try {
      phAllCities.value = await fetchPsgc('/cities-municipalities.json');
    } catch (_) {
      const regions = phRegions.value.length ? phRegions.value : await loadRegions();
      const provinces = (await Promise.all(regions.map((region) => fetchPsgc(`/regions/${region.code}/provinces.json`)))).flat();
      const cityLists = await Promise.all(provinces.map((province) => fetchPsgc(`/provinces/${province.code}/cities-municipalities.json`)));
      phAllCities.value = cityLists.flat();
    }
    phAllCities.value = [...new Map(phAllCities.value.map((city) => [city.code, city])).values()].sort((left, right) => left.name.localeCompare(right.name));
    return phAllCities.value;
  };
  const loadBarangays = async (city) => { phBarangays.value = city ? await fetchPsgc(`/cities-municipalities/${city.code}/barangays.json`) : []; return phBarangays.value; };

  return {
    pendingRiders, approvedRiders, selectedRider, phRegions, phProvinces, phCities, phAllCities, phBarangays,
    allRiders, loading, error, fetchRidersByStatus, registerRider, approveRider, rejectRider,
    saveAssignedLocations, loadRegions, loadProvinces, loadCities, loadAllCities, loadBarangays,
  };
});
