<template>



        <Link
            class="btn btn-ghost"
            :href="route('driver.journey.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>&nbsp;
            Torna alla lista viaggi
        </Link>
    


<form-wizard
    @on-complete="onComplete"
    @on-loading="setLoading"
    @on-validate="handleValidation"
    @on-error="handleErrorMessage"
    @on-change="onTabChange"
    :start-index="startIndex"
    step-size="sm"
    shape="circle"
    color="grey"
    error-color="#e74c3c"
>

<div class="loader" v-if="loadingWizard"></div>
<div v-if="errorMsg" class="bg-error p-2 text-white my-2">
    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" /> &nbsp;
    <span class="error">{{ errorMsg }}</span>
</div>

<!-- PARTENZA -->
    <tab-content 
        title="Partenza" 
        icon="fa fa-route"
        :before-change="updateJourneyStatus"
    >
        <div class="flex flex-row items-center gap-2">
            <div>
                <span class="font-medium">Data e ora di partenza: </span>
            </div>
            <div>
                <VueDatePicker
                    v-model="form.real_dt_start"
                    locale="it"
                    format="dd/MM/yyyy HH:mm"
                    required
                    placeholder="Data Partenza"
                    :range=false
                    time-picker-inline
                    auto-apply
                    minutes-increment="5"
                    minutes-grid-increment="5"
                    closeOnScroll="false"
                    @closed="manageDate"
                >
                </VueDatePicker>
            </div>
        </div>
    </tab-content>

<!-- TAPPE INTERMEDIE -->
    <tab-content v-for="order in props.journey.orders" :key="order.id"
        :title="order.customer.ragione_sociale"
        :icon="calculateIcon(order)"
        :before-change="()=>updateOrderStatus(order)"
    >
        <div class="flex flex-row gap-2">
            <Box class="w-1/2 flex flex-col gap-2">
                <template #header>Ordine #{{ String(order.id).padStart('9', '0') }}</template>
                 <div>
                    <span class="font-medium">Zona di carico Autotreno: </span>
                    <span v-if="order.truck_location == 'vehicle'">
                        <font-awesome-icon :icon="['fas', 'truck']" class="text-2xl"/>
                        Motrice
                    </span>
                    <span v-else-if="order.truck_location == 'trailer'">
                        <font-awesome-icon :icon="['fas', 'trailer']" class="text-2xl"/>
                        Rimorchio
                    </span>
                    <span v-else>
                        <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-2xl"/>
                        A capienza/Indifferente
                    </span>
                </div>
                <div class="flex flex-row items-center gap-2">
                    <div>
                        <span class="font-medium">Data e ora di carico: </span>
                    </div>
                    <div>
                        <VueDatePicker
                            v-model="(form.orders.find(o => o.id === order.id) || {}).real_withdraw_dt"
                            locale="it"
                            format="dd/MM/yyyy HH:mm"
                            required
                            placeholder="Data e ora Ritiro"
                            :range=false
                            time-picker-inline
                            auto-apply
                            minutes-increment="5"
                            minutes-grid-increment="5"
                            closeOnScroll="false"
                            @closed="manageDate"
                        >
                        </VueDatePicker>
                    </div>
                </div>
                <div>
                    ADR???
                </div>
                <div class="flex flex-row-reverse">
                    <Link
                        :href="route('driver.order.edit', {order: order.id} )"
                        method="get"
                        as="button"
                        class="btn btn-primary"
                        >
                        <font-awesome-icon :icon="['fas', 'pen']" class="text-2xl"/>
                        Modifica Ordine
                    </Link>
                </div>
            </Box>

            <Box class="w-1/2  flex flex-col gap-2">
                <template #header>Info sede di carico</template>
                <div>
                    <span class="font-medium">Indirizzo: </span>{{ order.site.indirizzo }}
                </div>
                <div>
                    <span class="font-medium">Mezzi di sollevamento: </span>
                    <SiteMezziDiSollevamento :site="order.site" />
                </div>
            </Box>
        </div>

        <div class="flex flex-col gap-2 mt-2">
            <Box>
                <template #header>Materiali</template>
                <JourneyOrderItems :items="order.items" :holders="props.holders" :cerList="props.cerList"/>
            </Box>

            <Box>
                <template #header>Contenitori</template>
                <JourneyOrderHolders :items="order.holders" :holders="props.holders"/>
            </Box>
        </div>


    </tab-content>


