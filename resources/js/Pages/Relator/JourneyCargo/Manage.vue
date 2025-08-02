<template>
<div class="w-full">

    <!-- HEAD and BACK BUTTON -->
    <div class="mb-2 flex flex-row items-center justify-between gap-4">
            <Link
            class="btn btn-ghost" 
            :href="route('relator.dashboard')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna a  Dashboard
        </Link>


        <div class="my-4 flex flex-row items-center gap-4"> 
          <div v-if="isRefreshig" class="flex flex-row items-center gap-2"> 
              <span class="text-error">
                
              </span>
              <span class="loading loading-ring loading-xl text-error"></span>
          </div>
          <div v-if="!isPolling" class="flex flex-row items-center gap-2">
            <span class="text-success">
              Mod. <span class="font-medium">MASTER</span> - Dati modificabili
            </span>
            <font-awesome-icon :icon="['fas', 'lock-open']" class="text-success text-2xl"/>
            <button @click="isPolling = true" class="btn btn-outline btn-success">
              Attiva LIVE
              <font-awesome-icon :icon="['fas', 'bullhorn']" class="text-2xl"/>
            </button>
          </div>
          <div v-else class="flex flex-row items-center gap-2">
            <span class="text-error">
              Mod. <span class="font-medium">SLAVE</span> - Aggiornamento automatico
            </span>
            <font-awesome-icon :icon="['fas', 'lock']" class="text-error text-2xl"/>
            <button @click="isPolling = false" class="btn btn-outline btn-error">
              LIVE ON 
              <font-awesome-icon :icon="['fas', 'bullhorn']" class="text-2xl"/>
              Click to STOP
            </button>
          </div>
        </div>

    </div>
  
    <div class="mb-4 flex flex-row justify-between items-center w-full">
          <div class="text-lg font-medium">
              Gestione Materiali Scaricati in {{ journeyCargo.warehouse.denominazione }} da cassone {{ journeyCargo.truck_location === 'vehicle'? 'Motrice': 'Rimorchio' }}
          </div>
          <div>
              <JourneyCargoHead :journeyCargo="journeyCargo"/>
          </div>
      </div>
    



        <Box class="flex flex-row mb-2 w-full">
          <div class="flex flex-row gap-4 justify-evenly py-4 w-1/2">
            <div class="flex flex-row items-center gap-2 justify-evenly">
              <label for="utente" class="font-medium">Capo Magazziniere:</label>
              {{ user.name }} 
              {{ user.surname }}
            </div>

            <div class="flex flex-row items-center gap-2 justify-evenly">
                <label for="has_ragno" class="font-medium">Uso del Ragno:</label>
                <input type="checkbox" id="has_ragno" v-model="form.has_ragno" class="toggle">
            </div>
          </div>

          <div v-if="form.has_ragno" class="w-1/2 flex flex-row justify-between items-center">


            <div class="flex flex-row items-center gap-2 justify-start">
              <label for="ragnista_id" class="font-medium">Ragnista:</label>
              <select id="ragnista_id" v-model="form.ragnista_id" class="select select-bordered">
                <option value="" selected disabled>Seleziona un Ragnista</option>
                <option v-for="worker in warehouseWorkers.filter(worker => worker.is_ragnista)" :key="worker.id" :value="worker.id">
                  {{ worker.name }} {{ worker.surname }}
                </option>
              </select>

              <label for="machinery_time" class="font-medium">Tempo uso ragno:</label>
              <select id="machinery_time_hh" v-model="machinery_time_hh" class="select select-bordered">
                <option value="0" selected>00</option>
                <option v-for="hour in 23" :key="hour" :value="hour">{{ String(hour).padStart(2, '0') }}</option>
              </select>
              ore
              <select id="machinery_time_mm" v-model="machinery_time_mm" class="select select-bordered">
                <option value="0" selected>00</option>
                <option v-for="min in 11" :key="min*5" :value="min*5">{{ String(min*5).padStart(2, '0') }}</option>
              </select>
              minuti
            
          </div>


            <div>
              <button class="btn btn-primary btn-circle btn-outline btn-success" :disabled="isPolling" @click="saveJourneyCargo(props.journeyCargo.id)">
                <font-awesome-icon :icon="['fas', 'save']" class="text-2xl"/>
              </button>
            </div>
          </div>

        </Box>


        <div>
            <WorkerItemRow
                v-for="(item, index) in journeyCargoItems.items.filter(item => !item.pivot?.is_double_load)"
                :key="item.id"
                :item="item"
                :index="index"
                :canModify="!isPolling"
                :warehouseManagers="warehouseManagers"
                :warehouse="journeyCargo.warehouse"
                :manualModified="manualFractions[item.id] !== undefined"
                :parentHasRagno="form.has_ragno"
                :parentMachineryTime="Number(form.machinery_time)"
                @update-manual-machinery-time="handleUpdateManualMachineryTime(item.id, $event)"
                @reset-manual-machinery-time="handleResetManualMachineryTime(item.id)"
                @save-item="handleSaveItem(item)"
                class="mb-2"
              />
        </div>

        <!-- ALTRI ITEM dello stesso JOURNEY -->
        <div class="mt-8">
            <div v-if="otherJourneyCargo.items.length > 0">
                <div class="flex flex-row justify-between items-center w-full mb-2">
                    <div class="text-lg font-medium">
                        Materiali dello stesso viaggio scaricati in 
                        {{ otherJourneyCargo.warehouse.denominazione }}
                        da cassone 
                        {{ otherJourneyCargo.truck_location === 'vehicle'? 'Motrice': 'Rimorchio' }}
                    </div>
                    <div>
                        <JourneyCargoHead :journeyCargo="otherJourneyCargo"/>
                    </div>
                </div> 
                <ItemForOtherJourneyCargoManagment v-for="(item, index) in otherJourneyCargoItems.items.filter(item => !item.pivot?.is_double_load)" 
                    :key="item.id"
                    :item="item"
                    :index="index"
                    :warehouseManagers="warehouseManagers"
                    :warehouse="otherJourneyCargo.warehouse"
                    :journeyCargoRequesting="journeyCargo"
                    @itemMoved="handleItemMoved"
                    class="mb-2"
                />
            </div>
            <EmptyState v-else>
                <div class="text-xl font-medium">
                    Nessun altro elemento per il viaggio
                </div>
            </EmptyState>

        </div>

    </div>

