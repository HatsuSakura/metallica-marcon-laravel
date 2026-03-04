<template>
  <div class="mb-4">
    <Link class="btn btn-ghost" :href="route('customer.index')">
      <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
      Torna a Clienti
    </Link>
  </div>

  <form @submit.prevent="update">
    <div class="grid grid-cols-6 gap-4">
      <div class="col-span-1">
        <label class="label" for="isOccasionalCustomer">Cliente Occasionale</label>
        <input id="isOccasionalCustomer" v-model="form.isOccasionalCustomer" type="checkbox" class="toggle" />
      </div>

      <div class="col-span-5">
        <label class="label">Ragione sociale</label>
        <input v-model="form.companyName" type="text" class="input" />
        <div class="input-error" v-if="form.errors.companyName">
          {{ form.errors.companyName }}
        </div>
      </div>

      <div class="col-span-3">
        <label class="label">Partita Iva</label>
        <input v-model="form.vatNumber" type="text" class="input" />
        <div class="input-error" v-if="form.errors.vatNumber">
          {{ form.errors.vatNumber }}
        </div>
      </div>

      <div class="col-span-3">
        <label class="label">Codice Fiscale</label>
        <input v-model="form.taxCode" type="text" class="input" />
        <div class="input-error" v-if="form.errors.taxCode">
          {{ form.errors.taxCode }}
        </div>
      </div>

      <div class="col-span-4">
        <label class="label">Address legale</label>
        <input v-model="form.legalAddress" type="text" class="input" />
        <div class="input-error" v-if="form.errors.legalAddress">
          {{ form.errors.legalAddress }}
        </div>
      </div>

      <div class="col-span-1">
        <label class="label">Lat</label>
        <input v-model="form.latitude" type="text" class="input" disabled="disabled" />
        <div class="input-error" v-if="form.errors.latitude">
          {{ form.errors.latitude }}
        </div>
      </div>

      <div class="col-span-1">
        <label class="label">Lng</label>
        <input v-model="form.longitude" type="text" class="input" disabled="disabled" />
        <div class="input-error" v-if="form.errors.longitude">
          {{ form.errors.longitude }}
        </div>
      </div>

      <div class="col-span-6 min-h-64 bg-gray-500">
        <GoogleMap mapId="DEMO_MAP_ID" :api-key="mapApiKey" style="width: 100%; height: 100%" :center="mapCenter" :zoom="zoomLevel">
          <AdvancedMarker :key="1" :id="1" :options="markerOptions" @dragend="showLocation" />
        </GoogleMap>
      </div>

      <div class="col-span-2">
        <label class="label">Commerciale</label>
        <select v-model="form.sellerId" class="select select-bordered w-full max-w-xs">
          <option disabled value="">Seleziona il commerciale</option>
          <option v-for="manager in props.managers" :key="manager.id" :value="manager.id">
            {{ manager.name }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.sellerId">
          {{ form.errors.sellerId }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">Codice SDI</label>
        <input v-model="form.sdiCode" type="text" class="input" />
        <div class="input-error" v-if="form.errors.sdiCode">
          {{ form.errors.sdiCode }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">Tipologia attivita</label>
        <select v-model="form.businessType" class="select select-bordered w-full max-w-xs">
          <option disabled value="">Seleziona la tipologia attivita</option>
          <option v-for="job in props.jobTypes" :key="job.value" :value="job.value">
            {{ job.label }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.businessType">
          {{ form.errors.businessType }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">E-mail Commerciale</label>
        <input v-model="form.salesEmail" type="email" class="input" />
        <div class="input-error" v-if="form.errors.salesEmail">
          {{ form.errors.salesEmail }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">E-mail Amministrativa</label>
        <input v-model="form.administrativeEmail" type="email" class="input" />
        <div class="input-error" v-if="form.errors.administrativeEmail">
          {{ form.errors.administrativeEmail }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">PEC</label>
        <input v-model="form.certifiedEmail" type="email" class="input" />
        <div class="input-error" v-if="form.errors.certifiedEmail">
          {{ form.errors.certifiedEmail }}
        </div>
      </div>

      <div class="col-span-4">
        <label class="label">Responsabile Smaltimenti</label>
        <input v-model="form.responsabileSmaltimenti" type="text" class="input" />
        <div class="input-error" v-if="form.errors.responsabileSmaltimenti">
          {{ form.errors.responsabileSmaltimenti }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">Telefono Principale</label>
        <input v-model="form.telefonoPrincipale" type="phone" class="input" />
        <div class="input-error" v-if="form.errors.telefonoPrincipale">
          {{ form.errors.telefonoPrincipale }}
        </div>
      </div>

      <div class="col-span-2">
        <button type="submit" class="btn btn-primary">Modifica cliente</button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, watch, computed, watchEffect } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { AdvancedMarker, GoogleMap } from 'vue3-google-map';
import { CUSTOM_MARKER_ELEMENTS } from '@/googleMapsConfig';

const props = defineProps({
  customer: Object,
  managers: Array,
  jobTypes: Array,
});

const store = useStore();

const form = useForm({
  isOccasionalCustomer: Boolean(props.customer.is_occasional_customer),
  companyName: props.customer.company_name,
  vatNumber: props.customer.vat_number,
  taxCode: props.customer.tax_code,
  legalAddress: props.customer.legal_address,
  latitude: props.customer.main_site?.latitude,
  longitude: props.customer.main_site?.longitude,
  sellerId: props.customer.seller_id,
  sdiCode: props.customer.sdi_code,
  businessType: props.customer.business_type,
  salesEmail: props.customer.sales_email,
  administrativeEmail: props.customer.administrative_email,
  certifiedEmail: props.customer.certified_email,
  responsabileSmaltimenti: props.customer.responsabile_smaltimenti,
  telefonoPrincipale: props.customer.telefono_principale,
});

const pinSvg = ref(null);
watchEffect(() => {
  const pinSvgContent =
    `<svg xmlns="http://www.w3.org/2000/svg" height="90" viewBox="${CUSTOM_MARKER_ELEMENTS.PinSvgViewBox}">
      <path fill="${CUSTOM_MARKER_ELEMENTS.fillColor5075}" stroke="${CUSTOM_MARKER_ELEMENTS.strokeColor5075}" stroke-width="2" d="${CUSTOM_MARKER_ELEMENTS.PinSvgPath}"/>
      <g transform="translate(7, 8) scale(0.03)">
        <path fill="white" d="${CUSTOM_MARKER_ELEMENTS.HouseSvgPath}"/>
      </g>
    </svg>`;
  pinSvg.value = new DOMParser().parseFromString(pinSvgContent, 'image/svg+xml').documentElement;
});

const mapCenter = computed(() => ({
  lat: form.latitude ? form.latitude : 45.7095677,
  lng: form.longitude ? form.longitude : 12.2230432,
}));

const zoomLevel = 16;
const mapApiKey = import.meta.env.VITE_MAPS_API_KEY;

const markerOptions = computed(() => ({
  id: 1,
  position: {
    lat: form.latitude ? form.latitude : 0,
    lng: form.longitude ? form.longitude : 0,
  },
  title: `${form.legalAddress}`,
  content: pinSvg.value,
  gmpClickable: true,
  gmpDraggable: true,
}));

const showLocation = (evt) => {
  form.latitude = evt.latLng.lat();
  form.longitude = evt.latLng.lng();
};

const getCoordinates = async () => {
  if (!form.legalAddress) {
    return;
  }

  try {
    const response = await axios.get('https://maps.googleapis.com/maps/api/geocode/json', {
      params: {
        address: form.legalAddress,
        key: mapApiKey,
      },
    });

    const location = response.data.results[0].geometry.location;
    form.latitude = location.lat;
    form.longitude = location.lng;
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Coordinate reperite correttamente' });
  } catch (error) {
    console.error('Error fetching coordinates:', error);
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Impossibile reperire le coordinate per questo address' });
  }
};

const debouncedGetCoordinates = debounce(getCoordinates, 1500);

watch(() => form.legalAddress, (newAddress) => {
  if (newAddress) {
    debouncedGetCoordinates();
  }
});

const update = () => form.put(route('customer.update', { customer: props.customer.id }), { method: 'patch' });
</script>

