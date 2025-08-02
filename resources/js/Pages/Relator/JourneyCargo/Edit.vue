<template>
    <div class="w-full">
  
      <!-- HEAD and BACK BUTTON -->
      <div class="mb-4 flex flex-row items-center gap-4">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.dashboard')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna a  Dashboard
        </Link>
        <div class="text-lg font-medium">
            Gestione cassoni per il viaggio #<ZeroPaddingId :id="props.journey.id "/>
        </div>
      </div>
  
      <form @submit.prevent="create">
  
        <div class="grid grid-cols-12 gap-2 w-full mb-4">
          
          <div class="col-span-9">
  
            <!-- LISTE DRAGGABILI MOTRICE E RIMORCHIO-->
            <div class="flex flex-row gap-2 w-full mt-4">
              <div class="flex-1">
                <div class="w-full flex flex-col gap-2 mb-4">
                  <div class="flex flex-row justify-center items-center gap-1 mb-2">
                    <font-awesome-icon :icon="['fas', 'truck']" class="text-4xl"/>
                    <span >Motrice</span>
                    <span class="font-semibold">{{ props.journey.vehicle.plate }}</span>
                    <span >con</span>
                    <span class="font-semibold">{{ props.journey.cargo_for_vehicle.description }}</span>                 
                  </div>
  
                  <div class="flex flex-row justify-center items-center gap-2">
  
                    <!-- CHECKBOX SCARICO/APPOGGIO -->
                    <div class="flex flex-col gap-1">
                      <div class="flex flex-row justify-start items-center gap-1">
                        <input v-model="isDownloadableTruck" id="isDownloadableTruck" type="checkbox" class="toggle" />
                        <label class="font-medium" for="isDownloadableTruck">Scarico</label>
                      </div>
                      <div class="flex flex-row justify-start items-center gap-1">
                        <input v-model="form.is_grounding_truck" id="is_grounding_truck" type="checkbox" class="toggle" />
                        <label class="font-medium" for="is_grounding_truck">Appoggio</label>
                      </div>
                    </div>
  
                    <!-- MAGAZZINO Select/Option -->
                    <select v-if="isDownloadableTruck" v-model="form.warehouse_id_truck" id="warehouse" class="select select-bordered">
                      <option value="" disabled>Seleziona Magazzino</option>
                      <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
                      {{ warehouse.denominazione }}
                      </option>
                    </select>
                    <span class="input-error" v-if="form.errors.warehouse_id_truck">{{ form.errors.warehouse_id_truck }}</span>
                    
                    <!-- ORDINE di SCARICO -->
                    <select v-if="differentDestinationWarehouse" v-model="computedOrderTruck" id="download_sequence_truck" class="select select-bordered">
                      <option value="" disabled>Ordine</option>
                      <option key="1" value=1>1°</option>
                      <option v-if="props.journey.trailer_id" key="2" value=2>2°</option>
                    </select>
                    <span class="input-error" v-if="form.errors.download_sequence_truck">{{ form.errors.download_sequence_truck }}</span>
  
                  </div>
                </div>
                <draggable 
                  v-model="listMotrice" 
                  group="items" 
                  @start="drag=true" 
                  @end="drag=false" 
                  
                  item-key="id"
                  class=" bg-primary p-1 pb-16 rounded-md"
                >
                  <template #item="{element, index}">
                    <DraggableItem
                      :element = "element"
                      :index = "index"
                      :isDownloadable = "isDownloadableTruck"
                      :warehouse_id="form.warehouse_id_truck"
                      :warehouses="props.warehouses"
                      @edit-item="handleEditItem"
                      @view-item="handleViewItem"
                      @view-map="handleViewMap"
                      @double-load-change="manageElementDoubleLoad"
                    />
                  </template>
                </draggable>
  
              </div>
              <div class="flex-1">
                <div class="flex flex-col gap-2 mb-4">
                  <div class="flex flex-row justify-center items-center gap-1 mb-2">
                    <font-awesome-icon :icon="['fas', 'trailer']" class="text-4xl"/>
                    <span >Rimorchio</span>
                    <span class="font-semibold">{{ props.journey.trailer.plate }}</span>
                    <span >con</span>
                    <span class="font-semibold">{{ props.journey.cargo_for_trailer.description }}</span> 
                  </div>
                  <div class="flex flex-row justify-center items-center gap-2">
  
                    <!-- CHECKBOX SCARICO/APPOGGIO -->
                    <div class="flex flex-col gap-1">
                      <div class="flex flex-row justify-start items-center gap-1">
                        <input v-model="isDownloadableTrailer" id="isDownloadableTrailer" type="checkbox" class="toggle" />
                        <label class="font-medium" for="isDownloadableTrailer">Scarico</label>
                      </div>
                      <div class="flex flex-row justify-start items-center gap-1">
                        <input v-model="form.is_grounding_trailer" id="is_grounding_trailer" type="checkbox" class="toggle" />
                        <label class="font-medium" for="is_grounding_trailer">Appoggio</label>
                      </div>
                    </div>
  
                    <!-- MAGAZZINO Select/Option -->
                    <select v-if="isDownloadableTrailer" v-model="form.warehouse_id_trailer" id="warehouse" class="select select-bordered">
                      <option value="" disabled>Seleziona Magazzino</option>
                      <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
                      {{ warehouse.denominazione }}
                      </option>
                    </select>
                    <span class="input-error" v-if="form.errors.warehouse_id_trailer">{{ form.errors.warehouse_id_trailer }}</span>
  
                    <!-- ORDINE di SCARICO -->
                    <select v-if="differentDestinationWarehouse" v-model="computedOrderTrailer" id="download_sequence_trailer" class="select select-bordered">
                      <option value="" disabled>Ordine</option>
                      <option key="1" value=1>1°</option>
                      <option v-if="props.journey.trailer_id" key="2" value=2>2°</option>
                    </select>
                    <span class="input-error" v-if="form.errors.download_sequence_trailer">{{ form.errors.download_sequence_trailer }}</span>
  
                  </div>
                </div>
                <draggable 
                  v-if="trailerEnabled"
                  v-model="listRimorchio" 
                  group="items" 
                  @start="drag=true" 
                  @end="drag=false" 
                  
                  item-key="id"
                  class=" bg-primary p-1 pb-16 rounded-md"
                >
                  <template #item="{element, index}">
                    <DraggableItem
                      :element = "element"
                      :index = "index"
                      :isDownloadable = "isDownloadableTrailer"
                      :warehouse_id="form.warehouse_id_trailer"
                      :warehouses="props.warehouses"
                      @edit-item="handleEditItem"
                      @view-item="handleViewItem"
                      @view-map="handleViewMap"
                      @double-load-change="manageElementDoubleLoad"
                    />
                  </template>
                </draggable>
              </div>
            </div>
  
            <!-- LISTE DRAGGABILE A RIEMPIMENTO -->
            <div class="w-full mb-8 mt-2">
              <h3 class="font-semibold">Carico a riempimento</h3>
              <draggable 
                v-model="listRiempimento" 
                v-if="trailerEnabled"
                group="items" 
                @start="drag=true" 
                @end="drag=false" 
                
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableItem
                    :element = "element"
                    :index = "index"
                    @edit-item="handleEditItem"
                    @view-item="handleViewItem"
                    @view-map="handleViewMap"
                  />
                </template>
              </draggable>
            </div>
  
          </div>
  
          <!-- FLOATING INFO OBJECT -->
          <div class="col-span-3">
            <Box>
              <div class="flex flex-row justify-between items-center gap-2 mb-1">
                <font-awesome-icon :icon="['fas', 'id-card']" class="text-2xl"/>              
                {{ props.journey.driver.name }} {{ props.journey.driver.surname }}
              </div>
  
              <div class="flex flex-row justify-between items-center gap-2 mb-1">
                <font-awesome-icon :icon="['fas', 'person-walking-arrow-right']" class="text-2xl"/>  
                Partenza: {{ dayjs(props.journey.dt_start).format('YYYY-MM-DD HH:mm') }}
              </div>
  
              <div class="flex flex-row justify-between items-center gap-2 mb-1">
                <font-awesome-icon :icon="['fas', 'person-walking-arrow-loop-left']" class="text-2xl"/>  
                Rientro previsto: {{ props.journey.dt_end? dayjs(props.journey.dt_end).format('YYYY-MM-DD HH:mm') : 'non valorizzato' }}
              </div>
  
              </Box>
  
            <div class="sticky top-24 shadow-md px-4 pb-4 mt-4 rounded-md flex flex-col gap-4 w-full">
              <div class="flex flex-col">
                <h3 class="font-semibold"> 
                      Ordini in Gestione
                </h3>
                <div v-for="order in props.orders">
                  {{order.id}} {{ order.customer.ragione_sociale }} {{ order.customer.id }} {{ order.index }}
                </div>
              </div>
  
              <div class="">
                <div class="flex flex-row justify-between items-center mb-2">
                  <div>
                    <h3 class="font-semibold"> 
                      Info Ordine
                    </h3>
                  </div>
                  <div v-if="viewMode != 'empty'">
                    <button
                      type="button" 
                      class="btn btn-primary btn-circle btn-sm"
                      @click.prevent="clearViewMode()"
                  >
                      <font-awesome-icon :icon="['fas', 'xmark']" class="text-lg"/>
                  </button>
                  </div>
  
                
                </div>
                <div v-if="viewMode === 'info'">
                  <OrderInfo
                    :item="clickedElement"
                  />
                </div>
                <Box v-else-if="viewMode === 'map'">
                  MAP
                  {{ clickedElement }}
                </Box>
                <Box v-else-if="viewMode === 'edit'">
                  Edit andrà aperto in altra finestra
                  {{ clickedElement }}
                </Box>
                <EmptyState v-else>
                  Nssun ordine selezionato
                </EmptyState>
  
              </div>
  
              <div class="mb-2">
                <button 
                  type="submit" 
                  class="btn btn-primary"
                >
                  <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-xl"/>
                  Salva Viaggio
                </button>
              </div>
  
            </div>
  
          </div>
  
        </div>
  
      </form>
    </div>
  
  
  
  </template>
      
  <script setup>
      import { computed, watch, ref, onMounted, onUnmounted } from 'vue';
      import { useStore } from 'vuex';
      import { Link, useForm, usePage } from '@inertiajs/vue3'
      import dayjs from 'dayjs';
      import VueDatePicker from '@vuepic/vue-datepicker';
      import { format } from 'date-fns';
      import { it } from 'date-fns/locale';
      import '@vuepic/vue-datepicker/dist/main.css';
      import "vue-select/dist/vue-select.css";
      import vSelect from "vue-select";
      import { getIconForSite } from '@/Composables/getIconForSite';
      import draggable from 'vuedraggable'
      import Box from '@/Components/UI/Box.vue';
      import DraggableItem from './Components/DraggableItem.vue';
      import EmptyState from '@/Components/UI/EmptyState.vue';
      import OrderInfo from './Components/OrderInfo.vue';
      import eventBus from '@/eventBus';
  import ZeroPaddingId from '@/Components/UI/ZeroPaddingId.vue';