</template>

<script setup>
import { defineProps, computed, watch, reactive, watchEffect , onMounted, onBeforeUnmount, ref } from 'vue';
import { Link, usePage, useForm, usePoll } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia'
import Box from '@/Components/UI/Box.vue';
import ZeroPaddingId from '@/Components/UI/ZeroPaddingId.vue';
import ItemForOtherJourneyCargoManagment from './Components/ItemForOtherJourneyCargoManagment.vue';
import WorkerItemRow from '@/Pages/Worker/Components/WorkerItemRow.vue';
import JourneyCargoHead from '@/Components/JourneyCargoHead.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import axios from 'axios';
import { useStore } from 'vuex';

const props = defineProps({
    journeyCargo: Object,
    journeyCargoItems: Object,
    otherJourneyCargo: Object,
    otherJourneyCargoItems: Object,
    //orders: Object,
    warehouseManagers: Object,
    warehouseWorkers: Object,
})

const page = usePage();
const store = useStore();
const user = computed(
    () => page.props.user
)

const isPolling = ref(false);
const isRefreshig = ref(false);
const { start, stop } = usePoll(
  10000,
  {
    only: ['journeyCargoItems', 'otherJourneyCargoItems'],
    onStart: () => isRefreshig.value = true,
    onFinish: () => isRefreshig.value = false,
  },
  {
    autoStart: isPolling.value,
  }
)

watch(isPolling, (isPolling) => {
  isPolling ? start() : stop()
})


const form = useForm({
    has_ragno: props.journeyCargoItems.has_ragno || false,
    ragnista_id: props.journeyCargoItems.ragnista_id || '',
    machinery_time: props.journeyCargoItems.machinery_time || 0,
    user_id: user ? user.id : null, // Fallback to null if user is not defined
    items: props.journeyCargoItems.items, // Access the items from the ref, or default to an empty array
});