<!-- SCARICO A MAGAZZINO -->
    <tab-content 
        title="Scarico"
        icon="fa fa-warehouse"
    >

        <div class="flex flex-row gap-2 items-center">
            <div class="flex items-center ">
                <label class="font-medium" for="is_double_load">Doppio scarico  &nbsp;</label>
                <input v-model="form.is_double_load" id="is_double_load" type="checkbox" class="toggle" @change="checkThisToggle"/>
            </div>
        </div>
        <div class="flex flex-row gap-4 my-4">
            <Box class="w-1/2">
                <template #header>
                    <span v-if="form.is_double_load">PRIMO</span>
                    <span v-else="form.is_double_load">UNICO</span>
                    Scarico a Magazzino
                    <!--
                    <div class="flex items-center ">
                        <label class="font-medium" for="is_temporary_storage">Stoccaggio  &nbsp;</label>
                        <input v-model="form.is_temporary_storage" id="is_temporary_storage" type="checkbox" class="toggle"/>
                    </div>
                    -->
                </template>
                <div v-if="!form.is_temporary_storage" class="flex flex-row gap-2 mt-4">
                    <!-- MAGAZZINO Select/Option -->
                    <select v-model="form.warehouse_id_1" id="warehouse" class="select select-bordered">
                        <option value="" disabled>Magazzino</option>
                        <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
                        {{ warehouse.denominazione }}
                        </option>
                    </select>

                    <VueDatePicker
                        v-model="form.warehouse_download_dt_1"
                        locale="it"
                        format="dd/MM/yyyy HH:mm"
                        required
                        placeholder="Data & Ora Scarico"
                        :range=false
                        time-picker-inline
                        auto-apply
                        minutes-increment="5"
                        minutes-grid-increment="5"
                        closeOnScroll="false"
                        class="w-full"
                    ></VueDatePicker>
                </div>
            </Box>

            <Box v-if="form.is_double_load" class="w-1/2">
                <template #header>
                    SECONDO Scarico a Magazzino
                </template>
                <div class="flex flex-row gap-2 mt-4">
                    <!-- MAGAZZINO Select/Option -->
                    <select v-model="form.warehouse_id_2" id="warehouse" class="select select-bordered">
                        <option value="" disabled>Magazzino</option>
                        <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
                        {{ warehouse.denominazione }}
                        </option>
                    </select>

                    <VueDatePicker
                        v-model="form.warehouse_download_dt_2"
                        locale="it"
                        format="dd/MM/yyyy HH:mm"
                        required
                        placeholder="Data & Ora Scarico"
                        :range=false
                        time-picker-inline
                        auto-apply
                        minutes-increment="5"
                        minutes-grid-increment="5"
                        closeOnScroll="false"
                        class="w-full"
                    ></VueDatePicker>
                </div>
            </Box>
        </div>
    </tab-content>

</form-wizard>

</template>

<script setup>

import { defineProps, ref, computed, reactive, onMounted, nextTick  } from 'vue';
import { useForm } from '@inertiajs/vue3'
import axios from 'axios';
import dayjs from 'dayjs';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import {FormWizard, TabContent} from 'vue3-form-wizard';
import 'vue3-form-wizard/dist/style.css';
import Box from "@/Components/UI/Box.vue";
import JourneyOrderItems from "./Components/JourneyOrderItems.vue";
import JourneyOrderHolders from "./Components/JourneyOrderHolders.vue";
import SiteMezziDiSollevamento from "@/Pages/Relator/Order/Components/SiteMezziDiSollevamento.vue";
import { Link } from '@inertiajs/vue3';


const props = defineProps({
    journey : Object,
    holders: Object,
    cerList: Object,
    warehouses: Object,
})

const form = useForm({
    real_dt_start: props.journey.real_dt_start? props.journey.real_dt_start : Date.now(),
    real_dt_end: props.journey.real_dt_end? props.journey.real_dt_end : '',
    orders: [],
    warehouse_id_1: props.journey.warehouse_id_1? props.journey.warehouse_id_1 : '',
    warehouse_download_dt_1: props.journey.warehouse_download_dt_1? props.journey.warehouse_download_dt_1 : '',
    warehouse_id_2: props.journey.warehouse_id_2? props.journey.warehouse_id_2 : '',
    warehouse_download_dt_2: props.journey.warehouse_download_dt_2? props.journey.warehouse_download_dt_2 : '',
    is_temporary_storage: props.journey.is_temporary_storage? Boolean(props.journey.is_temporary_storage) : false,
    is_double_load: props.journey.is_double_load? Boolean(props.journey.is_double_load) : false,
})

onMounted( async () => {
  form.orders = props.journey.orders || []
  await nextTick()
  primeDefaultsForTab(startIndex.value)
})

const loadingWizard = ref(false);
const errorMsg = ref(null);
const count = ref(0);


const fmt = (d) => d ? dayjs(d).format('YYYY-MM-DD HH:mm:ss') : null

const onComplete = () => {
  // Formatto le date prima dell’invio
  form.real_dt_start = fmt(form.real_dt_start)
  form.real_dt_end = fmt(form.real_dt_end)
  form.warehouse_download_dt_1 = fmt(form.warehouse_download_dt_1)

  if (form.is_double_load) {
    form.warehouse_download_dt_2 = fmt(form.warehouse_download_dt_2)
  } else {
    form.warehouse_id_2 = null
    form.warehouse_download_dt_2 = null
  }

  form.put(route('driver.journey.update', { journey: props.journey.id }), {
    preserveScroll: true,
    onSuccess: () => {
      // Torno alla lista viaggi
      form.reset() // opzionale: reset del form
      window.location.href = route('driver.journey.index')
    },
    onError: (errors) => {
      const first = Object.values(errors || {})[0]
      if (first) errorMsg.value = first
    },
  })
}




