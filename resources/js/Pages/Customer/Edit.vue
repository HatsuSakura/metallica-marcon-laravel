<template>
  <div class="mb-4 flex items-center justify-between gap-2">
    <Link class="btn btn-ghost" :href="backHref">
      <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
      Torna indietro
    </Link>
    <button type="button" class="btn btn-primary btn-sm" @click="openAddSiteModal">
      <font-awesome-icon :icon="['fas', 'plus']" />
      Aggiungi sede
    </button>
  </div>

  <form @submit.prevent="update">
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
        <button type="submit" class="btn btn-primary">Modifica cliente</button>
      </div>
    </div>
  </form>

  <div class="mt-6">
    <h2 class="text-lg font-semibold">Sedi collegate</h2>
    <div class="mt-2 space-y-2">
      <div
        v-for="site in props.customer.sites"
        :key="site.id"
        class="rounded-box border border-base-300 bg-base-100 p-3 flex items-center justify-between gap-3"
      >
        <div>
          <div class="font-medium">
            {{ site.name }}
            <span v-if="site.is_main" class="badge badge-sm badge-primary ml-2">Principale</span>
          </div>
          <div class="text-sm opacity-70">{{ site.address }}</div>
        </div>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="btn btn-primary btn-sm btn-outline"
            @click="openEditSiteModal(site)"
          >
            Modifica
          </button>
          <button
            type="button"
            class="btn btn-error btn-sm btn-outline"
            :disabled="Boolean(site.is_main)"
            :title="site.is_main ? 'Non puoi eliminare la sede principale' : 'Elimina sede'"
            @click="deleteSite(site.id)"
          >
            Elimina
          </button>
        </div>
      </div>
    </div>
  </div>

  <AuditCollapse
    :audits="props.audits ?? []"
    :is-admin="Boolean($page.props.user?.is_admin)"
    :field-labels="auditFieldLabels"
  />

  <dialog ref="addSiteDialog" class="modal">
    <div class="modal-box w-11/12 max-w-5xl">
      <h3 class="font-bold text-lg mb-3">{{ isEditingSite ? 'Modifica sede' : 'Aggiungi sede' }}</h3>
      <form class="space-y-3" @submit.prevent="saveSite">
        <div>
          <label class="label">Nome sede</label>
          <input v-model="siteForm.name" type="text" class="input input-bordered w-full" />
          <div class="input-error" v-if="siteForm.errors.name">{{ siteForm.errors.name }}</div>
        </div>
        <div>
          <label class="label">Indirizzo</label>
          <input v-model="siteForm.address" type="text" class="input input-bordered w-full" />
          <div class="input-error" v-if="siteForm.errors.address">{{ siteForm.errors.address }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="label">Lat</label>
            <input v-model="siteForm.latitude" type="number" step="any" class="input input-bordered w-full" disabled="disabled" />
            <div class="input-error" v-if="siteForm.errors.latitude">{{ siteForm.errors.latitude }}</div>
          </div>
          <div>
            <label class="label">Lng</label>
            <input v-model="siteForm.longitude" type="number" step="any" class="input input-bordered w-full" disabled="disabled" />
            <div class="input-error" v-if="siteForm.errors.longitude">{{ siteForm.errors.longitude }}</div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <SiteBooleanParameter
            title="Consulente ADR"
            :booleanValue="siteForm.has_adr_consultant"
            iconString="vial-circle-check"
            :isEditable="true"
            @update:booleanValue="siteForm.has_adr_consultant = $event"
          />
          <SiteBooleanParameter
            title="Muletto"
            :booleanValue="siteForm.has_muletto"
            iconString="truck-ramp-box"
            :isEditable="true"
            @update:booleanValue="siteForm.has_muletto = $event"
          />
          <SiteBooleanParameter
            title="Transpallet Elettrico"
            :booleanValue="siteForm.has_electric_pallet_truck"
            iconString="cart-flatbed"
            :isEditable="true"
            @update:booleanValue="siteForm.has_electric_pallet_truck = $event"
          />
          <SiteBooleanParameter
            title="Transpallet Manuale"
            :booleanValue="siteForm.has_manual_pallet_truck"
            iconString="dolly"
            :isEditable="true"
            @update:booleanValue="siteForm.has_manual_pallet_truck = $event"
          />
        </div>
        <div>
          <label class="label">Altri macchinari</label>
          <textarea
            v-model="siteForm.other_machines"
            class="textarea textarea-bordered w-full"
            rows="3"
            placeholder="Inserisci eventuali altri macchinari"
          />
          <div class="input-error" v-if="siteForm.errors.other_machines">{{ siteForm.errors.other_machines }}</div>
        </div>
        <div>
          <label class="label">Note Sede</label>
          <textarea
            v-model="siteForm.notes"
            class="textarea textarea-bordered w-full"
            rows="3"
            placeholder="Inserisci eventuali note operative sulla sede"
          />
          <div class="input-error" v-if="siteForm.errors.notes">{{ siteForm.errors.notes }}</div>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" class="btn btn-ghost" @click="closeAddSiteModal">Annulla</button>
          <button type="submit" class="btn btn-primary" :disabled="siteForm.processing">
            {{ isEditingSite ? 'Aggiorna sede' : 'Salva sede' }}
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button>close</button>
    </form>
  </dialog>
</template>

<script setup>
import { ref, computed, watchEffect } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import { AdvancedMarker, GoogleMap } from 'vue3-google-map';
import { CUSTOM_MARKER_ELEMENTS } from '@/googleMapsConfig';
import SiteBooleanParameter from './Components/SiteBooleanParameter.vue';
import { useDebouncedGeocoding } from '@/Composables/useDebouncedGeocoding';
import AuditCollapse from '@/Components/AuditCollapse.vue';

const props = defineProps({
  customer: Object,
  managers: Array,
  jobTypes: Array,
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
  business_type: 'Tipologia attività',
  sales_email: 'Email commerciale',
  administrative_email: 'Email amministrativa',
  certified_email: 'PEC',
  notes: 'Note',
};

const store = useStore();
const addSiteDialog = ref(null);
const editingSiteId = ref(null);

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
  business_type: props.customer.business_type ?? null,
  sales_email: props.customer.sales_email,
  administrative_email: props.customer.administrative_email,
  certified_email: props.customer.certified_email,
  notes: props.customer.notes ?? '',
  return_to: props.returnTo ?? route('customer.index'),
});

