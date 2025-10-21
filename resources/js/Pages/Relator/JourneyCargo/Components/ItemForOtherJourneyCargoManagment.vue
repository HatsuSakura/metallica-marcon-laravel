<template>

<Box>
  <div class="flex flex-row gap-4 w-full items-stretch justify-start">

    <!-- ID RIGA -->
    <div class="flex flex-row items-center">
      <div class="badge badge-outline badge-info">
        {{ index +1 }}
      </div>
    </div>

    <div class="flex flex-row justify-between w-full items-center">

        <div class="flex flex-col gap-2">
            <!-- BLOCCO Quantità e tipologia -->
            <div class="flex flex-col justify-evenly gap-2">

            <div>
                <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl"/>
                {{ item.order.customer.ragione_sociale }}
            </div>

            <div class="flex flex-row justify-start gap-4">
                <div class="badge badge-primary badge-lg">
                    {{item.holder_quantity}} x {{ item.holder.name }}     
                </div>

                <div class="badge badge-lg" :class="item.cer_code.is_dangerous ? 'badge-error' : 'badge-primary'">
                {{ item.cer_code.code }}
                </div>

                <div :class="item.cer_code_id.is_dangerous ? 'text-error' : 'text-info'">
                    {{ item.cer_code.description }}
                </div>
            </div>

            <div class="flex flex-row justify-start gap-4">

                <div>
                <span v-if="item.adr_hp" class="badge badge-error badge-lg">HP: {{ item.adr_hp }}</span>
                <span v-else class="badge badge-primary badge-lg">NO HP</span>
                </div>

                <div>
                <span v-if="item.adr" class="badge badge-primary badge-lg">Cod. UN = {{ item.adr_onu_code }} con 
                    <span v-if="item.adr_totale" class="badge badge-primary badge-lg">ADR Totale</span>
                    <span v-if="item.adr_esenzione_totale" class="badge badge-primary badge-lg">Esenzione Totale</span>
                    <span v-if="item.adr_esenzione_parziale" class="badge badge-primary badge-lg">Esenzione Parziale</span>
                </span>
                <span v-else class="badge badge-primary badge-lg">NO ADR</span>
                </div>

                <div class="">
                    {{ item.description }}
                </div>
            </div>
            </div>

            <!-- BLOCCO Peso e magazzino -->
            <div class="flex flex-row justify-start gap-2">
                <div>
                    Peso dichiarato: 
                    <div class="badge badge-primary badge-lg">
                    {{ item.weight_declared }} Kg    
                    </div>
                </div>

                <div>
                    Scarico Previsto:
                    <div class="badge badge-primary badge-lg">
                    {{ item.warehouse.denominazione }}
                    </div>
                </div>

                <div>
                    <!-- MAGAZZINO Select/Option -->
                    Scarico Effettivo: 
                    <div class="badge badge-primary badge-lg">
                    {{ warehouse.denominazione }}
                    </div>
                </div>

            </div>
        </div>

        <!-- PULSANTE ACQUISIZIONE -->
        <div class="flex flex-row items-center justify-end">
            <button class="btn btn-success btn-outline" @click="importItem(item)">
                <font-awesome-icon :icon="['fas', 'file-import']" class="text-2xl"/>
                Importa
            </button>
        </div>

    </div>




  </div>
  
</Box>




</template>

<script setup>
import JourneyCargoHead from '@/Components/JourneyCargoHead.vue';
import {defineEmits} from 'vue';    
import Box from '@/Components/UI/Box.vue';
import axios from 'axios';
import { useStore } from 'vuex';

const props = defineProps({
    item: Object,
    index: Number,
    warehouseManagers: Object, 
    warehouse: Object, 
    journeyCargoRequesting: Object,
});

const emit = defineEmits(['itemMoved']);

const store = useStore();

const importItem = async(item) => {
    try {
        const url = `/api/warehouse-order-items/move-journey-cargo/${item.id}`;
        const response = await axios.post(url, {
            journey_cargo_id: props.journeyCargoRequesting.id,
        });
        // Emit an event with the updated orderItem returned from the API.
        emit('itemMoved', response.data.orderItem);

        console.log('Move warehouse response:', response.data);
        store.dispatch('flash/queueMessage', { type: 'success', text: 'Materiale importato correttamente' });

    } catch (error) {
        console.error('Error moving warehouse item:', error);
        store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore durente la procedura di import ' + error });
    } 
}
</script>
