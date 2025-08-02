<template>

<Box>
  <div class="flex flex-row gap-4 w-full items-stretch justify-start">

    <!-- ID RIGA -->
    <div class="flex flex-row items-center">
      <div class="badge badge-outline badge-info">
        {{ index +1 }}
      </div>
    </div>

    <!-- BLOCCO Quantità e tipologia -->
    <div class="flex flex-col justify-evenly gap-1 w-1/4">

      <div>
        <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl"/>
        {{ item.order.customer.ragione_sociale }}
      </div>
      
      <div class="flex flex-row gap-4">
        <div class="badge badge-primary badge-lg">
            {{item.holder_quantity}} x {{ item.holder.name }}     
        </div>

        <div class="badge badge-lg" :class="item.cer_code.is_dangerous ? 'badge-error' : 'badge-primary'">
          {{ item.cer_code.code }}
        </div>

      </div>
      <div>
          <span v-if="item.adr_hp" class="badge badge-error badge-lg">HP: {{ item.adr_hp }}</span>
      </div>
      

      <div :class="item.cer_code_id.is_dangerous ? 'text-error' : 'text-info'">
        {{ item.cer_code.description }}
      </div>

      <div class="">
        {{ item.description }}
      </div>
    </div>

    <!-- BLOCCO Peso e magazzino -->
    <div class="flex flex-col gap-1 justify-evenly">
      <div>
        <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl text-primary"/>
        cliente: 
        <div class="badge badge-primary badge-lg">
          {{ item.weight_declared }} Kg    
        </div>
      </div>

      <div>
        <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl text-primary"/>
        previsto:
        <div class="badge badge-primary badge-lg">
          {{ item.warehouse.denominazione }}
        </div>
      </div>

      <div>
        <!-- MAGAZZINO Select/Option -->
        <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl text-primary"/>
        effettivo: 
        <div class="badge badge-primary badge-lg">
          {{ warehouse.denominazione }}
        </div>
      </div>

    </div>

    <!-- BLOCCO Operazioni Magazzino-->
    <div class="flex flex-col justify-evenly gap-1 w-1/2">

      <div class="flex flex-row gap-2 w-full">

        <!-- BLOCCO Magazziniere e PESI -->
        <div class="flex flex-col justify-evenly gap-1 w-2/5">

          

          <div class="flex flex-row gap-1">
            <input v-model="item.weight_gross" type="text" class="input input-bordered flex" placeholder="LORDO"/>
            <input v-model="item.weight_tare" type="text"  class="input input-bordered flex" placeholder="TARA" />
            <input v-model="item.weight_net"  type="text"  class="input input-bordered flex" placeholder="NETTO"/>
          </div>

          <select v-if="user.role === 'warehouse_chief' || user.is_admin" id="warehouse_manager_id" v-model="item.warehouse_manager_id" class="select select-bordered">
            <option value="" selected disabled>Seleziona Magazziniere</option>
            <option v-for="manager in props.warehouseManagers" :key="manager.id" :value="manager.id">
              {{ manager.name }} {{ manager.surname }}
            </option>
          </select>
          <select v-else-if="user.role === 'warehouse_manager' || user.is_admin" id="worker_id" v-model="item.worker_id" class="select select-bordered">
            <option value="" selected disabled>Seleziona Operaio</option>
            <option v-for="worker in props.warehouseWorkers" :key="worker.id" :value="worker.id">
              {{ worker.name }} {{ worker.surname }}
            </option>
          </select>

        </div>


        <div class="flex flex-col justify-evenly gap-1 w-3/5">

          <!-- TEMPO RAGNO-->
          <div class="flex flex-row gap-2">

            <div class="flex flex-row items-center gap-2">
              <input type="checkbox" id="has_ragno" v-model="localIsRagnabile" class="toggle" :disabled="!parentHasRagno" @change="onToggleIsRagnabileChange" >
              Ragnabile
            </div>

            <div v-if="localIsRagnabile" class="flex flex-row items-center gap-2">
              <select id="machinery_time_fraction_hh" v-model="machinery_time_fraction_hh" class="select select-bordered" @change="onManualMachineryTimeInput">
                <option value="0" selected>0</option>
                <option v-for="hour in 8" :key="hour" :value="hour">{{ String(hour) }}</option>
              </select>
              <div>hh</div>
              <select id="machinery_time_fraction_mm" v-model="machinery_time_fraction_mm" class="select select-bordered" @change="onManualMachineryTimeInput">
                <option value="0" selected>00</option>
                <option v-for="min in 11" :key="min*5" :value="min*5">{{ String(min*5).padStart(2, '0') }}</option>
              </select>
              <div>mm</div>

              <div v-if="props.manualModified" class="flex flex-col gap-2">
                <font-awesome-icon :icon="['fas', 'hand']" class="text-2xl text-primary"/>
                <button class="btn btn-primary btn-circle btn-outline btn-sm" @click="resetManualMachineryTimeInput">
                  <font-awesome-icon :icon="['fas', 'rotate-left']" class="text-lg"/>
                </button>
              </div>
            </div>

          </div>
          
          <!-- TEMPO SELEZIONE-->
          <div class="flex flex-row gap-2">

            <div class="flex flex-row items-center gap-2">
              <input type="checkbox" id="has_ragno" v-model="has_selection" class="toggle">
              Selezione
            </div>

            <div v-if="item.has_selection" class="flex flex-row items-center gap-2">
              <select id="selection_time_hh" v-model="selection_time_hh" class="select select-bordered">
                <option value="0" selected>0</option>
                <option v-for="hour in 8" :key="hour" :value="hour">{{ String(hour) }}</option>
              </select>
              <div>hh</div>
              <select id="selection_time_mm" v-model="selection_time_mm" class="select select-bordered">
                <option value="0" selected>00</option>
                <option v-for="min in 11" :key="min*5" :value="min*5">{{ String(min*5).padStart(2, '0') }}</option>
              </select>
              <div>mm</div>
            </div>

          </div>

        </div>

      </div>


      <div>
        <input 
            v-model="item.warehouse_notes" 
            type="text" 
            class="input input-bordered flex w-full"
            placeholder="Note / Non conformità"
        />
      </div>
    </div>

    <!-- TASTO SAVE -->
    <div class="flex flex-row items-center justify-end">
      <!--
      <button class="btn btn-primary btn-circle btn-outline btn-success" :disabled="!canModify" @click="$emit('save-item', item)">
      -->
      <button class="btn btn-primary btn-circle btn-outline btn-success" :disabled="!canModify" @click="saveItem(item)">
        <font-awesome-icon :icon="['fas', 'save']" class="text-2xl"/>
      </button>
    </div>

  </div>
  
