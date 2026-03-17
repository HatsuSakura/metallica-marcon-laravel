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
      <button class="btn btn-primary w-full" type="button" @click="runCustomerRiskRecalculation">
        Tutte le sedi del cliente
      </button>
    </div>

    <div class="modal-action">
      <button class="btn" type="button" @click="riskModal?.close()">Annulla</button>
    </div>
  </div>
  <form method="dialog" class="modal-backdrop">
    <button>close</button>
  </form>
</dialog>

    <input 
        type="radio"
        :name="calculatedName"
        role="tab" 
        class="tab whitespace-nowrap"
        :aria-label="calculatedLabel"
        :checked="props.counter === 0 ? true : false" 
    />
    <div role="tabpanel" class="tab-content border-base-300 rounded-box border-l-0 border-r-0 p-0 pb-8">

        <!-- NAVBAR singola SCHEDA -->
        <div class="navbar bg-base-100">
            <div class="navbar-start">
                <!-- DROPDOWN PER AZIONI RAPIDE -->
                <!--
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </div>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                        <li>
                            <Link :href="withdrawCreateUrl(props.site)">
                            Nuovo Ritiro
                            </Link>
                        </li>
                        <li><button type="button" @click="calculateRiskFactor(props.site)">Ricalcola Rischio</button></li>
                        <li><a>Edit</a></li>
                    </ul>
                </div>
                -->
            </div>
            <div class="navbar-center text-xl">
                Riepilogo sede {{ props.site.name }}
            </div>
            <div class="navbar-end space-x-2">
                <div class="tooltip" data-tip="Crea ordine">
                    <Link :href="route('order.create', { site: props.site.id })" class="btn btn-circle btn-primary">
                        <font-awesome-icon :icon="['fas', 'clipboard-list']" class="h-5 w-5" stroke="currentColor" />
                    </Link>
                </div>

                <div class="tooltip" data-tip="Crea ritiro">
                    <Link :href="withdrawCreateUrl(props.site)" class="btn btn-circle btn-secondary">
                        <font-awesome-icon :icon="['fas', 'cart-plus']" class="h-5 w-5" stroke="currentColor" />
                    </Link>
                </div>
                
                <div class="tooltip" data-tip="Ricalcola rischio">
                    <button type="button" @click="calculateRiskFactor(props.site)" class="btn btn-circle btn-warning">
                        <font-awesome-icon :icon="['fas', 'square-root-variable']" class="h-5 w-5" stroke="currentColor" />
                    </button>
                </div>
<!--
                <div class="tooltip" data-tip="Impostazioni">
                    <button class="btn btn-ghost btn-circle">
                        <font-awesome-icon :icon="['fas', 'cog']" class="h-5 w-5" stroke="currentColor" />
                    </button>
                </div>
-->
            </div>
        </div>

        <!-- SEZIONE 00 : info specifice SEDE-->
        <section>
            <form @submit.prevent="updateBooleanParametersForSite">
                <div class="flex flex-row gap-4">
                    <!-- DUE RIGHE DEI PARAMETRI-->
                    <div class="flex flex-col gap-4 justify-stretch w-full">
                        <!-- INTESTAZIONE : name, is_main, area, areas -->
                        <div class="flex flex-row gap-2 justify-stretch w-full items-stretch mt-4">

<!--
                            <br/>AREAS: {{ props.areas }}
                            <br/>SITE AREAS: {{ props.site.areas }}
                            <br/>PREFERRED: {{ sitePreferredArea }}