// Handler for when a child component signals that an item has been moved
const handleItemMoved = (updatedItem) => {
  // Remove the item from the otherJourneyCargo.items array
  const itemToMove = props.otherJourneyCargoItems.items.find(item => item.id === updatedItem.id);
  props.otherJourneyCargoItems.items = props.otherJourneyCargoItems.items.filter(item => item.id !== updatedItem.id);
  
  // Optionally update the journeyCargo object if needed, then add the updated item
  props.journeyCargoItems.items.push(itemToMove);
};



/**
 * Calcolo del machinery_time
 */
    // Computed property for hours with getter and setter
    const machinery_time_hh = computed({
      get() {
        return Math.floor(form.machinery_time / 60);
      },
      set(newHour) {
        // Update machinery_time based on the new hours and current minutes
        form.machinery_time = Number(newHour) * 60 + Number(machinery_time_mm.value);
        updateMachineryItemFraction();
      }
    });

    // Computed property for minutes with getter and setter
    const machinery_time_mm = computed({
      get() {
        return form.machinery_time % 60;
      },
      set(newMin) {
        // Update machinery_time based on the current hours and new minutes
        form.machinery_time = Number(machinery_time_hh.value) * 60 + Number(newMin);
        updateMachineryItemFraction();
      }
    });



// This object tracks manual fractions keyed by item ID.
const manualFractions = reactive({})

// Compute the total of manual fractions.
const totalManual = computed(() => {
  return Object.values(manualFractions).reduce((sum, val) => {
    const num = Number(val);
    return sum + (isNaN(num) ? 0 : num);
  }, 0);
});

// Calculate how many items are NOT manually set.
const nonManualCount = computed(() => {
  if (props.journeyCargoItems.items){
    return props.journeyCargoItems.items.filter(item => manualFractions[item.id] === undefined && item.is_ragnabile).length
  }
  return 0
  
})

// Calculate the remaining machinery time available for automatic distribution.
const remainingTime = computed(() => {
  return form.machinery_time - totalManual.value
})

// The automatic fraction for non-manually set items.
const autoFraction = computed(() => {
    // Round 47.5 down to 45 (i.e. round halves downward)
  return nonManualCount.value > 0 ? Math.floor(( remainingTime.value / nonManualCount.value ) / 5) * 5 : 0
})

watch(() => nonManualCount, (newVal) => {
    updateMachineryItemFraction();
}, { deep: true });


function updateMachineryItemFraction(){
  props.journeyCargoItems.items.forEach(item => {
    if (item.is_ragnabile){
        if (manualFractions[item.id] !== undefined) {
            // If the item was manually modified, use that value.
            item.machinery_time_fraction = manualFractions[item.id]
        } 
        else {
            // Otherwise, assign the automatically distributed fraction.
            item.machinery_time_fraction = autoFraction.value
        }
    }
  })
}


// Handler for events coming from WorkerItemRow.
// The event payload might be an object like { value: newFraction, isManual: true/false }
function handleUpdateManualMachineryTime(itemId, payload) {
  const item = props.journeyCargoItems.items.filter(item => item.id === itemId)[0]
  if (payload.isManual) {
    // Directly set or update the property on the reactive object.
    manualFractions[itemId] = payload.value;
    item.is_machinery_time_manual = true
  } else {
    // Remove the property using the delete operator.
    delete manualFractions[itemId];
    item.is_machinery_time_manual = false
  }
  updateMachineryItemFraction()
}

function handleResetManualMachineryTime(itemId){
    // Remove the property using the delete operator.
    delete manualFractions[itemId];
    updateMachineryItemFraction()
}


const saveJourneyCargo = async(journeyCargo_id) => {
      try {
          const url = `/api/warehouse-journey-cargos/${journeyCargo_id}`;
          const response = await axios.put(url, {
            has_ragno : form.has_ragno,
            ragnista_id : form.ragnista_id,
            machinery_time : form.machinery_time,
          });

          console.log('Save journeyCargo response:', response.data);
          store.dispatch('flash/queueMessage', { type: 'success', text: 'Cassone salvato correttamente' });

      } catch (error) {
          console.error('Error saving journeyCargo:', error);
          store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore durente la procedura di salvataggio ' + error });
      } 
}


</script>
