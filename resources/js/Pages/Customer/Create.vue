<template>
  <div class="mb-4">
    <Link class="btn btn-ghost" :href="route('customer.index')">
      <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
      Torna a Clienti
    </Link>
  </div>

  <form @submit.prevent="create">
    <div class="grid grid-cols-6 gap-2">
      <div class="col-span-1">
        <label class="label" for="is_occasional_customer">Cliente Occasionale</label>
        <input id="is_occasional_customer" v-model="form.is_occasional_customer" type="checkbox" class="toggle" />
      </div>

      <div class="col-span-5">
        <label class="label">Ragione sociale</label>
        <input v-model="form.company_name" type="text" class="input" />
        <div class="input-error" v-if="form.errors.company_name">
          {{ form.errors.company_name }}
        </div>
      </div>

      <div class="col-span-3">
        <label class="label">Partita Iva</label>
        <input v-model="form.vat_number" type="text" class="input" />
        <div class="input-error" v-if="form.errors.vat_number">
          {{ form.errors.vat_number }}
        </div>
      </div>

      <div class="col-span-3">
        <label class="label">Codice Fiscale</label>
        <input v-model="form.tax_code" type="text" class="input" />
        <div class="input-error" v-if="form.errors.tax_code">
          {{ form.errors.tax_code }}
        </div>
      </div>

      <div class="col-span-4">
        <label class="label">Address legale</label>
        <input v-model="form.legal_address" type="text" class="input" />
        <div class="input-error" v-if="form.errors.legal_address">
          {{ form.errors.legal_address }}
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
        <select v-model="form.seller_id" class="select select-bordered w-full max-w-xs">
          <option :value="null" disabled>Seleziona il commerciale</option>
          <option v-for="manager in props.managers" :key="manager.id" :value="manager.id">
            {{ manager.name }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.seller_id">
          {{ form.errors.seller_id }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">Codice SDI</label>
        <input v-model="form.sdi_code" type="text" class="input" />
        <div class="input-error" v-if="form.errors.sdi_code">
          {{ form.errors.sdi_code }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">Tipologia attivita</label>
        <select v-model="form.business_type" class="select select-bordered w-full max-w-xs">
          <option :value="null" disabled>Seleziona la tipologia attivita</option>
          <option v-for="job in props.jobTypes" :key="job.value" :value="job.value">
            {{ job.label }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.business_type">
          {{ form.errors.business_type }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">E-mail Commerciale</label>
        <input v-model="form.sales_email" type="email" class="input" />
        <div class="input-error" v-if="form.errors.sales_email">
          {{ form.errors.sales_email }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">E-mail Amministrativa</label>
        <input v-model="form.administrative_email" type="email" class="input" />
        <div class="input-error" v-if="form.errors.administrative_email">
          {{ form.errors.administrative_email }}
        </div>
      </div>

      <div class="col-span-2">
        <label class="label">PEC</label>
        <input v-model="form.certified_email" type="email" class="input" />
        <div class="input-error" v-if="form.errors.certified_email">
          {{ form.errors.certified_email }}
        </div>
      </div>

      <div class="col-span-6">
        <label class="label">Note Cliente</label>
        <textarea
          v-model="form.notes"
          class="textarea textarea-bordered w-full"
          rows="4"
          placeholder="Inserisci eventuali note operative sul cliente"
        />
        <div class="input-error" v-if="form.errors.notes">
          {{ form.errors.notes }}
        </div>
      </div>

      <div class="col-span-2">
        <button type="submit" class="btn btn-primary">Crea nuovo cliente</button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import { AdvancedMarker, GoogleMap } from 'vue3-google-map';
import { CUSTOM_MARKER_ELEMENTS } from '@/googleMapsConfig';
import { useDebouncedGeocoding } from '@/Composables/useDebouncedGeocoding';

const props = defineProps({
  managers: Array,
  jobTypes: Array,
});

const store = useStore();

const form = useForm({
  is_occasional_customer: null,
  company_name: null,
  vat_number: null,
  tax_code: null,
  legal_address: null,
  latitude: null,
  longitude: null,
  seller_id: null,
  sdi_code: null,
  business_type: null,
  sales_email: null,
  administrative_email: null,
  certified_email: null,
  notes: null,
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
  title: `${form.legal_address}`,
  content: pinSvg.value,
  gmpClickable: true,
  gmpDraggable: true,
}));

const showLocation = (evt) => {
  form.latitude = evt.latLng.lat();
  form.longitude = evt.latLng.lng();
};

useDebouncedGeocoding({
  sourceRef: () => form.legal_address,
  apiKey: mapApiKey,
  onResolved: (location) => {
    form.latitude = location.lat;
    form.longitude = location.lng;
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Coordinate reperite correttamente' });
  },
  onError: (error) => {
    console.error('Error fetching coordinates:', error);
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Impossibile reperire le coordinate per questo address' });
  },
});

const create = () => {
  form.post(route('customer.store'), {
    onError: (errors) => {
      const firstMessage = Object.values(errors || {}).find(Boolean);
      store.dispatch('flash/queueMessage', {
        type: 'error',
        text: firstMessage || 'Compila i campi obbligatori evidenziati.',
      });
    },
  });
};
</script>
