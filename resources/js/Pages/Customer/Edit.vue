<template>
<RegistryPage title="Modifica cliente">
<template #back><Link class="btn btn-outline" :href="backHref">
      <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
      Torna alla pagina precedente
    </Link></template>



  <RegistryForm :form="form" editing v-slot="{canSubmit, busy}" @submit="update" >
    <CustomerFields :form="form" :managers="props.managers" :business-types="props.businessTypes" @address-input="manualPosition = false">
      <template #map><LocationPicker :latitude="form.latitude" :longitude="form.longitude" :address="form.legal_address" :disabled="busy" @position-change="moveMarker" /></template>
      <template #actions>
        <Link :href="backHref" class="btn btn-error btn-outline mr-2">Annulla</Link>
<button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined"><span v-if="busy" class="loading loading-spinner loading-sm" aria-hidden="true"></span>Salva modifiche</button>
      </template>
    </CustomerFields>
  </RegistryForm>

  <div class="border-t border-base-300 pt-4"><Link class="btn btn-primary btn-outline" :href="route('customer.show', {customer:props.customer.id})">Gestisci sedi e referenti</Link></div>

  <AuditCollapse
    :audits="props.audits ?? []"
    :is-admin="Boolean($page.props.user?.is_admin)"
    :field-labels="auditFieldLabels"
  />



</RegistryPage>
</template>

<script setup>
import CustomerFields from './Components/CustomerFields.vue'

import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

import {ref, computed} from 'vue';
import LocationPicker from '@/Components/LocationPicker.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import { useDebouncedGeocoding } from '@/Composables/useDebouncedGeocoding';
import AuditCollapse from '@/Components/AuditCollapse.vue';

const props = defineProps({
  customer: Object,
  managers: Array,
  businessTypes: { type: Array, default: () => [] },
  returnTo: String,
  audits: { type: Array, default: () => [] },
});

const auditFieldLabels = {
  is_occasional_customer: 'Cliente occasionale',
  company_name: 'Ragione sociale',
  vat_number: 'Partita IVA',
  tax_code: 'Codice fiscale',
  seller_id: 'Commerciale',
  legal_address: 'Indirizzo legale',
  sdi_code: 'Codice SDI',
  business_type_id: 'Tipologia attività',
  sales_email: 'Email commerciale',
  administrative_email: 'Email amministrativa',
  certified_email: 'PEC',
  notes: 'Note',
};

const store = useStore();

const form = useForm({
  is_occasional_customer: Boolean(props.customer.is_occasional_customer),
  company_name: props.customer.company_name,
  vat_number: props.customer.vat_number,
  tax_code: props.customer.tax_code,
  legal_address: props.customer.legal_address,
  latitude: props.customer.main_site?.latitude,
  longitude: props.customer.main_site?.longitude,
  seller_id: props.customer.seller_id ?? null,
  sdi_code: props.customer.sdi_code,
  business_type_id: props.customer.business_type_id ?? null,
  sales_email: props.customer.sales_email,
  administrative_email: props.customer.administrative_email,
  certified_email: props.customer.certified_email,
  notes: props.customer.notes ?? '',
  return_to: props.returnTo ?? route('customer.index'),
});

const mapApiKey = import.meta.env.VITE_MAPS_API_KEY;
const manualPosition = ref(false);
const moveMarker = position => { manualPosition.value = true; Object.assign(form, position); };
const backHref = computed(() => props.returnTo ?? route('customer.index'));
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

const pushValidationErrorBanner = (errors, fallbackText = 'Compila i campi obbligatori evidenziati.') => {
  const firstMessage = Object.values(errors || {}).find(Boolean);
  store.dispatch('flash/queueMessage', {
    type: 'error',
    text: firstMessage || fallbackText,
  });
};

const update = () => form.put(route('customer.update', { customer: props.customer.id }), {
  method: 'patch',
  onError: (errors) => pushValidationErrorBanner(errors),
});

</script>
