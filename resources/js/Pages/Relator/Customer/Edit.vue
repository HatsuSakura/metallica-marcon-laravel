<template>
    <div class="mb-4">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.customer.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna a Clienti
        </Link>
    </div>
  
    <form @submit.prevent="update">
      <div class="grid grid-cols-6 gap-4">

      <div class="col-span-1">
        <label class="label" for="customerOccasionale">Cliente Occasionale</label>
        <input v-model="form.customerOccasionale" id="customerOccasionale" type="checkbox" class="toggle"/>
      </div>

        <div class="col-span-5">
          <label class="label">Ragione sociale</label>
          <input v-model="form.ragioneSociale" type="text" class="input"/> 
          <div class="input-error" v-if="form.errors.ragioneSociale">
            {{ form.errors.ragioneSociale }}
          </div>
        </div>
  
        <div class="col-span-3">
          <label class="label">Partita Iva</label>
          <input v-model="form.partitaIva" type="text" class="input"/> 
          <div class="input-error" v-if="form.errors.partitaIva">
            {{ form.errors.partitaIva }}
          </div>
        </div>
  
        <div class="col-span-3">
          <label class="label">Codice Fiscale</label>
          <input v-model="form.codiceFiscale" type="text" class="input"/> 
          <div class="input-error" v-if="form.errors.codiceFiscale">
            {{ form.errors.codiceFiscale }}
          </div>
        </div>
  
        <div class="col-span-4">
          <label class="label">Indirizzo Legale</label>
          <input v-model="form.indirizzoLegale" type="text" class="input"/> 
          <div class="input-error" v-if="form.errors.indirizzoLegale">
            {{ form.errors.indirizzoLegale }}
          </div>
        </div>

        <div class="col-span-1">
          <label class="label">Lat</label>
          <input v-model="form.lat" type="text" class="input" disabled="disabled"/> 
          <div class="input-error" v-if="form.errors.lat">
            {{ form.errors.lat }}
          </div>
        </div>

        <div class="col-span-1">
          <label class="label">Lng</label>
          <input v-model="form.lng" type="text" class="input" disabled="disabled"/>  
          <div class="input-error" v-if="form.errors.lng">
            {{ form.errors.lng }}
          </div>
        </div>

        <div class="col-span-6 min-h-64 bg-gray-500">

          <GoogleMap mapId="DEMO_MAP_ID" :api-key=mapApiKey style="width: 100%; height: 100%" :center="mapCenter" :zoom="zoomLevel">
            <AdvancedMarker :key="1" :id="1" :options="markerOptions"
            @dragend="showLocation"/>              
          </GoogleMap>



        </div>
  
        <div class="col-span-2">
          <label class="label">Commerciale</label>
          <select v-model="form.seller_id" id="manager" class="select select-bordered w-full max-w-xs">
          <option disabled value="">Seleziona il commerciale</option>
          <option v-for="manager in props.managers" :key="manager.id" :value="manager.id">
            {{ manager.name }} <!-- Assuming user model has a 'name' field -->
          </option>
        </select>
        </div>
  
        <div class="col-span-2">
          <label class="label">Codice SDI</label>
          <input v-model="form.codiceSdi" type="text" class="input"/> 
          <div class="input-error" v-if="form.errors.codiceSdi">
            {{ form.errors.codiceSdi }}
          </div>
        </div>
  
        <div class="col-span-2">
          <label class="label">Tipolgia attività</label>
          <select v-model="form.jobType" id="jobType" class="select select-bordered w-full max-w-xs">
          <option disabled value="">Seleziona la tipologia attività</option>
          <option v-for="job in props.jobTypes" :key="job.value" :value="job.value">
            {{ job.label }} <!-- Assuming user model has a 'name' field -->
          </option>
        </select>
          <div class="input-error" v-if="form.errors.jobType">
            {{ form.errors.jobType }}
          </div>
        </div>
  
        <div class="col-span-2">
          <label class="label">E-mail Commerciale</label>
          <input v-model="form.emailCommerciale" type="email" class="input"/> 
          <div class="input-error" v-if="form.errors.emailCommerciale">
            {{ form.errors.emailCommerciale }}
          </div>
        </div>

        <div class="col-span-2">
          <label class="label">E-mail Amministrativa</label>
          <input v-model="form.emailAmministrativa" type="email" class="input"/> 
          <div class="input-error" v-if="form.errors.emailAmministrativa">
            {{ form.errors.emailAmministrativa }}
          </div>
        </div>
  
        <div class="col-span-2">
          <label class="label">PEC</label>
          <input v-model="form.pec" type="email" class="input"/> 
          <div class="input-error" v-if="form.errors.pec">
            {{ form.errors.pec }}
          </div>
        </div>

        <div class="col-span-4">
          <label class="label">Responsabile Smaltimenti</label>
          <input v-model="form.responsabileSmaltimenti" type="text" class="input"/> 
          <div class="input-error" v-if="form.errors.responsabileSmaltimenti">
            {{ form.errors.responsabileSmaltimenti }}
          </div>
        </div>

        <div class="col-span-2">
          <label class="label">Telefono Principale</label>
          <input v-model="form.telefonoPrincipale" type="phone" class="input"/> 
          <div class="input-error" v-if="form.errors.telefonoPrincipale">
            {{ form.errors.telefonoPrincipale }}
          </div>
        </div>


        <div  class="col-span-2">
          <button type="submit" class="btn btn-primary">Modifica cliente</button>
        </div>
      </div>
    </form>
  </template>
  
  <script setup>
  import { ref, watch, computed, watchEffect } from 'vue';
  import { Link, useForm } from '@inertiajs/vue3'
  import { useStore } from 'vuex';
  import dayjs from 'dayjs';
  import axios from 'axios';
  import debounce from 'lodash/debounce'; 
  import { AdvancedMarker, GoogleMap  } from 'vue3-google-map';
  import {CUSTOM_MARKER_ELEMENTS} from "@/googleMapsConfig"


  const props = defineProps({
    customer: Object,
    managers: Array,
    jobTypes: Array,
})

