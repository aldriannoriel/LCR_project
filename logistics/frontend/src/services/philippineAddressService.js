import Axios from 'axios';

const BASE_URL = 'https://psgc.gitlab.io/api';

// In-memory cache for fast lookup
const cache = {
  provinces: null,
  citiesByProvince: {},
  barangaysByCity: {},
};

export const philippineAddressService = {
  /**
   * Get all provinces in the Philippines, including Metro Manila (NCR).
   */
  async getProvinces() {
    if (cache.provinces) {
      return cache.provinces;
    }

    try {
      const response = await Axios.get(`${BASE_URL}/provinces.json`);
      const list = (response.data || []).map((p) => ({
        code: p.code,
        name: p.name,
        isNCR: false,
      }));

      // Add Metro Manila (NCR) at the top or sorted alphabetically
      list.push({
        code: '130000000',
        name: 'Metro Manila (NCR)',
        isNCR: true,
      });

      list.sort((a, b) => a.name.localeCompare(b.name));
      cache.provinces = list;
      return list;
    } catch (err) {
      console.error('Failed to load provinces from PSGC API:', err);
      // Fallback provinces list
      return [
        { code: '130000000', name: 'Metro Manila (NCR)', isNCR: true },
        { code: '041000000', name: 'Batangas', isNCR: false },
        { code: '031400000', name: 'Bulacan', isNCR: false },
        { code: '042100000', name: 'Cavite', isNCR: false },
        { code: '072200000', name: 'Cebu', isNCR: false },
        { code: '112400000', name: 'Davao del Sur', isNCR: false },
        { code: '063000000', name: 'Iloilo', isNCR: false },
        { code: '043400000', name: 'Laguna', isNCR: false },
        { code: '104300000', name: 'Misamis Oriental', isNCR: false },
        { code: '035400000', name: 'Pampanga', isNCR: false },
        { code: '045800000', name: 'Rizal', isNCR: false },
      ].sort((a, b) => a.name.localeCompare(b.name));
    }
  },

  /**
   * Get all cities and municipalities for a given province code.
   */
  async getCities(province) {
    if (!province) return [];

    const key = province.code || province.name;
    if (cache.citiesByProvince[key]) {
      return cache.citiesByProvince[key];
    }

    try {
      let url = `${BASE_URL}/provinces/${province.code}/cities-municipalities.json`;
      if (province.isNCR || province.name?.includes('Metro Manila')) {
        url = `${BASE_URL}/regions/130000000/cities-municipalities.json`;
      }

      const response = await Axios.get(url);
      const cities = (response.data || []).map((c) => ({
        code: c.code,
        name: c.name.replace(/^(City of\s+)/i, '').replace(/(\s+City)$/i, '') + (c.isCity ? ' City' : ''),
        rawName: c.name,
        isCity: c.isCity,
      }));

      cities.sort((a, b) => a.name.localeCompare(b.name));
      cache.citiesByProvince[key] = cities;
      return cities;
    } catch (err) {
      console.error(`Failed to load cities for ${province.name}:`, err);
      return [];
    }
  },

  /**
   * Get all barangays for a given city / municipality code.
   */
  async getBarangays(city) {
    if (!city || !city.code) return [];

    const key = city.code;
    if (cache.barangaysByCity[key]) {
      return cache.barangaysByCity[key];
    }

    try {
      const response = await Axios.get(`${BASE_URL}/cities-municipalities/${city.code}/barangays.json`);
      const barangays = (response.data || []).map((b) => ({
        code: b.code,
        name: b.name,
      }));

      barangays.sort((a, b) => a.name.localeCompare(b.name));
      cache.barangaysByCity[key] = barangays;
      return barangays;
    } catch (err) {
      console.error(`Failed to load barangays for ${city.name}:`, err);
      return [];
    }
  },
};

