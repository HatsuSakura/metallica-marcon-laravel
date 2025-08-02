<template>

<ul class="timeline timeline-vertical">
    <li>
        <div class="timeline-start timeline-box">
            Partenza
        </div>
        <div class="timeline-middle">
            <font-awesome-icon :icon="['fas', 'circle-check']" />
        </div>
        <div class="timeline-end timeline-box">
            <div class="flex flex-row gap-2 items-center">
                <span>
                    <font-awesome-icon :icon="['fas', 'route']" class="text-4xl"/>
                </span>                
                <span v-if="journey.state == 'attivo'" class="inline-flex items-center justify-center w-10 h-10 p-4 rounded-full bg-success text-white">
                    <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                </span>

            </div>
        </div>
        <hr />
    </li>

  <!-- ELENCO AUTOMATICO -->
  <li v-for="order in props.journey.orders">
    <hr/>
    <div class="timeline-start timeline-box">
        {{ order.customer.ragione_sociale }} <br/>
        Sede: {{ order.site.indirizzo }} 
    </div>
    <div class="timeline-middle">
        <font-awesome-icon :icon="['fas', 'circle-check']" />
    </div>
    <div class="timeline-end timeline-box">
        <div class="flex flex-row gap-2 items-center">
            <span v-if="order.truck_location == 'vehicle'">
                <font-awesome-icon :icon="['fas', 'truck']" class="text-4xl"/>
            </span>
            <span v-else-if="order.truck_location == 'trailer'">
                <font-awesome-icon :icon="['fas', 'trailer']" class="text-4xl"/>
            </span>
            <span v-else>
                <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-4xl"/>
            </span>

            <div v-if="props.journey.state == 'attivo'" class="flex flex-row gap-2 items-center">
                <span v-if="order.state == 'eseguito'" class="inline-flex items-center justify-center w-10 h-10 p-4 rounded-full bg-success text-white">
                    <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                </span>
            </div>

        </div>
    </div>
    <hr />
  </li>
  <!-- FINE ELENCO AUTOMATICO -->

  <!-- ELENCO MAGAZZINI -->
  <li>
        <hr/>
        <div class="timeline-start timeline-box">
            <span v-if="props.is_double_load">PRIMO </span>Scarico Magazzino
        </div>
        <div class="timeline-middle">
            <font-awesome-icon :icon="['fas', 'circle-check']" />
        </div>
        <div class="timeline-end timeline-box">
            <div class="flex flex-row gap-2 items-center">
                <span>
                    <font-awesome-icon :icon="['fas', 'warehouse']" class="text-4xl"/>
                </span>

                    <span v-if="props.journey.state == 'completato'" class="inline-flex items-center justify-center w-10 h-10 p-4 rounded-full bg-success text-white">
                        <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                    </span>

            </div>
        </div>
    </li>
</ul>

</template>

<script setup>

import { defineProps, reactive } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    journey : Object,
    warehouses: Object,
})

const form = reactive({
    dt_start: Date.now(),
    warehouse_id_1: '',
    warehouse_download_dt_1: '',
    warehouse_id_2: '',
    warehouse_download_dt_2: '',
})
</script>