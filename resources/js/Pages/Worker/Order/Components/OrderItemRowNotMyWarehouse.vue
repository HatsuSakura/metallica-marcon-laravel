<template>

  <div class="border border-base-300 bg-base-100" style="border-radius: 1rem; padding-top:2px; padding-bottom: 2px;">
    <!-- HEADER -->
    <div class="collapse-title flex justify-between items-center">
      <!-- ID -->
      <div class="flex flex-col items-center justify-center">
        <div class="badge badge-outline badge-info">{{ index + 1 }}</div>
        <div class="badge badge-outline badge-info">{{ localItem.id }}</div>
      </div>
      <!-- Quantità e holder -->
      <div class="flex flex-row items-center gap-2">
        <div class="text-xl font-bold">
          {{ localItem.holder_quantity }} x
        </div>
        <div class="badge badge-primary badge-lg">
          {{ localItem.holder.name }}
        </div>
      </div>

      <!-- Codice CER -->
      <div class="tooltip tooltip-top" :class="localItem.cer_code.is_dangerous ? 'tooltip-error' : 'tooltip-info'"
        :data-tip="localItem.cer_code.description">
        <div class="badge badge-lg" :class="localItem.cer_code.is_dangerous ? 'badge-error' : 'badge-primary'">
          {{ localItem.cer_code.code }}
        </div>
      </div>
      <!-- Peso dichiarato -->
      <div class="flex items-center gap-1">
        <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl text-primary" />
        <div v-if="localItem.weight_declared">
          dichiarato
          <div class="badge badge-primary badge-lg">{{ localItem.weight_declared }} Kg</div>
        </div>
        <div v-else>
          <div v-if="localItem.is_warehouse_added" class="badge badge-info badge-lg">
            + Magazzino
          </div>
          <div v-else class="badge badge-secondary badge-lg">Nessuno</div>
        </div>
      </div>
      <!-- Descrizione -->
      <div class="w-1/2">{{ localItem.description || 'nessuna descrizione' }}</div>


        <div class="flex flex-row items-center justify-end">
            <button class="btn btn-success btn-outline" @click="importItem(item)">
                <font-awesome-icon :icon="['fas', 'file-import']" class="text-2xl"/>
                Importa
            </button>
        </div>



    </div>
  </div>


</template>

<script setup>
import Box from '@/Components/UI/Box.vue'
import EmptyState from '@/Components/UI/EmptyState.vue'
import ImageUploader from '@/Pages/Worker/Order/Components/ImageUploader.vue'
import { ref, reactive, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import dayjs from 'dayjs'

const props = defineProps({
  item: Object,
  index: Number,
})

const localItem = reactive({ 
  ...props.item
})

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