const setLoading = (value) => {
  loadingWizard.value = value;
};
const handleValidation = (isValid, tabIndex) => {
  console.log("Tab: " + tabIndex + " valid: " + isValid);
};
const handleErrorMessage = (err) => {
  errorMsg.value = err;
};

/**
 * Ritorna “adesso” arrotondato al multiplo di 5 minuti più vicino (coerente con il tuo minutes-increment=5)
 */
const nowRounded5 = () => {
  const m = dayjs()
  const rounded = Math.round(m.minute() / 5) * 5
  return m.minute(rounded).second(0).millisecond(0).toDate()
}

/**
 * Mappa l’indice tab -> se il campo data è vuoto lo valorizza con sysdate (solo in memoria)
 * Indici:
 *  0 = Partenza
 *  1..N = ordini
 *  N+1 = Scarico
 */
const primeDefaultsForTab = (tabIndex) => {
  // assicurati che gli ordini siano disponibili
  const orders = Array.isArray(form.orders) ? form.orders : []

  if (tabIndex === 0) {
    // PARTENZA
    if (!form.real_dt_start) form.real_dt_start = nowRounded5()
    return
  }

  const lastIndex = orders.length + 1

  if (tabIndex >= 1 && tabIndex <= orders.length) {
    // Tappa intermedia per ordine i-esimo
    const ord = orders[tabIndex - 1]
    if (ord && !ord.real_withdraw_dt) ord.real_withdraw_dt = nowRounded5()
    return
  }

  if (tabIndex === lastIndex) {
    // SCARICO
    if (!form.warehouse_download_dt_1) form.warehouse_download_dt_1 = nowRounded5()
    // se il doppio scarico è attivo, proponi anche per il secondo
    if (form.is_double_load && !form.warehouse_download_dt_2) {
      form.warehouse_download_dt_2 = nowRounded5()
    }
    return
  }
}

/**
 * Evento del wizard: si attiva quando si passa da un tab all’altro
 * (prevIndex, nextIndex) -> proponi i default quando entri nel prossimo tab
 */
const onTabChange = (prevIndex, nextIndex) => {
  primeDefaultsForTab(nextIndex)
}


const updateJourneyStatus = () => {
    return new Promise((resolve, reject) => {
        if (props.journey.state == 'creato'){
            if (form.real_dt_start) {
                axios.put(`/api/journey/updateState/${props.journey.id}`, {
                    new_state: 'attivo',
                    real_dt_start: dayjs(form.real_dt_start).format('YYYY-MM-DD HH:mm:ss')
                })
                .then(response => {
                    console.log(response.data);
                    resolve(true);
                })
                .catch(error => {
                    console.error(error);
                    reject("Errore API durante l'aggiornamento dello stato del viaggio");
                });
            } else {
                reject("Inserire data e ora di partenza");
            }
        }
        else {
            resolve(true);
        }
    });
};

const updateOrderStatus = (order) => {
    return new Promise((resolve, reject) => {
        const existingOrder = form.orders.find(o => o.id === order.id);
        if (existingOrder.state == 'pianificato'){
            if (existingOrder && existingOrder.real_withdraw_dt) {
                axios.put(`/api/order/updateState/${order.id}`, {
                    new_state: 'eseguito',
                    real_withdraw_dt: dayjs(existingOrder.real_withdraw_dt).format('YYYY-MM-DD HH:mm:ss')
                })
                .then(response => {
                    console.log(response.data);
                    resolve(true);
                })
                .catch(error => {
                    console.error(error);
                    reject("Errore API durante l'aggiornamento dello stato dell'ordine");
                });
            } 
            else {
                reject("Inserire data e ora di carico");
            }
        }
        else {
            resolve(true);
        }
    });
};

const startIndex = computed(
    () => {
        const step = props.journey.state == 'creato' ? 0 : 1
        const plannedOrdersCount = props.journey.orders.filter(order => order.state === 'eseguito').length;
       
        return 0 + step + plannedOrdersCount;
    }
)

const calculateIcon = (order) => {
    if(order.truck_location == 'vehicle'){
        return 'fa fa-truck'
    }
    else if(order.truck_location == 'trailer'){
        return 'fa fa-trailer'
    }
    else return 'fa fa-cart-arrow-down'
}

const manageDate = (date) => {
    console.log(date)
}

const checkThisToggle = (e) => {
    console.log(e.target.id)
    if(e.target.id == 'is_double_load' && form.is_double_load == true){
        form.is_temporary_storage = false
    }
    if(e.target.id == 'is_temporary_storage' && form.is_temporary_storage == true){
        form.is_double_load = false
    }
}

</script>

<style>
@import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css");

.vue-datepicker {
  box-sizing: content-box !important;
}
.dp__input_icons {
  box-sizing: content-box !important;
}
</style>
