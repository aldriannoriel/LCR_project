<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { axios } from '../../lib/echo';
import { philippineAddressService } from '../../services/philippineAddressService';
import {
  User, Mail, Phone, Lock, Calendar, MapPin, Building,
  FileText, Upload, CheckCircle2, AlertCircle, ArrowRight,
  ShieldCheck, Eye, EyeOff, Loader2, Bike, Package, ChevronDown
} from 'lucide-vue-next';
import AccountTypeSelector from '../../components/auth/AccountTypeSelector.vue';

const router = useRouter();

// Account type selection
const accountType = ref('');

// Form data
const form = ref({
  first_name: '', last_name: '', middle_initial: '',
  sex: 'male', email: '', phone_number: '', birthdate: '',
  password: '', password_confirmation: '',
  province: '', city_municipality: '', barangay: '', street_address: '',
  business_name: '',
  // Courier fields
  vehicle_type: '', plate_number: '', license_number: '',
  hub_id: '',
});

const hubs = ref([]);
const loadingHubs = ref(false);

// Address state
const provinces = ref([]);
const cities = ref([]);
const barangays = ref([]);
const loadingProvinces = ref(false);
const loadingCities = ref(false);
const loadingBarangays = ref(false);

// File refs
const idDocumentFile = ref(null);
const idDocumentPreview = ref(null);
const businessPermitFile = ref(null);
const businessPermitPreview = ref(null);
const licenseDocFile = ref(null);
const licenseDocPreview = ref(null);
const vehicleOrCrFile = ref(null);
const vehicleOrCrPreview = ref(null);

const showPassword = ref(false);
const submitting = ref(false);
const serverErrors = ref({});
const generalError = ref('');

const isCourier = computed(() => accountType.value === 'courier');

// Age calculation
const computedAge = computed(() => {
  if (!form.value.birthdate) return null;
  const birth = new Date(form.value.birthdate);
  if (isNaN(birth.getTime())) return null;
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  return age >= 0 ? age : 0;
});
const isUnderage = computed(() => computedAge.value !== null && computedAge.value < 18);

// Load data on mount
onMounted(async () => {
  loadingProvinces.value = true;
  try {
    provinces.value = await philippineAddressService.getProvinces();
  } finally {
    loadingProvinces.value = false;
  }
  // Load hubs for courier
  loadingHubs.value = true;
  try {
    const res = await axios.get('/hubs');
    hubs.value = res.data.data || res.data || [];
  } catch {}
  loadingHubs.value = false;
});

// Address cascading
const onProvinceChange = async () => {
  form.value.city_municipality = '';
  form.value.barangay = '';
  cities.value = [];
  barangays.value = [];
  const found = provinces.value.find((p) => p.name === form.value.province);
  if (found) {
    loadingCities.value = true;
    try {
      cities.value = await philippineAddressService.getCities(found);
    } finally {
      loadingCities.value = false;
    }
  }
};

const onCityChange = async () => {
  form.value.barangay = '';
  barangays.value = [];
  const found = cities.value.find((c) => c.name === form.value.city_municipality);
  if (found) {
    loadingBarangays.value = true;
    try {
      barangays.value = await philippineAddressService.getBarangays(found);
    } finally {
      loadingBarangays.value = false;
    }
  }
};

// File handlers
const handleIdFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  idDocumentFile.value = file;
  idDocumentPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : 'pdf';
  delete serverErrors.value.id_document;
};

const handlePermitFileChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  businessPermitFile.value = file;
  businessPermitPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : 'pdf';
  delete serverErrors.value.business_permit;
};

const handleLicenseDocChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  licenseDocFile.value = file;
  licenseDocPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : 'pdf';
  delete serverErrors.value.license_doc;
};

const handleVehicleOrCrChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  vehicleOrCrFile.value = file;
  vehicleOrCrPreview.value = file.type.startsWith('image/') ? URL.createObjectURL(file) : 'pdf';
  delete serverErrors.value.vehicle_or_cr;
};

