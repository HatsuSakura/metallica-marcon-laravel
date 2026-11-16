<template>
<RegistryPage title="Nuovo cliente">
<template #back><Link class="btn btn-outline" :href="route('customer.index')">
      <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
      Torna a Clienti
    </Link></template>



  <RegistryForm :form="form" v-slot="{canSubmit, busy}" @submit="create" >
    <CustomerFields :form="form" :managers="props.managers" :business-types="props.businessTypes" @address-input="manualPosition = false">
      <template #map><LocationPicker :latitude="form.latitude" :longitude="form.longitude" :address="form.legal_address" :disabled="busy" @position-change="moveMarker" /></template>
      <template #actions>
        <Link :href="route('customer.index')" class="btn btn-error btn-outline mr-2">Annulla</Link>
<button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined"><span v-if="busy" class="loading loading-spinner loading-sm" aria-hidden="true"></span>Crea nuovo cliente</button>
      </template>
    </CustomerFields>
  </RegistryForm>

</RegistryPage>
</template>

<script setup>
import CustomerFields from './Components/CustomerFields.vue'

import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

import {ref, computed} from 'vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import { useDebouncedGeocoding } from '@/Composables/useDebouncedGeocoding';

const props = defineProps({
  managers: Array,
  businessTypes: { type: Array, default: () => [] },
});

const store = useStore();

const form = useForm({
  is_occasional_customer: false,
  company_name: null,
  vat_number: null,
  tax_code: null,
  legal_address: null,
  latitude: null,
  longitude: null,
  seller_id: null,
  sdi_code: null,
  business_type_id: null,
  sales_email: null,
  administrative_email: null,
  certified_email: null,
  notes: null,
});

const mapApiKey = import.meta.env.VITE_MAPS_API_KEY;
const manualPosition = ref(false);
const moveMarker = position => { manualPosition.value = true; Object.assign(form, position); };
useDebouncedGeocoding({
  sourceRef: () => form.legal_address,
  apiKey: mapApiKey,
  onResolved: (location, address) => {
    if (manualPosition.value || form.legal_address?.trim() !== address) return;
    form.latitude = location.lat;
    form.longitude = location.lng;
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Coordinate reperite correttamente' });
  },
  onError: (error) => {
    console.error('Error fetching coordinates:', error);
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Impossibile reperire le coordinate per questo indirizzo' });
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