import ListingAddress from '@/Components/ListingAddress.vue';
  
      const props = defineProps({
        journey: Object,
        warehouses: Array,
        orders: Array,
        journeyCargos: Object,
        currentUser: Object
      })
      
      const page = usePage()
      const store = useStore();
      
      const user = computed(
        () => page.props.user
      )

        const JourneyCargoVehicle = computed(() => {
            return props.journeyCargos.find(item => item.truck_location === 'vehicle') || null;
        });
        const JourneyCargoTrailer = computed(() => {
            return props.journeyCargos.find(item => item.truck_location === 'trailer') || null;
        });
      
      const form = useForm({
        logistic_id: '',
        journey_id: props.journey.id,
        warehouse_id_truck: JourneyCargoVehicle.value.warehouse_id,
        is_grounding_truck: Boolean(JourneyCargoVehicle.value.is_grounding),
        download_sequence_truck: JourneyCargoVehicle.value.download_sequence,
        warehouse_id_trailer: JourneyCargoTrailer.value.warehouse_id,
        is_grounding_trailer: Boolean(JourneyCargoTrailer.value.is_grounding),
        download_sequence_trailer: JourneyCargoTrailer.value.download_sequence,
        items_truck: [],
        items_trailer: [],
        items_fullfill: [], 
      })
      
      // Computed property for the inverse toggle
      const isDownloadableTruck = computed({
        get() {
          return !form.is_grounding_truck;
        },
        set(value) {
          form.is_grounding_truck = !value;
        },
      });
        // Computed property for the inverse toggle
        const isDownloadableTrailer = computed({
        get() {
          return !form.is_grounding_trailer;
        },
        set(value) {
          form.is_grounding_trailer = !value;
        },
      });
  
      // The truck computed property gets/sets the truck field and updates the trailer field accordingly.
      const computedOrderTruck = computed({
        get() {
          return form.download_sequence_truck;
        },
        set(newValue) {
          form.download_sequence_truck = newValue;
          // Automatically assign the complementary value to the trailer field.
          form.download_sequence_trailer = Number(newValue) === 1 ? 2 : 1;
        },
      });
  
      // Similarly, the trailer computed property does the inverse.
      const computedOrderTrailer = computed({
        get() {
          return form.download_sequence_trailer;
        },
        set(newValue) {
          form.download_sequence_trailer = newValue;
          form.download_sequence_truck = Number(newValue) === 1 ? 2 : 1;
        },
      });
  
      // Evaluate if it is the case in which the truck and trailer are in different warehouses.
      const differentDestinationWarehouse = computed(() => {
        return form.warehouse_id_trailer != form.warehouse_id_truck;
      });
  
      const create = () => {
        // Map each list to only send IDs
        //form.items_truck    = listMotrice.value.map(item => item.id);
        //form.items_trailer  = listRimorchio.value.map(item => item.id);
        //form.items_fullfill = listRiempimento.value.map(item => item.id);
        form.items_truck    = listMotrice.value;
        form.items_trailer  = listRimorchio.value;
        form.items_fullfill = listRiempimento.value;
        form.logistic_id = user.value.id;
        form.post(route('relator.journeyCargo.store'));
      }
  
      const listOrdini       = ref(props.orders);
      const listMotrice      = ref([]);
      const listRimorchio    = ref([]);
      const listRiempimento  = ref([]);

      // Function to distribute order items into the proper lists.
      const distributeOrderItems = () => {

        const JourneyCargoVehicle = props.journeyCargos.find(item => item.truck_location === 'vehicle') || null;
        const JourneyCargoTrailer = props.journeyCargos.find(item => item.truck_location === 'trailer') || null;
         

        // Clear the lists first
        listMotrice.value = JourneyCargoVehicle.items;
        listRimorchio.value = JourneyCargoTrailer.items;
        listRiempimento.value = [];
  
        // Iterate over each order and distribute items based on truck_location.
        props.orders.forEach(order => {
            if(order.items.length > 0){
                // Check if the order has items and distribute them accordingly.
                order.items.forEach(item => {
                  /*
                  console.log(
                  'ordine', order.id, 'item', item.id,
                  'ESISTE M =' , listMotrice.value.find(function(listItem) {return listItem.id === item.id;})
                  )
                  console.log(
                  'ordine', order.id, 'item', item.id,
                  'ESISTE R =' , listRimorchio.value.find(function(listItem) {return listItem.id === item.id;})
                  )
                  */
                  if( 
                    !listMotrice.value.find(function(listItem) {return listItem.id === item.id;})
                    &&
                    !listRimorchio.value.find(function(listItem) {return listItem.id === item.id;})
                  ){
                    listRiempimento.value.push(item);
                  }

/*
VECCHIA LOGICA
                    if (item.journey_cargo_id === JourneyCargoVehicle.value.id) {
                        listMotrice.value.push(item);
                    } else if (item.journey_cargo_id === JourneyCargoTrailer.value.id) {
                        listRimorchio.value.push(item);
                    } else {
                        listRiempimento.value.push(item);
                    }
                        */
                });
            }
        });
      };
  
      // Distribute the items when the component mounts.
      onMounted(() => {
        distributeOrderItems();
      });
    
      const manageElementDoubleLoad = (element_id, is_double_load, warehouse_download_id) => {
        console.log('manageElementDoubleLoad PARAMS', element_id, is_double_load, warehouse_download_id);
        // Find the element in the listMotrice or listRimorchio based on the element_id
        const element = listMotrice.value.find(item => item.id === element_id) || listRimorchio.value.find(item => item.id === element_id);
        console.log('manageElementDoubleLoad ELEMENT', element);
        if (!element) return; // Element not found in either list
        element.pivot.is_double_load = is_double_load;
        element.pivot.warehouse_download_id = warehouse_download_id;
      };

      const viewMode = ref('empty');
      const clickedElement = ref();
  
      const handleEditItem = (element) => {
        viewMode.value = 'edit';
      }
  
      const handleViewItem = (element) => {
        viewMode.value = 'info';
        clickedElement.value = element
      }
  
      const handleViewMap = (element) => {
        viewMode.value = 'map';
        clickedElement.value = element
      }
  
      const clearViewMode = () => {
        viewMode.value = 'empty';
      }
  
      const trailerEnabled          = ref(props.journey.trailer_id? true: false);
  
  // Handlers for moving items
  const handleSetToTruck = (item) => {
    moveOrder(item, listMotrice);
  };
  
  const handleSetToTrailer = (item) => {
    moveOrder(item, listRimorchio);
  };
  
  const handleSetToRiempimento = (item) => {
    moveOrder(item, listRiempimento);
  };
  
  const handleSetToOrders = (item) => {
    moveOrder(item, listOrdini);
  };
  
  // Helper function to move an item between lists
  const moveOrder = (item, targetList) => {
   
    if ( targetList != listMotrice &&
        listMotrice.value.findIndex( (i) => i.id === item.id) !== -1
      ) {
      const index = listMotrice.value.findIndex((i) => i.id === item.id);
      const [removed] = listMotrice.value.splice(index, 1); // Remove from source
      targetList.value.push(removed); // Add to target
    }
    if (targetList != listRimorchio &&
        listRimorchio.value.findIndex((i) => i.id === item.id) !== -1
      ) {
      const index = listRimorchio.value.findIndex((i) => i.id === item.id);
      const [removed] = listRimorchio.value.splice(index, 1); // Remove from source
      targetList.value.push(removed); // Add to target
    }
    if (targetList != listRiempimento &&
        listRiempimento.value.findIndex((i) => i.id === item.id) !== -1
      ) {
      const index = listRiempimento.value.findIndex((i) => i.id === item.id);
      const [removed] = listRiempimento.value.splice(index, 1); // Remove from source
      targetList.value.push(removed); // Add to target
    }
    if (targetList != listOrdini &&
        listOrdini.value.findIndex((i) => i.id === item.id) !== -1
      ) {
      const index = listOrdini.value.findIndex((i) => i.id === item.id);
      const [removed] = listOrdini.value.splice(index, 1); // Remove from source
      targetList.value.push(removed); // Add to target
    }
  
  };
  
  // Set up event listeners on mount and clean up on unmount
  onMounted(() => {
    eventBus.on('setOrderToTruckList', handleSetToTruck);
    eventBus.on('setOrderToTrailerList', handleSetToTrailer);
    eventBus.on('setOrderToRiempimentoList', handleSetToRiempimento);
  //  eventBus.on('setOrderToOrdersList', handleSetToOrders);
  });
  
  onUnmounted(() => {
    eventBus.off('setOrderToTruckList', handleSetToTruck);
    eventBus.off('setOrderToTrailerList', handleSetToTrailer);
    eventBus.off('setOrderToRiempimentoList', handleSetToRiempimento);
  //  eventBus.off('setOrderToOrdersList', handleSetToOrders);
  });
  
      </script>
      
      <style scoped>
      .button {
        margin-top: 35px;
      }
      .handle {
        float: left;
        padding-top: 8px;
        padding-bottom: 8px;
      }
      
      .close {
        float: right;
        padding-top: 8px;
        padding-bottom: 8px;
      }
       
      .text {
        margin: 20px;
      }
  
      .masonry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        grid-auto-rows: auto;
        gap: 4px;
      }
  
      .masonry-item {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      }
  
      </style>