const handleSubmit = async () => {
  generalError.value = '';
  serverErrors.value = {};

  if (isUnderage.value) {
    serverErrors.value.birthdate = ['You must be at least 18 years old.'];
    return;
  }
  if (!idDocumentFile.value) {
    serverErrors.value.id_document = ['Government ID is required.'];
    return;
  }
  if (isCourier.value) {
    if (!form.value.vehicle_type) { serverErrors.value.vehicle_type = ['Select a vehicle type.']; return; }
    if (!form.value.license_number) { serverErrors.value.license_number = ['Driver license number is required.']; return; }
    if (!licenseDocFile.value) { serverErrors.value.license_doc = ['Driver license document is required.']; return; }
    if (!vehicleOrCrFile.value) { serverErrors.value.vehicle_or_cr = ['Vehicle OR/CR is required.']; return; }
    if (!form.value.hub_id) { serverErrors.value.hub_id = ['Select your assigned hub.']; return; }
  }

  submitting.value = true;
  try {
    const fd = new FormData();
    fd.append('account_type', accountType.value);
    fd.append('first_name', form.value.first_name);
    fd.append('last_name', form.value.last_name);
    if (form.value.middle_initial) fd.append('middle_initial', form.value.middle_initial);
    fd.append('sex', form.value.sex);
    fd.append('email', form.value.email);
    fd.append('phone_number', form.value.phone_number);
    fd.append('birthdate', form.value.birthdate);
    fd.append('password', form.value.password);
    fd.append('password_confirmation', form.value.password_confirmation);
    fd.append('province', form.value.province);
    fd.append('city_municipality', form.value.city_municipality);
    fd.append('barangay', form.value.barangay);
    fd.append('street_address', form.value.street_address);
    if (form.value.business_name) fd.append('business_name', form.value.business_name);
    fd.append('id_document', idDocumentFile.value);
    if (businessPermitFile.value) fd.append('business_permit', businessPermitFile.value);

    if (isCourier.value) {
      fd.append('vehicle_type', form.value.vehicle_type);
      if (form.value.plate_number) fd.append('plate_number', form.value.plate_number);
      fd.append('license_number', form.value.license_number);
      fd.append('license_doc', licenseDocFile.value);
      fd.append('vehicle_or_cr', vehicleOrCrFile.value);
      fd.append('hub_id', form.value.hub_id);
    }

    const res = await axios.post('/register', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
    router.push({ path: '/registration-submitted', query: { email: form.value.email, type: accountType.value } });
  } catch (err) {
    if (err.response?.status === 422) {
      serverErrors.value = err.response.data.errors || {};
    } else {
      generalError.value = err.response?.data?.message || 'Registration failed. Please try again.';
    }
  } finally {
    submitting.value = false;
  }
};
</script>

<template>
  <div class="registration-page min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(7,143,150,0.12),_transparent_24%),linear-gradient(180deg,#ffffff_0%,#f2fbfb_100%)] px-4 py-10 text-slate-900 antialiased selection:bg-teal-500 selection:text-white">
    <div class="mx-auto max-w-3xl">
      <div class="mb-8 text-center">
        <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-1.5 text-xs font-bold uppercase tracking-widest text-blue-700">
          <ShieldCheck class="h-4 w-4" />
          Join Logistics OS
        </div>
        <h1 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl text-slate-900">Create Your Account</h1>
        <p class="mt-2 text-sm text-slate-500">All registrations undergo verification before access is granted.</p>
      </div>

      <AccountTypeSelector v-model="accountType" />

      <!-- No type selected message -->
      <div v-if="!accountType" class="rounded-2xl border border-slate-700 bg-slate-900/80 p-8 text-center">
        <Package class="mx-auto h-12 w-12 text-slate-600" />
        <p class="mt-3 font-bold text-slate-400">Select an account type above to continue</p>
      </div>

      <!-- Main Form Card -->
      <div v-if="accountType" class="rounded-[28px] border border-slate-200 bg-white/90 p-6 shadow-[0_24px_60px_rgba(15,23,42,0.08)] backdrop-blur-xl sm:p-10">
        <!-- Error Alert -->
        <div v-if="generalError" class="mb-6 flex items-start gap-3 rounded-lg border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-300">
          <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" />
          <span>{{ generalError }}</span>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-8">
          <!-- Section 1: Personal Details -->
          <div>
            <div class="flex items-center gap-2 border-b border-white/10 pb-3">
              <User class="h-5 w-5 text-teal-400" />
              <h2 class="text-base font-bold uppercase tracking-wider text-teal-300">1. Personal Information</h2>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-6">
              <div class="sm:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">First Name <span class="text-rose-400">*</span></label>
                <input v-model="form.first_name" type="text" required placeholder="e.g. Juan" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                <p v-if="serverErrors.first_name" class="mt-1 text-xs text-rose-400">{{ serverErrors.first_name[0] }}</p>
              </div>
              <div class="sm:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Last Name <span class="text-rose-400">*</span></label>
                <input v-model="form.last_name" type="text" required placeholder="e.g. Dela Cruz" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                <p v-if="serverErrors.last_name" class="mt-1 text-xs text-rose-400">{{ serverErrors.last_name[0] }}</p>
              </div>
              <div class="sm:col-span-1">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">M.I.</label>
                <input v-model="form.middle_initial" type="text" maxlength="2" placeholder="P" class="mt-1.5 w-full text-center uppercase rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
              </div>
              <div class="sm:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Sex <span class="text-rose-400">*</span></label>
                <select v-model="form.sex" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                </select>
                <p v-if="serverErrors.sex" class="mt-1 text-xs text-rose-400">{{ serverErrors.sex[0] }}</p>
              </div>
              <div class="sm:col-span-3" :class="isCourier ? 'sm:col-span-6' : ''">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Business Name {{ isCourier ? '' : '(Optional)' }}</label>
                <div class="relative mt-1.5">
                  <Building class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="form.business_name" type="text" :placeholder="isCourier ? 'Your name or business (optional)' : 'e.g. Apex Trading Corp.'" class="w-full rounded-lg border border-slate-700 bg-slate-800/80 pl-9 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                </div>
              </div>
            </div>
          </div>

          <!-- Section 2: Contact & Security -->
          <div>
            <div class="flex items-center gap-2 border-b border-white/10 pb-3">
              <Lock class="h-5 w-5 text-teal-400" />
              <h2 class="text-base font-bold uppercase tracking-wider text-teal-300">2. Contact & Security</h2>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Email <span class="text-rose-400">*</span></label>
                <div class="relative mt-1.5">
                  <Mail class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="form.email" type="email" required placeholder="you@example.ph" class="w-full rounded-lg border border-slate-700 bg-slate-800/80 pl-9 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                </div>
                <p v-if="serverErrors.email" class="mt-1 text-xs text-rose-400">{{ serverErrors.email[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Phone <span class="text-rose-400">*</span></label>
                <div class="relative mt-1.5">
                  <Phone class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
                  <input v-model="form.phone_number" type="tel" inputmode="numeric" maxlength="11" pattern="[0-9]{11}" required placeholder="09171234567" @input="form.phone_number = form.phone_number.replace(/\D/g, '').slice(0, 11)" class="w-full rounded-lg border border-slate-700 bg-slate-800/80 pl-9 pr-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                </div>
                <p v-if="serverErrors.phone_number" class="mt-1 text-xs text-rose-400">{{ serverErrors.phone_number[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Password <span class="text-rose-400">*</span></label>
                <div class="relative mt-1.5">
                  <input v-model="form.password" :type="showPassword ? 'text' : 'password'" required placeholder="Min. 8 characters" class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 pr-10 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                  <button type="button" class="absolute right-3 top-3 text-slate-400 hover:text-slate-200" @click="showPassword = !showPassword">
                    <component :is="showPassword ? EyeOff : Eye" class="h-4 w-4" />
                  </button>
                </div>
                <p v-if="serverErrors.password" class="mt-1 text-xs text-rose-400">{{ serverErrors.password[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Confirm Password <span class="text-rose-400">*</span></label>
                <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'" required placeholder="Re-enter password" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
              </div>
            </div>
          </div>

          <!-- Section 3: Birthdate & Age -->
          <div>
            <div class="flex items-center gap-2 border-b border-white/10 pb-3">
              <Calendar class="h-5 w-5 text-teal-400" />
              <h2 class="text-base font-bold uppercase tracking-wider text-teal-300">3. Date of Birth</h2>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Birthdate <span class="text-rose-400">*</span></label>
                <input v-model="form.birthdate" type="date" required :max="new Date().toISOString().split('T')[0]" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                <p v-if="serverErrors.birthdate" class="mt-1 text-xs text-rose-400">{{ serverErrors.birthdate[0] }}</p>
                <p v-if="isUnderage" class="mt-1 text-xs font-bold text-rose-400">Must be at least 18 years old.</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Age (Auto)</label>
                <div class="relative mt-1.5">
                  <input :value="computedAge !== null ? `${computedAge} years old` : 'Select birthdate'" type="text" readonly class="w-full rounded-lg border border-slate-700 bg-slate-800/40 px-3.5 py-2.5 font-mono text-sm text-slate-300 cursor-not-allowed" :class="isUnderage ? 'border-rose-500 text-rose-300' : ''" />
                  <CheckCircle2 v-if="computedAge !== null && !isUnderage" class="absolute right-3 top-3 h-4 w-4 text-emerald-400" />
                </div>
              </div>
            </div>
          </div>

          <!-- Section 4: Address -->
          <div>
            <div class="flex items-center gap-2 border-b border-white/10 pb-3">
              <MapPin class="h-5 w-5 text-teal-400" />
              <h2 class="text-base font-bold uppercase tracking-wider text-teal-300">4. Address</h2>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
              <div>
                <label class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-300">Province <span class="text-rose-400">*</span><Loader2 v-if="loadingProvinces" class="h-3 w-3 animate-spin text-teal-400" /></label>
                <select v-model="form.province" @change="onProvinceChange" required :disabled="loadingProvinces || !provinces.length" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:opacity-50">
                  <option value="" disabled>Select Province</option>
                  <option v-for="prov in provinces" :key="prov.code" :value="prov.name">{{ prov.name }}</option>
                </select>
                <p v-if="serverErrors.province" class="mt-1 text-xs text-rose-400">{{ serverErrors.province[0] }}</p>
              </div>
              <div>
                <label class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-300">City <span class="text-rose-400">*</span><Loader2 v-if="loadingCities" class="h-3 w-3 animate-spin text-teal-400" /></label>
                <select v-model="form.city_municipality" @change="onCityChange" :disabled="loadingCities || !cities.length" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white disabled:opacity-40 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                  <option value="" disabled>{{ loadingCities ? 'Loading...' : 'Select City' }}</option>
                  <option v-for="city in cities" :key="city.code" :value="city.name">{{ city.name }}</option>
                </select>
                <p v-if="serverErrors.city_municipality" class="mt-1 text-xs text-rose-400">{{ serverErrors.city_municipality[0] }}</p>
              </div>
              <div>
                <label class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-slate-300">Barangay <span class="text-rose-400">*</span><Loader2 v-if="loadingBarangays" class="h-3 w-3 animate-spin text-teal-400" /></label>
                <select v-model="form.barangay" :disabled="loadingBarangays || !barangays.length" required class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white disabled:opacity-40 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                  <option value="" disabled>{{ loadingBarangays ? 'Loading...' : 'Select Barangay' }}</option>
                  <option v-for="brgy in barangays" :key="brgy.code" :value="brgy.name">{{ brgy.name }}</option>
                </select>
                <p v-if="serverErrors.barangay" class="mt-1 text-xs text-rose-400">{{ serverErrors.barangay[0] }}</p>
              </div>
              <div class="sm:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Street Address <span class="text-rose-400">*</span></label>
                <textarea v-model="form.street_address" rows="2" required placeholder="Unit, Street, Building..." class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                <p v-if="serverErrors.street_address" class="mt-1 text-xs text-rose-400">{{ serverErrors.street_address[0] }}</p>
              </div>
            </div>
          </div>

          <!-- Section 5: Courier-specific (vehicle & license) -->
          <div v-if="isCourier">
            <div class="flex items-center gap-2 border-b border-white/10 pb-3">
              <Bike class="h-5 w-5 text-blue-400" />
              <h2 class="text-base font-bold uppercase tracking-wider text-blue-300">5. Vehicle & License</h2>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Vehicle Type <span class="text-rose-400">*</span></label>
                <div class="relative mt-1.5">
                  <select v-model="form.vehicle_type" required class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 appearance-none">
                    <option value="" disabled>Select vehicle</option>
                    <option value="motorcycle">Motorcycle</option>
                    <option value="tricycle">Tricycle</option>
                    <option value="van">Van</option>
                    <option value="truck">Truck</option>
                  </select>
                  <ChevronDown class="absolute right-3 top-3.5 h-4 w-4 text-slate-400 pointer-events-none" />
                </div>
                <p v-if="serverErrors.vehicle_type" class="mt-1 text-xs text-rose-400">{{ serverErrors.vehicle_type[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Plate Number</label>
                <input v-model="form.plate_number" type="text" placeholder="ABC 1234" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Driver License No. <span class="text-rose-400">*</span></label>
                <input v-model="form.license_number" type="text" required placeholder="D01-XX-XXXXXX" class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" />
                <p v-if="serverErrors.license_number" class="mt-1 text-xs text-rose-400">{{ serverErrors.license_number[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Assigned Hub <span class="text-rose-400">*</span></label>
                <div class="relative mt-1.5">
                  <select v-model="form.hub_id" required :disabled="loadingHubs" class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3.5 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 appearance-none">
                    <option value="" disabled>{{ loadingHubs ? 'Loading hubs...' : 'Select hub' }}</option>
                    <option v-for="hub in hubs" :key="hub.id" :value="hub.id">{{ hub.name }}</option>
                  </select>
                  <ChevronDown class="absolute right-3 top-3.5 h-4 w-4 text-slate-400 pointer-events-none" />
                </div>
                <p v-if="serverErrors.hub_id" class="mt-1 text-xs text-rose-400">{{ serverErrors.hub_id[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">License Document <span class="text-rose-400">*</span></label>
                <div class="mt-1.5 rounded-lg border-2 border-dashed border-slate-700 bg-slate-800/40 p-3 text-center hover:border-blue-500/60 transition">
                  <input type="file" accept="image/jpeg,image/png,application/pdf" required class="hidden" id="license-doc-input" @change="handleLicenseDocChange" />
                  <label for="license-doc-input" class="cursor-pointer flex flex-col items-center">
                    <Upload class="h-6 w-6 text-blue-400" />
                    <span class="mt-1.5 text-[11px] font-medium text-slate-200">{{ licenseDocFile ? licenseDocFile.name : 'Upload license' }}</span>
                    <span class="text-[10px] text-slate-400">JPEG, PNG, PDF (max 5MB)</span>
                  </label>
                </div>
                <div v-if="licenseDocPreview && licenseDocPreview !== 'pdf'" class="mt-2">
                  <img :src="licenseDocPreview" alt="License" class="h-16 rounded border border-white/10 object-cover" />
                </div>
                <p v-if="serverErrors.license_doc" class="mt-1 text-xs text-rose-400">{{ serverErrors.license_doc[0] }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Vehicle OR/CR <span class="text-rose-400">*</span></label>
                <div class="mt-1.5 rounded-lg border-2 border-dashed border-slate-700 bg-slate-800/40 p-3 text-center hover:border-blue-500/60 transition">
                  <input type="file" accept="image/jpeg,image/png,application/pdf" required class="hidden" id="vehicle-or-cr-input" @change="handleVehicleOrCrChange" />
                  <label for="vehicle-or-cr-input" class="cursor-pointer flex flex-col items-center">
                    <Upload class="h-6 w-6 text-blue-400" />
                    <span class="mt-1.5 text-[11px] font-medium text-slate-200">{{ vehicleOrCrFile ? vehicleOrCrFile.name : 'Upload OR/CR' }}</span>
                    <span class="text-[10px] text-slate-400">JPEG, PNG, PDF (max 5MB)</span>
                  </label>
                </div>
                <div v-if="vehicleOrCrPreview && vehicleOrCrPreview !== 'pdf'" class="mt-2">
                  <img :src="vehicleOrCrPreview" alt="OR/CR" class="h-16 rounded border border-white/10 object-cover" />
                </div>
                <p v-if="serverErrors.vehicle_or_cr" class="mt-1 text-xs text-rose-400">{{ serverErrors.vehicle_or_cr[0] }}</p>
              </div>
            </div>
          </div>

          <!-- Section 6: Documents (ID + Business Permit for logistics / just ID for courier) -->
          <div>
            <div class="flex items-center gap-2 border-b border-white/10 pb-3">
              <FileText class="h-5 w-5 text-teal-400" />
              <h2 class="text-base font-bold uppercase tracking-wider text-teal-300">{{ isCourier ? '5' : '5' }}. Verification Documents</h2>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Government ID <span class="text-rose-400">*</span></label>
                <div class="mt-1.5 rounded-lg border-2 border-dashed border-slate-700 bg-slate-800/40 p-4 text-center hover:border-teal-500/60 transition">
                  <input type="file" accept="image/jpeg,image/png,application/pdf" required class="hidden" id="id-document-input" @change="handleIdFileChange" />
                  <label for="id-document-input" class="cursor-pointer flex flex-col items-center">
                    <Upload class="h-8 w-8 text-teal-400" />
                    <span class="mt-2 text-xs font-medium text-slate-200">{{ idDocumentFile ? idDocumentFile.name : 'Click to upload ID' }}</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">Passport, Driver's License, PhilID</span>
                  </label>
                </div>
                <div v-if="idDocumentPreview && idDocumentPreview !== 'pdf'" class="mt-2">
                  <img :src="idDocumentPreview" alt="ID" class="h-20 rounded border border-white/10 object-cover" />
                </div>
                <p v-if="serverErrors.id_document" class="mt-1 text-xs text-rose-400">{{ serverErrors.id_document[0] }}</p>
              </div>
              <div v-if="!isCourier">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">Business Permit (Optional)</label>
                <div class="mt-1.5 rounded-lg border-2 border-dashed border-slate-700 bg-slate-800/40 p-4 text-center hover:border-teal-500/60 transition">
                  <input type="file" accept="image/jpeg,image/png,application/pdf" class="hidden" id="permit-document-input" @change="handlePermitFileChange" />
                  <label for="permit-document-input" class="cursor-pointer flex flex-col items-center">
                    <Upload class="h-8 w-8 text-teal-400" />
                    <span class="mt-2 text-xs font-medium text-slate-200">{{ businessPermitFile ? businessPermitFile.name : 'Click to upload permit' }}</span>
                    <span class="text-[10px] text-slate-400 mt-0.5">DTI, SEC, or Mayor's Permit</span>
                  </label>
                </div>
                <div v-if="businessPermitPreview && businessPermitPreview !== 'pdf'" class="mt-2">
                  <img :src="businessPermitPreview" alt="Permit" class="h-20 rounded border border-white/10 object-cover" />
                </div>
                <p v-if="serverErrors.business_permit" class="mt-1 text-xs text-rose-400">{{ serverErrors.business_permit[0] }}</p>
              </div>
            </div>
          </div>

          <!-- Submit -->
          <div class="pt-4">
            <button type="submit" :disabled="submitting || isUnderage" class="flex w-full items-center justify-center gap-2 rounded-xl py-3.5 text-sm font-bold uppercase tracking-wider shadow-lg transition disabled:opacity-50" :class="isCourier ? 'bg-blue-500 text-white shadow-blue-500/20 hover:bg-blue-400' : 'bg-teal-500 text-slate-950 shadow-teal-500/20 hover:bg-teal-400'">
              <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
              <span>{{ submitting ? 'Submitting...' : `Register as ${isCourier ? 'Courier' : 'Logistics/Seller'}` }}</span>
              <ArrowRight v-if="!submitting" class="h-4 w-4" />
            </button>
            <p class="mt-4 text-center text-xs text-slate-400">
              Already have an account?
              <router-link to="/login" class="font-bold text-teal-400 hover:underline">Sign in</router-link>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
