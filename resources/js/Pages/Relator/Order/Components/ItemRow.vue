<template>
    <!-- MAIN ROW DIV -->
    <div class="flex flex-col items-start gap-2 mb-2">

       
        <div class="flex flex-row gap-2 w-full">

           <!-- CER Selector -->
            <div :class="getCerStyle(item.cer_code_id)">
                <v-select 
                v-model="item.cer_code_id" 
                :id="'cer-' + index" 
                :options="props.cerList"
                label="code"
                :reduce="cer => cer.id" 
                :filterable="true" 
                :searchable="true"
                placeholder="Cod. CER"
                class="custom-style-chooser w-32 min-w-max"
                >
                <template #option="{ code, description, is_dangerous }">
                    <span :class="is_dangerous === 1 ? 'cer-list-dangerous' : 'cer-list-normal'">{{ code }}</span>
                    <br />
                    <span class="text-xs"><cite>{{ description }}</cite></span>
                </template>
                </v-select>
            </div>


            <!-- Toggle SFUSO -->
            <div class="flex items-center">
                <label class="label" for="sfuso">Sfuso</label>
                <input 
                  id="sfuso" 
                  v-model="item.is_bulk" 
                  type="checkbox" 
                  class="toggle"
                />
            </div>
       
            <!-- Quantity Number Input -->
            <input 
                v-model.number="item.holder_quantity" 
                type="number" 
                class="input input-bordered w-16"
                placeholder="Q.tà"
                min="1"
                :disabled="item.is_bulk"
            />
        
            <!-- Holder Select/Option -->
            <div>
              <select v-model="item.holder_id" 
                id="holder" 
                class="select select-bordered"
                :disabled="item.is_bulk"
              >
                  <option disabled value="">Seleziona un contenitore</option>
                  <option v-for="holder in holders" :key="holder.id" :value="holder.id">
                  {{ holder.name }}
                  </option>
              </select>

              <div v-if="!item.is_bulk && customSelectedHolder" class="flex gap-2 items-end">
                <div class="form-control">
                  <label class="label"><span class="label-text">Largh. (cm)</span></label>
                  <input v-model.number="item.custom_l_cm" type="number" min="0.01" step="0.01" class="input input-bordered w-28" />
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text">Prof. (cm)</span></label>
                  <input v-model.number="item.custom_w_cm" type="number" min="0.01" step="0.01" class="input input-bordered w-28" />
                </div>
                <div class="form-control">
                  <label class="label"><span class="label-text">Altezza (cm)</span></label>
                  <input v-model.number="item.custom_h_cm" type="number" min="0.01" step="0.01" class="input input-bordered w-28" />
                </div>
              </div>


            </div>



        
            <!-- Descrizione TEXT Input -->
            <input 
                v-model="item.description" 
                type="text" 
                class="input input-bordered flex-grow"
                placeholder="Descrizione"
            />
        
            <!-- Weight Number Input -->
            <input 
                v-model.number="item.weight_declared" 
                type="number" 
                step="0.1"
                class="input input-bordered w-24"
                placeholder="Peso [Kg]"
            />
        
            <!-- MAGAZZINO Select/Option -->
            <select v-model="item.warehouse_id" id="warehouse" class="select select-bordered">
                <option value="" disabled>Magazzino</option>
                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                {{ warehouse.denominazione }}
                </option>
            </select>

            <input 
              v-model="item.adr_hp" 
              :disabled="!is_selected_cer_dangerous"
              type="text" 
              class="input input-bordered flex w-24"
              placeholder="HP"
            />
        
            <!-- ADR TOGGLE Input -->
            <div class="flex items-center">
                <label class="label" for="adr">ADR</label>
                <input 
                v-model="item.adr" 
                id="adr" 
                type="checkbox" 
                class="toggle" 
                @click="toggleAdrFields(index, item.adr)" 
                />
            </div>
        
            <button @click="$emit('remove')" class="btn btn-error btn-circle">
                <font-awesome-icon :icon="['fas', 'xmark']" />
            </button>
        </div>
  
        <!-- Riga ADR -->
        <div :id="'adr-fields-' + index" class="hidden flex flex-row justify-end items-center w-full gap-2 mb-2">
            <div class="font-medium">
            Campi specifici per ADR
            <font-awesome-icon :icon="['fas', 'arrow-right']" />
            </div>

            <input 
            v-model="item.adr_onu_code" 
            type="text" 
            class="input input-bordered flex basis-32"
            placeholder="Cod. UN"
            />

            <label class="label" for="adr">ADR Totale</label>
            <input 
            v-model="item.adr_totale" 
            id="adr_totale" 
            type="checkbox" 
            class="toggle" 
            @click="checkAdrEsenzioni('adr_totale')" 
            />

            <label class="label" for="adr">Esenzione Totale</label>
            <input 
            v-model="item.adr_esenzione_totale" 
            id="adr_esenzione_totale" 
            type="checkbox" 
            class="toggle" 
            @click="checkAdrEsenzioni('adr_esenzione_totale')" 
            />

            <label class="label" for="adr">Esenzione Parziale</label>
            <input 
            v-model="item.adr_esenzione_parziale" 
            id="adr_esenzione_parziale" 
            type="checkbox" 
            class="toggle" 
            @click="checkAdrEsenzioni('adr_esenzione_parziale')" 
            />
        
          </div>


    <!-- Hint quando sfuso -->
    <div v-if="item.is_bulk" class="text-xs opacity-80 ml-auto">
      Modalità <strong>sfuso</strong> attiva: nessun contenitore attivo e quantità contenitori impostata a 0.
    </div>


    </div>
  </template>
  
  <script setup>
  import { ref, watch, computed } from "vue";
  import vSelect from "vue-select";
  import "vue-select/dist/vue-select.css";
  
  const props = defineProps({ 
    item: Object,
    index: Number,
    cerList: Array, 
    holders: Array,
    warehouses: Array,
  });

  // Default robusto per il nuovo campo
  if (props.item.is_bulk === undefined) {
    props.item.is_bulk = false;
  }
  
  const emit = defineEmits(['remove']);

  const customSelectedHolder = computed(() => {
    return props.holders.find(h => h.id === props.item.holder_id)?.is_custom
  })

  watch(() => props.item.holder_id, (newVal) => {
    if (!customSelectedHolder.value) {
      props.item.custom_l_cm = props.item.custom_w_cm = props.item.custom_h_cm = null;
    }
  });
  
  // Quando cambia il flag sfuso, normalizzo i campi holder
  watch(() => props.item.is_bulk, (isBulk) => {
    if (isBulk) {
      props.item.holder_id = null;
      props.item.holder_quantity = 0;
      // nel dubbio piallo anche i custom dimensions
      props.item.custom_l_cm = props.item.custom_w_cm = props.item.custom_h_cm = null;
    } else {
      // quando esco da sfuso, propongo default sensati
      if (!props.item.holder_quantity || props.item.holder_quantity < 1) {
        props.item.holder_quantity = 1;
      }
    }
  }, { immediate: true });


  const is_selected_cer_dangerous = ref(false);

  const getCerStyle = (cerId) => {
    if (!cerId){
      is_selected_cer_dangerous.value = false;
      return ""; // Return an empty class for undefined or null values
    } 
    const cer = props.cerList.find((c) => c.id === cerId);
    is_selected_cer_dangerous.value = Boolean(cer?.is_dangerous);
    return cer?.is_dangerous === 1 ? "cer-selected-dangerous" : "cer-selected-normal";
  };

  watch(is_selected_cer_dangerous, (newVal) => {
      if (!newVal) {
        props.item.adr_hp = '';
      }
  });
  
  const toggleAdrFields = (index, isAdrEnabled) => {
    const adrFields = document.getElementById(`adr-fields-${index}`);
    if (adrFields) {
      adrFields.classList.toggle('hidden', !isAdrEnabled);
    }
  };

  const checkAdrEsenzioni = (clickedElement) => {
    props.item.adr_totale = false;
    props.item.adr_esenzione_totale = false;
    props.item.adr_esenzione_parziale = false;
    switch(clickedElement){
      case 'adr_totale':
        props.item.adr_totale = true;
        break;
      case 'adr_esenzione_totale':
        props.item.adr_esenzione_totale = true;
        break;
      case 'adr_esenzione_parziale':
        props.item.adr_esenzione_parziale = true;
        break;
    }
  }
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
  