</Box>

</template>
  
  <script setup>
  import Box from "@/Components/UI/Box.vue";
  import { ref, watch, computed } from "vue";
  import { usePage } from '@inertiajs/vue3';
  import "vue-select/dist/vue-select.css";
  import axios from 'axios';
  import { useStore } from 'vuex';
  
  const props = defineProps({ 
    item: Object,
    index: Number,
    canModify: Boolean,
    warehouseManagers: Object,
    warehouseWorkers: Object,
    warehouse: Object,
    parentHasRagno: Boolean, 
    parentMachineryTime: Number, 
    manualModified: Boolean,
  });
  
  const emit = defineEmits(['update-manual-machinery-time', 'update-is-ragnabile-toggle', 'save-item']);

  
  const page = usePage();
  const user = computed(
      () => page.props.user
  )

  const store = useStore();
  const saveItem = async(item) => {
      try {
          const url = `/api/warehouse-order-items/${item.id}`;
          const response = await axios.put(url, {
            warehouse_id : item.warehouse_id,
            warehouse_notes : item.warehouse_notes,
            warehouse_manager_id : item.warehouse_manager_id,
            worker_id : item.worker_id,
            has_selection : item.has_selection,
            selection_time : item.selection_time,
            is_ragnabile : item.is_ragnabile,
            machinery_time_fraction : item.machinery_time_fraction,
            is_machinery_time_manual : item.is_machinery_time_manual,
            is_transshipment : item.is_transshipment,
            weight_gross : item.weight_gross,
            weight_tare : item.weight_tare,
            weight_net : item.weight_net,
          });

          console.log('Save item response:', response.data);
          store.dispatch('flash/queueMessage', { type: 'success', text: 'Materiale salvato correttamente' });

      } catch (error) {
          console.error('Error saving item:', error);
          store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore durente la procedura di salvataggio ' + error });
      } 
  }



  // Computed property for minutes with getter and setter
    const warehouse_manager_id = computed({
      get() {
        return props.item.warehouse_manager_id;
      },
      set(new_warehouse_manager_id) {
        props.item.warehouse_manager_id = new_warehouse_manager_id;
      }
    });

    // Computed property for hours with getter and setter
    const machinery_time_fraction_hh = computed({
      get() {
        return Math.floor(props.item.machinery_time_fraction / 60);
      },
      set(newHour) {
        // Update item.machinery_time_fraction based on the new hours and current minutes
        props.item.machinery_time_fraction = Number(newHour) * 60 + Number(machinery_time_fraction_mm.value);
      }
    });

    // Computed property for minutes with getter and setter
    const machinery_time_fraction_mm = computed({
      get() {
        return props.item.machinery_time_fraction % 60;
      },
      set(newMin) {
        // Update item.machinery_time_fraction based on the current hours and new minutes
        props.item.machinery_time_fraction = Number(machinery_time_fraction_hh.value) * 60 + Number(newMin);
      }
    });


    const has_selection = computed({
      get() {
        return Boolean(props.item.has_selection);
      },
      set(new_has_selection) {
        props.item.has_selection = new_has_selection;
      }
    });

    // Computed property for hours with getter and setter
    const selection_time_hh = computed({
      get() {
        return Math.floor(props.item.selection_time / 60);
      },
      set(newHour) {
        // Update item.selection_time based on the new hours and current minutes
        props.item.selection_time = newHour * 60 + selection_time_mm.value;
      }
    });

    // Computed property for minutes with getter and setter
    const selection_time_mm = computed({
      get() {
        return props.item.selection_time % 60;
      },
      set(newMin) {
        // Update item.selection_time based on the current hours and new minutes
        props.item.selection_time = selection_time_hh.value * 60 + newMin;
      }
    });


    // Local state for the toggle, initialized from the item.
    //const localIsRagnabile = ref(props.item.is_ragnabile)
    const localIsRagnabile = computed({
      get() {
        return Boolean(props.item.is_ragnabile);
      },
      set(new_is_ragnabile) {
        props.item.is_ragnabile = new_is_ragnabile;
      }
    });

    // When the user toggles is_ragnabile, you might emit an event if needed.
    function onToggleIsRagnabileChange() {
      // For example, you could emit:
       emit('update-is-ragnabile-toggle', {toggle: localIsRagnabile.value} )
    }

    // When the input changes, assume the user is manually setting the value.
    function onManualMachineryTimeInput(event) {
      const newValue = Number(props.item.machinery_time_fraction)
      // Ensure that the new value does not exceed the parent's machinery time.
      const safeValue = Math.min(newValue, props.parentMachineryTime)
      // Emit the change to the parent.
      emit('update-manual-machinery-time', { value: safeValue, isManual: true })
    }


    // When the user toggles is_ragnabile, you might emit an event if needed.
    function resetManualMachineryTimeInput() {
      // For example, you could emit:
       emit('reset-manual-machinery-time' )
    }




    // A flag to avoid triggering watchers recursively
    const updatingWeights = ref(false);

    /**
     * Watch the net field.
     * When weight_net changes, recalc weight_gross as: gross = net + tare.
     */
    watch(
      () => props.item.weight_net,
      (newNet, oldNet) => {
        if (updatingWeights.value) return;
        updatingWeights.value = true;
        // Parse values as numbers to avoid string concatenation issues
        props.item.weight_tare = Number(props.item.weight_gross) - Number(newNet);
        updatingWeights.value = false;
      }
    );

    /**
     * Watch the tare field.
     * When weight_tare changes, recalc weight_gross as: gross = net + tare.
     */
    watch(
      () => props.item.weight_tare,
      (newTare, oldTare) => {
        if (updatingWeights.value) return;
        updatingWeights.value = true;
        props.item.weight_net = Number(props.item.weight_gross) - Number(newTare);
        updatingWeights.value = false;
      }
    );

    /**
     * Watch the gross field.
     * When weight_gross changes, recalc weight_net as: net = gross - tare.
     * (You could also choose to update tare, but you need to decide which field takes precedence.)
     */
    watch(
      () => props.item.weight_gross,
      (newGross, oldGross) => {
        if (updatingWeights.value) return;
        updatingWeights.value = true;
        props.item.weight_net = Number(newGross) - Number(props.item.weight_tare);
        updatingWeights.value = false;
      }
    );


  </script>
  
  <style scoped>
  .cer-list-dangerous {
    color: red;
    font-weight: bold;
  }
  
  .cer-selected-dangerous {
    background-color: #ffe6e6; /* Light red background for dangerous items */
  }
  </style>
  