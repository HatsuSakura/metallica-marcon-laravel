<template>

<dialog id="my_modal_4" class="modal">
  <div class="modal-box">
    <h3 class="text-lg font-bold">Nuovo Ritiro</h3>
    {{ props.site }}
    <Create />
    <div class="modal-action">
      <form method="dialog">
        <!-- if there is a button in form, it will close the modal -->
        <button class="btn">Close</button>
      </form>
    </div>
  </div>
</dialog>

<!-- You can open the modal using ID.showModal() method -->
<dialog id="my_modal_42" class="modal">
  <div class="modal-box w-11/12 max-w-5xl">
    <h3 class="text-lg font-bold">Nuovo Ritiro</h3>
    <Create />
    <div class="modal-action">
      <form method="dialog">
        <!-- if there is a button, it will close the modal -->
        <button class="btn">Close</button>
      </form>
    </div>
  </div>
</dialog>

    <input 
        type="radio"
        :name="calculatedName"
        role="tab" 
        class="tab whitespace-nowrap"
        :aria-label="calculatedLabel"
        :checked="props.counter === 0 ? true : false" 
    />
    <div role="tabpanel" class="tab-content  p-0 pb-8">

        <!-- NAVBAR singola SCHEDA -->
        <div class="navbar bg-base-100">
            <div class="navbar-start">
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
                            <Link @click="selectSite(props.site)" :href="route('relator.withdraw.create')">
                            Nuovo Ritiro
                            </Link>
                        </li>
                        <li><a>Ricalcola Rischio</a></li>
                        <li><a>Edit</a></li>
                    </ul>
                </div>
            </div>
            <div class="navbar-center text-xl">
                Riepilogo sede {{ props.site.denominazione }}
            </div>
            <div class="navbar-end space-x-2">
                <Link 
                    :href="route('relator.order.create', { site: props.site.id })"
                    class="btn btn-circle btn-primary"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" class="h-5 w-5" stroke="currentColor" />
                </Link>

                <button class="btn btn-circle btn-secondary" @click="openCreateWithdrawModal(props.site)">
                    <font-awesome-icon :icon="['fas', 'plus']" class="h-5 w-5" stroke="currentColor" />
                </button>
                
                <Link @click="selectSite(props.site)" :href="route('relator.withdraw.create')"
                    class="btn btn-circle btn-warning">
                <font-awesome-icon :icon="['fas', 'square-root-variable']" class="h-5 w-5" stroke="currentColor" />
                </Link>
                <button class="btn btn-ghost btn-circle">
                    <font-awesome-icon :icon="['fas', 'cog']" class="h-5 w-5" stroke="currentColor" />
                </button>
            </div>
        </div>

        <!-- SEZIONE 00 : info specifice SEDE-->
        <section>
            <form @submit.prevent="updateBooleanParametersForSite">
                <div class="flex flex-row gap-4">
                    <!-- DUE RIGHE DEI PARAMETRI-->
                    <div class="flex flex-col gap-4 justify-stretch w-full">
                        <!-- INTESTAZIONE : Denominazione, is_main, area, areas -->
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
                                :denominazione="props.site.denominazione"
                                :preferred_area="sitePreferredArea"
                                @update:is_main="form.is_main = $event"
                                @update:denominazione="form.denominazione = $event"
                                @update:preferred_area="form.preferred_area = $event"
                            />

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
                                :booleanValue = form.has_transpallet_el
                                iconString="cart-flatbed"
                                :isEditable="isToggleEditable"
                                @update:booleanValue="form.has_transpallet_el = $event"
                            />

                            <SiteBooleanParameter
                                title="Transpallet Manuale"
                                :booleanValue = "form.has_transpallet_ma"
                                iconString="dolly"
                                :isEditable="isToggleEditable"
                                @update:booleanValue="form.has_transpallet_ma = $event"
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
                            <div class="flex text-2xl">{{ 100 * props.site.fattore_rischio_calcolato }}%</div>
                        </div>
                    </div>

                    <div class="w-full shadow-xl p-4 gap-2">
                        <h2 clasS="mb-2">Prossimo Ritiro</h2>
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-4xl"><font-awesome-icon icon="calendar-day" /></div>
                        <div class="flex text-2xl">{{ props.site.giorni_prossimo_ritiro }} gg</div>
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

import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { useStore } from 'vuex';
import EmptyState from '@/Components/UI/EmptyState.vue';
import EditableTimetable from './EditableTimetable.vue';
import SimpleMapWithMarker from '@/Pages/Map/Components/SimpleMapWithMarker.vue';
//import Create from '../../Withdraw/Create.vue';
import StoricoRitiri from './StoricoRitiri.vue';
import OrdiniInCorso from './OrdiniInCorso.vue';
import SiteBooleanParameter from './SiteBooleanParameter.vue';
import Box from '@/Components/UI/Box.vue';
import axios from 'axios';
import SiteHeadParameter from './SiteHeadParameter.vue';

const props = defineProps({
    site: Object,
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
    () => '' + props.site.denominazione
);

// Method to select the current site
const selectSite = (site) => {
    console.log('Selected Site:', site);
    store.dispatch('setCurrentSite', site); // Dispatch action to set current site
};

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
    denominazione: props.site.denominazione,
    is_main: Boolean(props.site.is_main),
    preferred_area: sitePreferredArea,
    other_areas: siteAllAreasWithoutPreferred,
    has_adr_consultant: Boolean(props.site.has_adr_consultant),
    has_muletto: Boolean(props.site.has_muletto),
    has_transpallet_el: Boolean(props.site.has_transpallet_el),
    has_transpallet_ma: Boolean(props.site.has_transpallet_ma),
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

const openCreateWithdrawModal = (site) => {
    selectSite(site);
    my_modal_4.showModal();
};




</script>