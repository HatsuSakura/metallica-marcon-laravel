<template>
  <dialog ref="addSiteDialog" class="modal" aria-label="Dati sede" @close="!addSiteDialog.open && (siteEditorOpen = false)" @cancel="siteForm.processing ? $event.preventDefault() : (siteEditorOpen = false)">
    <div class="modal-box w-11/12 max-w-5xl">
      <h3 class="font-bold text-lg mb-3">{{ isEditingSite ? 'Modifica dati sede' : 'Aggiungi sede' }}</h3>
      <RegistryForm v-if="siteEditorOpen" :key="editingSiteId ?? 'new'" :form="siteForm" :editing="isEditingSite" v-slot="{canSubmit, busy}" class="space-y-3" @submit="saveSite">
        <div>
          <label class="label" for="registry-customer-siteForm-name">Nome sede</label>
          <input v-model="siteForm.name" type="text" class="input input-bordered w-full"  id="registry-customer-siteForm-name" :aria-invalid="!!siteForm.errors.name" :aria-describedby="siteForm.errors.name ? 'registry-customer-siteForm-name-error' : undefined" name="name"/>
          <div class="input-error" v-if="siteForm.errors.name" id="registry-customer-siteForm-name-error" role="alert">{{ siteForm.errors.name }}</div>
        </div>
        <div>
          <label class="label" for="registry-customer-siteForm-address">Indirizzo</label>
          <input v-model="siteForm.address" type="text" class="input input-bordered w-full"  id="registry-customer-siteForm-address" :aria-invalid="!!siteForm.errors.address" :aria-describedby="siteForm.errors.address ? 'registry-customer-siteForm-address-error' : undefined" name="address" @input="addressChanged"/>
          <div class="input-error" v-if="siteForm.errors.address" id="registry-customer-siteForm-address-error" role="alert">{{ siteForm.errors.address }}</div>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="label" for="registry-customer-siteForm-latitude">Lat</label>
            <input v-model="siteForm.latitude" type="number" step="any" class="input input-bordered w-full" disabled="disabled"  id="registry-customer-siteForm-latitude" :aria-invalid="!!siteForm.errors.latitude" :aria-describedby="siteForm.errors.latitude ? 'registry-customer-siteForm-latitude-error' : undefined" name="latitude"/>
            <div class="input-error" v-if="siteForm.errors.latitude" id="registry-customer-siteForm-latitude-error" role="alert">{{ siteForm.errors.latitude }}</div>
          </div>
          <div>
            <label class="label" for="registry-customer-siteForm-longitude">Lng</label>
            <input v-model="siteForm.longitude" type="number" step="any" class="input input-bordered w-full" disabled="disabled"  id="registry-customer-siteForm-longitude" :aria-invalid="!!siteForm.errors.longitude" :aria-describedby="siteForm.errors.longitude ? 'registry-customer-siteForm-longitude-error' : undefined" name="longitude"/>
            <div class="input-error" v-if="siteForm.errors.longitude" id="registry-customer-siteForm-longitude-error" role="alert">{{ siteForm.errors.longitude }}</div>
          </div>
        </div>
        <LocationPicker class="pt-2" :latitude="siteForm.latitude" :longitude="siteForm.longitude" :address="siteForm.address" :disabled="siteForm.processing" @position-change="moveMarker" />
        <p v-if="locationError" role="alert" class="text-sm text-error">{{ locationError }}</p>
        <label class="flex min-h-12 w-fit items-center gap-2 text-sm"><input v-model="siteForm.is_main" type="checkbox" class="toggle toggle-primary" :disabled="currentMain" />Sede principale</label>
        <h4 class="pt-3 font-semibold">Dotazioni e consulenza</h4>
<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <SiteBooleanParameter
            title="Consulente ADR" positive-label="Presente" negative-label="Assente"
            :booleanValue="siteForm.has_adr_consultant"
            iconString="vial-circle-check"
            :isEditable="true"
            @update:booleanValue="siteForm.has_adr_consultant = $event"
          />
          <SiteBooleanParameter
            title="Muletto" positive-label="Disponibile" negative-label="Non disponibile"
            :booleanValue="siteForm.has_muletto"
            iconString="truck-ramp-box"
            :isEditable="true"
            @update:booleanValue="siteForm.has_muletto = $event"
          />
          <SiteBooleanParameter
            title="Transpallet elettrico" positive-label="Disponibile" negative-label="Non disponibile"
            :booleanValue="siteForm.has_electric_pallet_truck"
            iconString="cart-flatbed"
            :isEditable="true"
            @update:booleanValue="siteForm.has_electric_pallet_truck = $event"
          />
          <SiteBooleanParameter
            title="Transpallet manuale" positive-label="Disponibile" negative-label="Non disponibile"
            :booleanValue="siteForm.has_manual_pallet_truck"
            iconString="dolly"
            :isEditable="true"
            @update:booleanValue="siteForm.has_manual_pallet_truck = $event"
          />
        </div>
        <div>
          <label class="label" for="registry-customer-siteForm-other_machines">Altri macchinari</label>
          <textarea
            v-model="siteForm.other_machines"
            class="textarea textarea-bordered w-full"
            rows="3"
            placeholder="Inserisci eventuali altri macchinari"
           id="registry-customer-siteForm-other_machines" :aria-invalid="!!siteForm.errors.other_machines" :aria-describedby="siteForm.errors.other_machines ? 'registry-customer-siteForm-other_machines-error' : undefined" name="other_machines"/>
          <div class="input-error" v-if="siteForm.errors.other_machines" id="registry-customer-siteForm-other_machines-error" role="alert">{{ siteForm.errors.other_machines }}</div>
        </div>
        <div>
          <label class="label" for="registry-customer-siteForm-notes">Note Sede</label>
          <textarea
            v-model="siteForm.notes"
            class="textarea textarea-bordered w-full"
            rows="3"
            placeholder="Inserisci eventuali note operative sulla sede"
           id="registry-customer-siteForm-notes" :aria-invalid="!!siteForm.errors.notes" :aria-describedby="siteForm.errors.notes ? 'registry-customer-siteForm-notes-error' : undefined" name="notes"/>
          <div class="input-error" v-if="siteForm.errors.notes" id="registry-customer-siteForm-notes-error" role="alert">{{ siteForm.errors.notes }}</div>
        </div>
        <div class="flex items-center justify-end gap-2 pt-2">
          <button type="button" class="btn btn-error btn-outline" @click="closeAddSiteModal">Annulla</button>
          <button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined">
            Salva sede
          </button>
        </div>
      </RegistryForm>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button :disabled="siteForm.processing">Chiudi</button>
    </form>
  </dialog>
