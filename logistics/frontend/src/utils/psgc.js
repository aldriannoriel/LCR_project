import Axios from 'axios';

const baseUrl = import.meta.env.VITE_PSGC_API_URL || 'https://psgc.gitlab.io/api';
const cache = new Map();

const get = async (path) => {
  if (cache.has(path)) return cache.get(path);
  const response = await Axios.get(`${baseUrl}${path}`);
  cache.set(path, response.data || []);
  return response.data || [];
};

export const getRegions = () => get('/regions.json');
export const getAllProvinces = () => get('/provinces.json');
export const getProvinces = (regionCode) => get(`/regions/${regionCode}/provinces.json`);
export const getMunicipalities = (provinceCode) => get(`/provinces/${provinceCode}/cities-municipalities.json`);
export const getBarangays = (cityCode) => get(`/cities-municipalities/${cityCode}/barangays.json`);

export const calculateAge = (birthday) => {
  if (!birthday) return '';
  const birthDate = new Date(`${birthday}T00:00:00`);
  if (Number.isNaN(birthDate.getTime())) return '';
  const today = new Date();
  let age = today.getFullYear() - birthDate.getFullYear();
  const birthdayPassed = today.getMonth() > birthDate.getMonth() || (today.getMonth() === birthDate.getMonth() && today.getDate() >= birthDate.getDate());
  if (!birthdayPassed) age -= 1;
  return age >= 0 ? age : '';
};

export const normalizeUpload = (file) => ({ name: file?.name || '', size: file?.size || 0, type: file?.type || '' });
