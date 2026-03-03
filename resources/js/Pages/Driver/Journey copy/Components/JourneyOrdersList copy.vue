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

                <VueDatePicker
                    v-model="form.dt_start"
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
                ></VueDatePicker>

                <button v-if="journey.state == 'creato'" class="btn btn-success btn-circle">
                    <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                </button>
                <span v-else-if="props.journey.state == 'attivo'">
                    <font-awesome-icon :icon="['fas', 'check']" class="text-2xl"/>
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
                <button class="btn btn-primary btn-circle">
                    <font-awesome-icon :icon="['fas', 'pen']" class="text-lg"/>
                </button>

                <button v-if="order.state == 'pianificato'" class="btn btn-success btn-circle">
                    <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                </button>
                <span v-else>
                    <font-awesome-icon :icon="['fas', 'check']" class="text-2xl"/>
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

                <div v-if="props.journey.state == 'attivo'" class="flex flex-row gap-2 items-center">

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
                        placeholder="Data Scarico"
                        :range=false
                        time-picker-inline
                        auto-apply
                        minutes-increment="5"
                        minutes-grid-increment="5"
                        closeOnScroll="false"
                        @closed="manageDate"
                    ></VueDatePicker>

                    <button v-if="props.journey.state == 'attivo'" class="btn btn-success btn-circle">
                        <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                    </button>
                    <span v-else-if="props.journey.state == 'completato'">
                        <font-awesome-icon :icon="['fas', 'check']" class="text-2xl"/>
                    </span>

                </div>
            </div>
        </div>
        <hr  v-if="props.is_double_load" />
    </li>
    <li v-if="props.is_double_load">
        <hr/>
        <div class="timeline-start timeline-box">
            SECONDO Scarico Magazzino
        </div>
        <div class="timeline-middle">
            <font-awesome-icon :icon="['fas', 'circle-check']" />
        </div>
        <div class="timeline-end timeline-box">
            <div class="flex flex-row gap-2 items-center">

                <span>
                    <font-awesome-icon :icon="['fas', 'warehouse']" class="text-4xl"/>
                </span>

                <div v-if="props.journey.state == 'attivo'" class="flex flex-row gap-2 items-center">
                    
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
                        placeholder="Data Scarico"
                        :range=false
                        time-picker-inline
                        auto-apply
                        minutes-increment="5"
                        minutes-grid-increment="5"
                        closeOnScroll="false"
                        @closed="manageDate"
                    ></VueDatePicker>

                    <button v-if="props.journey.state == 'attivo'" class="btn btn-success btn-circle">
                        <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                    </button>
                    <span v-else-if="props.journey.state == 'completato'">
                        <font-awesome-icon :icon="['fas', 'check']" class="text-2xl"/>
                    </span> 

                </div>
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
    is_double_load : Boolean,
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