-->

                            <SiteHeadParameter
                                :isEditable="isToggleEditable"
                                :areas="props.areas"
                                :is_main="Boolean(props.site.is_main)"
                                :name="props.site.name"
                                :preferred_area="sitePreferredArea"
                                @update:is_main="form.is_main = $event"
                                @update:name="form.name = $event"
                                @update:preferred_area="form.preferred_area = $event"
                            />


                        <div class="flex flex-row gap-2 justify-stretch items-stretch w-1/2">
                            <Box class="w-full">
                                <template #header>Note Sede</template>
                                <textarea
                                    v-if="isToggleEditable"
                                    v-model="form.notes"
                                    class="textarea textarea-bordered w-full"
                                    rows="3"
                                    placeholder="Inserisci eventuali note operative sulla sede"
                                />
                                <div v-else-if="form.notes" class="whitespace-pre-line">
                                    {{ form.notes }}
                                </div>
                                <div v-else class="opacity-70">
                                    Nessuna nota sede
                                </div>
                            </Box>
                        </div>

                        </div>

                        <!-- BOOLEAN PARAMETERS -->
                        <div class="flex flex-row gap-4 justify-stretch w-full items-stretch mt-0">
                            
                            <SiteBooleanParameter
                                title="Consulente ADR"
                                :booleanValue = form.has_adr_consultant
                                iconString="vial-circle-check"
                                :isEditable="isToggleEditable"
                                @update:booleanValue="form.has_adr_consultant = $event"
                            />

                            <SiteBooleanParameter
                                title="Muletto"
                                :booleanValue = form.has_muletto
                                iconString="truck-ramp-box"
                                :isEditable="isToggleEditable"
                                @update:booleanValue="form.has_muletto = $event"
                            />

                            <SiteBooleanParameter
                                title="Transpallet Elettrico"
                                :booleanValue = form.has_electric_pallet_truck
                                iconString="cart-flatbed"
                                :isEditable="isToggleEditable"
                                @update:booleanValue="form.has_electric_pallet_truck = $event"
                            />

                            <SiteBooleanParameter
                                title="Transpallet Manuale"
                                :booleanValue = "form.has_manual_pallet_truck"
                                iconString="dolly"
                                :isEditable="isToggleEditable"
                                @update:booleanValue="form.has_manual_pallet_truck = $event"
                            />

                        </div>


                    </div>
                    <!-- PULSANTI EDIT/SAVE-->
                    <div>
                        <div class="flex flex-col justify-center h-full gap-4">
                            <div v-if="!isToggleEditable">
                                <button 
                                    type="button" 
                                    @click="toggleToggleEditable"
                                    class="btn btn-primary btn-circle"
                                >
                                    <font-awesome-icon :icon="['fas', 'pencil']" class="text-2xl"/>
                                </button>
                            </div>
                            <div v-else>
                                <button 
                                    type="button" 
                                    @click="toggleToggleEditable"
                                    class="btn btn-error btn-circle"
                                >
                                    <font-awesome-icon :icon="['fas', 'ban']" class="text-2xl"/>
                                </button>
                            </div>


                            <div>
                                <button 
                                    type="submit"
                                    class="btn btn-success btn-circle"
                                    :disabled = "!isToggleEditable"
                                >
                                    <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-2xl"/>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

        </section>

        <!-- SEZIONE 01 INDICATORI RITIRI E ORDINI -->
        <section class="flex flex-col-reverse md:grid md:grid-cols-12 gap-4 my-4">

            <!-- INDICATORI -->
            <div class="col-span-2">
                <div class="flex flex-col items-center gap-4">

                    <div class="w-full shadow-xl p-4 gap-2">
                        <h2 clasS="mb-2">Fattore Rischio</h2>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-4xl"><font-awesome-icon icon="triangle-exclamation" /></div>
                            <div class="flex text-2xl">{{ (100 * (props.site.calculated_risk_factor ?? 0)).toFixed(1) }}%</div>
                        </div>
                    </div>

                    <div class="w-full shadow-xl p-4 gap-2">
                        <h2 clasS="mb-2">Prossimo Ritiro</h2>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-4xl"><font-awesome-icon icon="calendar-day" /></div>
                        <div class="flex text-2xl">{{ props.site.days_until_next_withdraw }} gg</div>
                        </div>
                    </div>


                </div>
            </div>
            <!-- RITIRI -->
            <div class="col-span-6 md:col-span-6">
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

        <!-- SEZIONE 02 ORARI & MAPPA -->
        <section class="flex flex-col-reverse md:grid md:grid-cols-12 gap-4 py-4">
            <div class="col-span-6">
                <EditableTimetable :site="props.site"/>
            </div>
            
            <!-- MAPPA -->
            <div class="col-span-6">
                <SimpleMapWithMarker :site="props.site" :withInfoWindow="false" />
            </div>
        </section>

    </div>

</template>

<script setup>

import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, nextTick } from 'vue';
import { useStore } from 'vuex';
import EmptyState from '@/Components/UI/EmptyState.vue';
import EditableTimetable from './EditableTimetable.vue';
import SimpleMapWithMarker from '@/Pages/Map/Components/SimpleMapWithMarker.vue';
import StoricoRitiri from './StoricoRitiri.vue';
import OrdiniInCorso from './OrdiniInCorso.vue';
import SiteBooleanParameter from './SiteBooleanParameter.vue';
import Box from '@/Components/UI/Box.vue';
import axios from 'axios';
import SiteHeadParameter from './SiteHeadParameter.vue';

const props = defineProps({
    site: Object,
    customerSitesCount: Number,
    counter: Number,
    areas: Array,
    orders: Array,
});

const store = useStore();

const isToggleEditable = ref(false);

const toggleToggleEditable = () => {
    isToggleEditable.value = !isToggleEditable.value;   
}
const setToggleEditableFalse = () => {
    isToggleEditable.value = false;
}

const calculatedName = computed(
    () => "tabs_" + props.site.customer_id,
);

const calculatedLabel = computed(
    () => '' + props.site.name
);

const sitePreferredArea = ref(
    props.site.areas && props.site.areas.length > 0
        ? props.site.areas.find(area => area.pivot.is_preferred === 1)?.id
        : ''
) 
const siteAllAreas = computed(() => areas.value);
const siteAllAreasWithoutPreferred = ref( 
    props.site.areas && props.site.areas.length > 0
        ? props.site.areas.filter(area => area.pivot.is_preferred !== 1)
        : []
);

const form = useForm({
    name: props.site.name,
    is_main: Boolean(props.site.is_main),
    preferred_area: sitePreferredArea,
    other_areas: siteAllAreasWithoutPreferred,
    has_adr_consultant: Boolean(props.site.has_adr_consultant),
    has_muletto: Boolean(props.site.has_muletto),
    has_electric_pallet_truck: Boolean(props.site.has_electric_pallet_truck),
    has_manual_pallet_truck: Boolean(props.site.has_manual_pallet_truck),
    notes: props.site.notes ?? '',
})
    




const emit = defineEmits(['siteUpdated'])
function updateBooleanParametersForSite() {
    console.log('Site che sto tentando di UPPARE',props.site)
    axios.put(`/api/site/updateBooleans/${props.site.id}`, form )
    //axios.put(`/api/site/updateBooleans/${props.site.id}?full=true`, form)
        .then(response => {
            setToggleEditableFalse();
            store.dispatch('flash/queueMessage', { type: 'success', text: 'Sede aggiornata correttamente' });

            emit('siteUpdated', JSON.parse(JSON.stringify(response.data.site) ) ); // The event name is 'siteUpdated'
            console.log('Emitted siteUpdated with:', response.data.site);
        })
        .catch(error => {
            console.error('Errore nel salvataggio: ', error);
        });
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
