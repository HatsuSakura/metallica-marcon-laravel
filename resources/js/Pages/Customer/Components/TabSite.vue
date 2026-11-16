<template>

<dialog ref="riskModal" class="modal">
  <div class="modal-box">
    <h3 class="font-bold text-lg">Ricalcolo Rischio</h3>
    <p class="py-2 text-sm text-base-content/70">
      Il cliente ha più sedi: come vuoi ricalcolare il rischio?
    </p>

    <div class="mt-3 flex flex-col gap-2">
      <button class="btn btn-outline w-full" type="button" @click="runSingleSiteRiskRecalculation">
        Solo questa sede
      </button>
      <button class="btn btn-success min-h-12 w-full" type="button" @click="runCustomerRiskRecalculation">
        Tutte le sedi del cliente
      </button>
    </div>

    <div class="modal-action">
      <button class="btn btn-error btn-outline min-h-12" type="button" @click="riskModal?.close()">Annulla</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

<div class="min-w-0 space-y-4 p-3 sm:p-4">
<section class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <h3 class="font-semibold">Dati della sede</h3>
            <AppAction v-if="canManageContacts" variant="function" shape="circle" aria-label="Modifica dati sede" title="Modifica dati sede" @click="$emit('edit')"><font-awesome-icon :icon="['fas','pencil']" aria-hidden="true" /></AppAction>
            <RegistryDeleteAction v-if="canDelete" :label="`sede ${props.site.name}`" :disabled="!!props.site.is_main" disabled-reason="Non puoi eliminare la sede principale" :busy="deleting === route('site.destroy', {site:props.site.id})" @confirm="removeRecord(route('site.destroy', {site:props.site.id}))" />
        </div>
        <div class="ml-auto flex flex-wrap justify-end gap-2">
            <AppAction :as="Link" variant="function" :href="route('order.create', {site:props.site.id})">Crea ordine</AppAction>
            <AppAction :as="Link" variant="function" :href="withdrawCreateUrl(props.site)">Crea ritiro</AppAction>
            <AppAction variant="function" @click="calculateRiskFactor(props.site)">Ricalcola rischio</AppAction>
        </div>
    </div>
    <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
        <div class="min-w-0 space-y-3">
    <div><h4 class="text-sm font-semibold">Indirizzo</h4><Address :address="props.site.address" class="text-sm" /></div>
    <p class="text-sm">Area principale: <span class="font-medium">{{ props.site.areas?.find(area => area.pivot?.is_preferred)?.name || 'Non impostata' }}</span></p>
    <div><h4 class="text-sm font-semibold">Note della sede</h4><p class="whitespace-pre-line break-words text-sm">{{ props.site.notes || 'Nessuna nota sede' }}</p></div>
    <div v-if="props.site.other_machines"><h4 class="text-sm font-semibold">Altri macchinari</h4><p class="whitespace-pre-line break-words text-sm">{{ props.site.other_machines }}</p></div>
        </div>
        <div class="h-64 min-w-0 overflow-hidden rounded-xl border border-base-300"><SimpleMapWithMarker :site="props.site" :withInfoWindow="false" /></div>
    </div>
    <h3 class="border-t border-base-300 pt-3 font-semibold">Dotazioni e consulenza</h3>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <SiteBooleanParameter title="Consulente ADR" icon-string="vial-circle-check" :boolean-value="!!props.site.has_adr_consultant" positive-label="Presente" negative-label="Assente" />
        <SiteBooleanParameter title="Muletto" icon-string="truck-ramp-box" :boolean-value="!!props.site.has_muletto" positive-label="Disponibile" negative-label="Non disponibile" />
        <SiteBooleanParameter title="Transpallet elettrico" icon-string="cart-flatbed" :boolean-value="!!props.site.has_electric_pallet_truck" positive-label="Disponibile" negative-label="Non disponibile" />
        <SiteBooleanParameter title="Transpallet manuale" icon-string="dolly" :boolean-value="!!props.site.has_manual_pallet_truck" positive-label="Disponibile" negative-label="Non disponibile" />
    </div>
