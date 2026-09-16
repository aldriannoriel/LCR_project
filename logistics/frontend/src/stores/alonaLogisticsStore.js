import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { createClient } from '@supabase/supabase-js';

const apiBaseUrl = import.meta.env.VITE_ALONA_API_URL || import.meta.env.VITE_API_URL || 'http://localhost:8000/api/alona';
const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;

const supabase = supabaseUrl && supabaseAnonKey
  ? createClient(supabaseUrl, supabaseAnonKey)
  : null;

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

  let payload = null;
  try {
    payload = await response.json();
  } catch (_) {
    payload = null;
  }

  if (!response.ok) {
    throw new Error(payload?.message || `Logistics API request failed with status ${response.status}.`);
  }

  return payload?.data ?? payload;
};

const collection = (payload) => {
  if (Array.isArray(payload)) return payload;
  if (Array.isArray(payload?.data)) return payload.data;
  return [];
};

export const useAlonaLogisticsStore = defineStore('alonaLogistics', () => {
  const riders = ref([]);
  const zones = ref([]);
  const manifests = ref([]);
  const parcels = ref([]);
  const activeFilter = ref('');
  const loading = ref(false);
  const error = ref('');
  const realtimeConnected = ref(false);
  let realtimeChannel = null;

  const filteredParcels = computed(() => activeFilter.value
    ? parcels.value.filter((parcel) => parcel.status === activeFilter.value)
    : parcels.value);

  const fetchLogisticsData = async () => {
    loading.value = true;
    error.value = '';
    try {
      const [ridersResponse, zonesResponse, manifestsResponse, parcelsResponse] = await Promise.all([
        requestJson('/riders'),
        requestJson('/zones'),
        requestJson('/manifests'),
        requestJson('/parcels'),
      ]);
      riders.value = collection(ridersResponse);
      zones.value = collection(zonesResponse);
      manifests.value = collection(manifestsResponse);
      parcels.value = collection(parcelsResponse);
      return { riders: riders.value, zones: zones.value, manifests: manifests.value, parcels: parcels.value };
    } catch (requestError) {
      error.value = requestError.message || 'Unable to load logistics data.';
      throw requestError;
    } finally {
      loading.value = false;
    }
  };

  const updateParcelStatus = async (parcelId, newStatus, remarks = '') => {
    if (!parcelId || !newStatus) throw new Error('parcelId and newStatus are required.');

    const updatedParcel = await requestJson(`/parcels/${encodeURIComponent(parcelId)}/status`, {
      method: 'POST',
      body: JSON.stringify({ status: newStatus, reason: remarks }),
    });

    const index = parcels.value.findIndex((parcel) => String(parcel.id) === String(parcelId));
    if (index >= 0) parcels.value[index] = { ...parcels.value[index], ...updatedParcel };
    else parcels.value.unshift(updatedParcel);
    return updatedParcel;
  };

  const updateRiderStatus = async (riderId, status) => {
    const updatedRider = await requestJson(`/riders/${encodeURIComponent(riderId)}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status }),
    });
    upsertById(riders, updatedRider);
    return updatedRider;
  };

  const reviewRider = async (riderId, action, reason = '') => {
    const updatedRider = await requestJson(`/riders/${encodeURIComponent(riderId)}/${action}`, {
      method: 'POST',
      body: JSON.stringify(action === 'reject' ? { reason } : {}),
    });
    upsertById(riders, updatedRider);
    return updatedRider;
  };

  const verifyRiderDocument = async (riderId, documentId, status, rejectionReason = '') => {
    const document = await requestJson(`/riders/${encodeURIComponent(riderId)}/documents/${encodeURIComponent(documentId)}/verify`, {
      method: 'PATCH',
      body: JSON.stringify({ status, rejection_reason: rejectionReason || null }),
    });
    const rider = riders.value.find((item) => String(item.id) === String(riderId));
    if (rider?.documents) rider.documents = rider.documents.map((item) => String(item.id) === String(documentId) ? { ...item, ...document } : item);
    return document;
  };

  const assignZoneRider = async (zoneId, riderId) => {
    const updatedZone = await requestJson(`/zones/${encodeURIComponent(zoneId)}/assign-default`, {
      method: 'POST',
      body: JSON.stringify({ rider_id: riderId || null }),
    });
    upsertById(zones, updatedZone);
    return updatedZone;
  };

  const fetchManifestDetails = async (manifestId) => requestJson(`/manifests/${encodeURIComponent(manifestId)}`);

  const approveManifest = async (manifestId) => {
    const updatedManifest = await requestJson(`/manifests/${encodeURIComponent(manifestId)}/approve`, { method: 'POST' });
    upsertById(manifests, updatedManifest);
    return updatedManifest;
  };

  const upsertById = (items, nextItem) => {
    const index = items.value.findIndex((item) => String(item.id) === String(nextItem.id));
    if (index === -1) items.value.unshift(nextItem);
    else items.value[index] = { ...items.value[index], ...nextItem };
  };

  const initRealtimeSubscriptions = async () => {
    if (!supabase) {
      realtimeConnected.value = false;
      error.value = 'Supabase realtime is not configured. Set VITE_SUPABASE_URL and VITE_SUPABASE_ANON_KEY.';
      return null;
    }

    if (realtimeChannel) await supabase.removeChannel(realtimeChannel);

    realtimeChannel = supabase
      .channel('alona-logistics-changes')
      .on('postgres_changes', { event: '*', schema: 'public', table: 'alona_parcels' }, ({ eventType, new: nextParcel }) => {
        if (eventType === 'INSERT' || eventType === 'UPDATE') upsertById(parcels, nextParcel);
      })
      .on('postgres_changes', { event: '*', schema: 'public', table: 'alona_bulk_manifests' }, ({ eventType, new: nextManifest }) => {
        if (eventType === 'INSERT' || eventType === 'UPDATE') upsertById(manifests, nextManifest);
      });

    realtimeChannel.subscribe((status) => {
      realtimeConnected.value = status === 'SUBSCRIBED';
      if (status === 'CHANNEL_ERROR' || status === 'TIMED_OUT') error.value = `Supabase realtime connection ${status.toLowerCase()}.`;
    });

    return realtimeChannel;
  };

  const stopRealtimeSubscriptions = async () => {
    if (supabase && realtimeChannel) await supabase.removeChannel(realtimeChannel);
    realtimeChannel = null;
    realtimeConnected.value = false;
  };

  return {
    riders,
    zones,
    manifests,
    parcels,
    activeFilter,
    filteredParcels,
    loading,
    error,
    realtimeConnected,
    fetchLogisticsData,
    updateParcelStatus,
    updateRiderStatus,
    reviewRider,
    verifyRiderDocument,
    assignZoneRider,
    fetchManifestDetails,
    approveManifest,
    initRealtimeSubscriptions,
    stopRealtimeSubscriptions,
  };
});