</template>
<script setup>
import LocationPicker from '@/Components/LocationPicker.vue'
import {ref, computed} from 'vue'
import {useForm} from '@inertiajs/vue3'
import RegistryForm from '@/Components/Registry/RegistryForm.vue'
import SiteBooleanParameter from './SiteBooleanParameter.vue'
import {useDebouncedGeocoding} from '@/Composables/useDebouncedGeocoding'
const props = defineProps({customer:{type:Object,required:true}})
const addSiteDialog = ref(null)
const editingSiteId = ref(null)
const siteEditorOpen = ref(false)
const addressQuery = ref('')
const manualPosition = ref(false)
const locationError = ref('')
const originalAddress = ref('')
const originalCoordinates = ref({latitude:null,longitude:null})
const currentMain = ref(false)
const emptyDraft = () => ({
    customer_id: props.customer.id, site_type: null, name: '',
    address: props.customer.legal_address ?? '', latitude: null, longitude: null,
    is_main: false, has_muletto: false, has_electric_pallet_truck: false,
    has_manual_pallet_truck: false, other_machines: '', has_adr_consultant: false, notes: '',
})
const siteForm = useForm(emptyDraft())
const isEditingSite = computed(() => editingSiteId.value !== null)
const open = site => {
    if (siteForm.processing) return
    const draft = emptyDraft()
    if (site) for (const key of Object.keys(draft)) draft[key] = site[key] ?? draft[key]
    for (const key of ['is_main','has_muletto','has_electric_pallet_truck','has_manual_pallet_truck','has_adr_consultant']) draft[key] = Boolean(draft[key])
    draft.customer_id = props.customer.id
    siteForm.defaults(draft)
    siteForm.reset()
    siteForm.clearErrors()
    addressQuery.value = site ? '' : draft.address
    manualPosition.value = false
    locationError.value = ''
    originalAddress.value = draft.address
    originalCoordinates.value = {latitude:draft.latitude, longitude:draft.longitude}
    currentMain.value = Boolean(site?.is_main)
    editingSiteId.value = site?.id ?? null
    siteEditorOpen.value = true
    addSiteDialog.value.showModal()
}
const closeAddSiteModal = () => {
    if (siteForm.processing) return
    addSiteDialog.value.close()
    siteEditorOpen.value = false
    addressQuery.value = ''
}
const saveSite = () => {
    if (siteForm.processing) return
    const options = {preserveScroll: true, onSuccess: closeAddSiteModal}
    if (isEditingSite.value) siteForm.put(route('site.update', {site: editingSiteId.value}), options)
    else siteForm.post(route('site.store'), options)
}
useDebouncedGeocoding({
    sourceRef: () => addressQuery.value,
    apiKey: import.meta.env.VITE_MAPS_API_KEY,
    onResolved: (location, address) => {
        if (!siteEditorOpen.value || manualPosition.value || siteForm.address.trim() !== address || (isEditingSite.value && address === originalAddress.value.trim())) return
        siteForm.latitude = location.lat
        siteForm.longitude = location.lng
        locationError.value = ''
    },
    onError: (_, address) => {
        if (siteEditorOpen.value && !manualPosition.value && siteForm.address.trim() === address) locationError.value = 'Coordinate non trovate. Controlla l’indirizzo e riprova.'
    },
})
const moveMarker = position => {
    if (siteForm.processing) return
    manualPosition.value = true
    locationError.value = ''
    Object.assign(siteForm, position)
}
const addressChanged = event => {
    manualPosition.value = false
    locationError.value = ''
    addressQuery.value = event.target.value === originalAddress.value ? '' : event.target.value
    // Coordinates from the former address must not be saved against a new one.
    if (siteForm.address !== originalAddress.value) {siteForm.latitude = null; siteForm.longitude = null}
    else Object.assign(siteForm, originalCoordinates.value)
}
defineExpose({open})
</script>