</section>

        <!-- SEZIONE 01 REFERENTI INTERNI -->
        <section class="py-4">
            <div class="font-medium mb-2">Referenti Interni</div>
            <SiteInternalContacts :contacts="props.site.internal_contacts || []" :site-id="props.site.id" :roles="contactRoles" :editable="canManageContacts" @saved="contactSaved" />
        </section>

        <!-- SEZIONE 02 INDICATORI RITIRI E ORDINI -->
        <section class="flex flex-col-reverse md:grid md:grid-cols-12 gap-4 my-4">

            <!-- INDICATORI -->
            <div class="md:col-span-3">
                <div class="flex flex-col items-center gap-4">

                    <div class="w-full rounded-xl border border-base-300 p-3">
                        <h2 clasS="mb-2">Fattore Rischio</h2>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-4xl"><font-awesome-icon icon="triangle-exclamation" /></div>
                            <div class="flex text-2xl">{{ (100 * (props.site.calculated_risk_factor ?? 0)).toFixed(1) }}%</div>
                        </div>
                    </div>

                    <div class="w-full rounded-xl border border-base-300 p-3">
                        <h2 clasS="mb-2">Prossimo Ritiro</h2>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-4xl"><font-awesome-icon icon="calendar-day" /></div>
                        <div class="flex text-2xl">{{ props.site.days_until_next_withdraw }} gg</div>
                        </div>
                    </div>


                </div>
            </div>
            <!-- RITIRI -->
            <div class="md:col-span-5">
                <StoricoRitiri
                    :site = "props.site"
                    :withdraws="props.site.withdraws"
                />
            </div>

            <div class="col-span-4 md:col-span-4">
                <OrdiniInCorso
                    :orders = "props.orders"
                />
            </div>

        </section>

        <section class="border-t border-base-300 pt-4">
            <EditableTimetable :site="props.site" />
        </section>

</div>
</template>

<script setup>
import Address from '@/Components/Address.vue'
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()
import AppAction from '@/Components/UI/AppAction.vue'


import { Link, router } from '@inertiajs/vue3';
import { ref, nextTick } from 'vue';
import { useStore } from 'vuex';
import EditableTimetable from './EditableTimetable.vue';
import SimpleMapWithMarker from '@/Pages/Map/Components/SimpleMapWithMarker.vue';
import StoricoRitiri from './StoricoRitiri.vue';
import OrdiniInCorso from './OrdiniInCorso.vue';
import SiteBooleanParameter from './SiteBooleanParameter.vue';
import axios from 'axios';
import SiteInternalContacts from '@/Components/SiteInternalContacts.vue';

const props = defineProps({
    site: Object,
    contactRoles: {type:Array,default:()=>[]},
    canManageContacts: Boolean,
    canDelete: Boolean,
    customerSitesCount: Number,
    areas: Array,
    orders: Array,
});

const store = useStore();

const emit = defineEmits(['siteUpdated','edit'])
const contactSaved = contact => {
    const contacts = [...(props.site.internal_contacts || [])]
    const index = contacts.findIndex(row => row.id === contact.id)
    if (index < 0) contacts.push(contact)
    else contacts[index] = contact
    emit('siteUpdated', {...props.site, internal_contacts: contacts})
}
const withdrawCreateUrl = (site) => route('withdraw.create', {
    site: site.id,
    customer: site.customer_id,
});

const riskModal = ref(null);
const targetSiteForRisk = ref(null);

const calculateRiskFactor = (site) => {
    targetSiteForRisk.value = site;
    const sitesCount = Number(props.customerSitesCount ?? 1);
    if (sitesCount > 1) {
        nextTick(() => riskModal.value?.showModal?.());
        return;
    }
    runSingleSiteRiskRecalculation();
}

const runSingleSiteRiskRecalculation = () => {
    const site = targetSiteForRisk.value ?? props.site;
    if (!site?.id) return;
    riskModal.value?.close?.();

    axios.post(`/api/site/${site.id}/recalculate-risk`)
        .then((response) => {
            const mergedSite = {
                ...JSON.parse(JSON.stringify(site)),
                ...(response.data.site ?? {}),
            };
            emit('siteUpdated', mergedSite);
            store.dispatch('flash/queueMessage', { type: 'success', text: 'Rischio sede ricalcolato correttamente' });
        })
        .catch(() => {
            store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore durante il ricalcolo del rischio' });
        });
}

const runCustomerRiskRecalculation = () => {
    const site = targetSiteForRisk.value ?? props.site;
    if (!site?.customer_id) return;
    riskModal.value?.close?.();

    axios.post(`/api/customer/${site.customer_id}/recalculate-risk`)
        .then(() => {
            store.dispatch('flash/queueMessage', { type: 'success', text: 'Rischio ricalcolato su tutte le sedi del cliente' });
            router.reload({
                only: ['customer', 'orders_by_site'],
                preserveScroll: true,
                preserveState: false,
            });
        })
        .catch(() => {
            store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore durante il ricalcolo globale del rischio' });
        });
}



</script>