const store = useStore();

  const form = useForm({
    customerOccasionale: Boolean(props.customer.customer_occasionale),
    ragioneSociale: props.customer.ragione_sociale,
    partitaIva: props.customer.partita_iva,
    codiceFiscale: props.customer.codice_fiscale,
    indirizzoLegale: props.customer.indirizzo_legale,
    lat: props.customer.main_site.lat,
    lng: props.customer.main_site.lng,
    seller_id: props.customer.seller_id,
    codiceSdi: props.customer.codice_sdi,
    jobType: props.customer.job_type,
    emailCommerciale: props.customer.email_commerciale,
    emailAmministrativa: props.customer.email_amministrativa,
    pec: props.customer.pec,
    responsabileSmaltimenti: props.customer.responsabile_smaltimenti,
    telefonoPrincipale: props.customer.telefono_principale,
  })



// Reference to hold the generated SVG content
const pinSvg = ref(null);

// Generating SVG content
watchEffect(() => {
  const pinSvgContent =
    `<svg xmlns="http://www.w3.org/2000/svg"  height="90" viewBox="${CUSTOM_MARKER_ELEMENTS.PinSvgViewBox}">
      <path fill="${CUSTOM_MARKER_ELEMENTS.fillColor5075}" stroke="${CUSTOM_MARKER_ELEMENTS.strokeColor5075}" stroke-width="2" d="${CUSTOM_MARKER_ELEMENTS.PinSvgPath}"/>
      <g transform="translate(7, 8) scale(0.03)">
        <path fill="white" d="${CUSTOM_MARKER_ELEMENTS.HouseSvgPath}"/>
      </g>
    </svg>`;

  pinSvg.value = new DOMParser().parseFromString(pinSvgContent, "image/svg+xml").documentElement;
});

// Set the map center
// Computed property per aggiornare automaticamente il centro della mappa
const mapCenter = computed(() => ({
  lat: form.lat ? form.lat : 45.7095677,
  lng: form.lng ? form.lng : 12.2230432
}));
const zoomLevel = 16;
const mapApiKey = import.meta.env.VITE_MAPS_API_KEY;

const markerOptions = computed(() => ({
  id: 1,
  position: { 
    lat: form.lat ? form.lat : 0, 
    lng: form.lng ? form.lng : 0 },
  title: `${form.indirizzoLegale}`,
  content: pinSvg.value,
  gmpClickable: true, // Ensures the marker is clickable
  gmpDraggable: true
}));

const showLocation = (evt) => {
  console.log( evt.latLng.toString());
  form.lat = evt.latLng.lat();
  form.lng = evt.latLng.lng();
};

// Function to get coordinates from Google Geocoding API
const getCoordinates = async () => {
  if (form.indirizzoLegale) {
    try {
      // Call Google Geocoding API
      const response = await axios.get('https://maps.googleapis.com/maps/api/geocode/json', {
        params: {
          address: form.indirizzoLegale,
          key: mapApiKey
        }
      });

      const location = response.data.results[0].geometry.location;
      form.lat = location.lat;
      form.lng = location.lng;

      console.log('Coordinate reperite correttamente')
      // Queue success message
      store.dispatch('flash/queueMessage', { type: 'success', text: 'Coordinate reperite correttamente' });

    } catch (error) {
      console.error('Error fetching coordinates:', error);
      // Queue error message
      store.dispatch('flash/queueMessage', { type: 'error', text: 'Impossibile reperire le coordinate per questo indirizzo ' });
    }
  }
};

// Debounced version of getCoordinates
const debouncedGetCoordinates = debounce(getCoordinates, 1500); // 500ms debounce

// Watch for changes in indirizzoLegale and call getCoordinates
watch(() => form.indirizzoLegale, (newAddress) => {
  if (newAddress) {
    debouncedGetCoordinates();
  }
});

const update = () => form.put( route('relator.customer.update', {customer:props.customer.id}) , 
    {method: 'patch'}
);

  </script>
  