const siteForm = useForm({
  customer_id: props.customer.id,
  name: '',
  address: props.customer.legal_address ?? '',
  latitude: props.customer.main_site?.latitude ?? null,
  longitude: props.customer.main_site?.longitude ?? null,
  is_main: false,
  has_muletto: false,
  has_electric_pallet_truck: false,
  has_manual_pallet_truck: false,
  other_machines: '',
  has_adr_consultant: false,
  notes: '',
});

const isEditingSite = computed(() => Boolean(editingSiteId.value));

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
const backHref = computed(() => props.returnTo ?? route('customer.index'));

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

useDebouncedGeocoding({
  sourceRef: () => siteForm.address,
  apiKey: mapApiKey,
  immediate: true,
  onResolved: (location) => {
    siteForm.latitude = location.lat;
    siteForm.longitude = location.lng;
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

const resetSiteFormForCreate = () => {
  editingSiteId.value = null;
  siteForm.reset();
  siteForm.customer_id = props.customer.id;
  siteForm.address = props.customer.legal_address ?? '';
  siteForm.latitude = props.customer.main_site?.latitude ?? null;
  siteForm.longitude = props.customer.main_site?.longitude ?? null;
  siteForm.is_main = false;
  siteForm.notes = '';
  siteForm.clearErrors();
};

const openAddSiteModal = () => {
  resetSiteFormForCreate();
  addSiteDialog.value?.showModal();
};

const openEditSiteModal = (site) => {
  editingSiteId.value = site.id;
  siteForm.customer_id = site.customer_id ?? props.customer.id;
  siteForm.name = site.name ?? '';
  siteForm.address = site.address ?? '';
  siteForm.latitude = site.latitude ?? null;
  siteForm.longitude = site.longitude ?? null;
  siteForm.is_main = Boolean(site.is_main);
  siteForm.has_muletto = Boolean(site.has_muletto);
  siteForm.has_electric_pallet_truck = Boolean(site.has_electric_pallet_truck);
  siteForm.has_manual_pallet_truck = Boolean(site.has_manual_pallet_truck);
  siteForm.other_machines = site.other_machines ?? '';
  siteForm.has_adr_consultant = Boolean(site.has_adr_consultant);
  siteForm.notes = site.notes ?? '';
  siteForm.clearErrors();
  addSiteDialog.value?.showModal();
};

const closeAddSiteModal = () => {
  editingSiteId.value = null;
  addSiteDialog.value?.close();
};

const saveSite = () => {
  if (isEditingSite.value) {
    siteForm.put(route('site.update', { site: editingSiteId.value }), {
      preserveScroll: true,
      onSuccess: () => {
        closeAddSiteModal();
        resetSiteFormForCreate();
      },
      onError: (errors) => pushValidationErrorBanner(errors, 'Errore di validazione durante l\'aggiornamento della sede.'),
    });
    return;
  }

  siteForm.post(route('site.store'), {
    preserveScroll: true,
    onSuccess: () => {
      closeAddSiteModal();
      resetSiteFormForCreate();
    },
    onError: (errors) => pushValidationErrorBanner(errors, 'Errore di validazione durante la creazione della sede.'),
  });
};

const deleteSite = (siteId) => {
  if (!confirm('Confermi eliminazione sede?')) {
    return;
  }

  router.delete(route('site.destroy', { site: siteId }), {
    preserveScroll: true,
  });
};
</script>
