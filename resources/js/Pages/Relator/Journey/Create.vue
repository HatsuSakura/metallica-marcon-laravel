<template>


<button 
    type="button"
    class="btn btn-primary btn-circle btn-toggle-map"
    :class="extendedMapMode? 'btn-error' : 'btn-success'"
    @click.prevent="toggleExtendedMapMode()"
>
  <font-awesome-icon 
    :icon="['fas', 'location-pin-lock']" 
    class="text-2xl"
  />
 </button>

<!-- MAIN CONTAINER -->
<div class="flex flex-row pt-24" :class="extendedMapMode? 'full-width' : 'container mx-auto'">
  <!-- LIST HALF -->
  <div :class="extendedMapMode? 'w-1/2 px-8 ' : 'w-full'">
    <form @submit.prevent="create">

      <div class="grid grid-cols-12 justify-stretch gap-2 w-full mb-4">


        
        <div class="col-span-9">

          <!-- TESTATA TOTALI -->
          <div class="flex flex-row justify-items-stretch gap-2 w-full">
            <!-- MOTRICE -->
            <Box class="flex-1">
              <div class="w-full">
                <div class="flex flex-row justify-center items-center">
                  Motrice &nbsp;
                  <font-awesome-icon :icon="['fas', 'truck']" class="text-4xl"/>
                </div>
              </div>

              <div class="flex flex-row items-center gap-4 mt-2 pr-2">
                <select 
                  v-model="form.vehicle_id"
                  required
                  id="vehicle_id" 
                  class="select select-bordered w-full max-w-xs"
                  @change="setPreferredTrailerAndCargo"
                >
                  <option disabled value="">Seleziona la motrice</option>
                  <option v-for="vehicle in props.vehicles" :key="vehicle.id" :value="vehicle.id">
                    {{ vehicle.plate }} - {{ vehicle.name }} <!-- Assuming user model has a 'name' field -->
                  </option>
                </select>

                <select 
                  v-model="form.cargo_for_vehicle_id"
                  required
                  id="cargo_for_vehicle_id" 
                  class="select select-bordered w-full max-w-xs"
                  :disabled="!trailerEnabled"
                  @change="checkCargo"
                >
                  <option disabled value="">Nessun cassone</option>
                  <option v-for="cargo in props.cargos" :key="cargo.id" :value="cargo.id">
                    {{ cargo.name }} <!-- Assuming user model has a 'name' field -->
                  </option>
                </select>
              </div>


              <div class="grid grid-flow-col justify-stretch gap-2 w-full mt-2">
                <div class="flex items-center">
                  <font-awesome-icon :icon="['fas', 'truck']" class="text-2xl"/>
                </div>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ spaziCasseMotrice }}
                    </div>
                    <div>
                      Casse
                    </div>
                  </div>
                </Box>        
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ spaziBancaleMotrice }}
                    </div>
                    <div>
                      Bancali
                    </div>
                  </div>
                </Box>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ capacitaCaricoMotrice /1000 }} t.
                    </div>
                    <div>
                      Peso
                    </div>
                  </div>
                </Box>
              </div>

              <div class="grid grid-flow-col justify-stretch gap-2 w-full mt-2">
                <div class="flex items-center">
                  <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-2xl"/>
                </div>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniCasseMotrice }}
                    </div>
                    <div>
                      Casse
                    </div>
                  </div>
                </Box>        
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniBancaleMotrice }}
                    </div>
                    <div>
                      Bancali
                    </div>
                  </div>
                </Box>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniCaricoMotrice / 1000 }} t.
                    </div>
                    <div>
                      Peso
                    </div>
                  </div>
                </Box>
              </div>

            </Box>

            <!-- RIMORCHIO -->
            <Box class="flex-1">
              <div class="w-full">
                <div class="flex flex-row justify-center items-center">
                  Rimorchio &nbsp;
                  <font-awesome-icon :icon="['fas', 'trailer']" class="text-4xl"/>
                </div>
              </div>

              <div class="flex flex-row items-center gap-4 mt-2 pr-2">
                <select 
                  v-model="form.trailer_id"
                  id="trailer_id" 
                  class="select select-bordered w-full max-w-xs"
                  :disabled="!trailerEnabled"
                  @change="checkCargo"
                >
                  <option value="">Nessun rimorchio</option>
                  <option v-for="trailer in props.trailers" :key="trailer.id" :value="trailer.id">
                    {{ trailer.plate }} - {{ trailer.name }} <!-- Assuming user model has a 'name' field -->
                  </option>
                </select>

                <select 
                  v-model="form.cargo_for_trailer_id"
                  :required="form.trailer_id? true: false"
                  id="cargo_for_trailer_id" 
                  class="select select-bordered w-full max-w-xs"
                  :disabled="!trailerEnabled"
                  @change="checkCargo"
                >
                  <option disabled value="">Nessun cassone</option>
                  <option v-for="cargo in props.cargos" :key="cargo.id" :value="cargo.id">
                    {{ cargo.name }} <!-- Assuming user model has a 'name' field -->
                  </option>
                </select>
              </div>

              <div class="grid grid-flow-col justify-stretch gap-2 w-full mt-2">
              <div class="flex items-center">
                <font-awesome-icon :icon="['fas', 'trailer']" class="text-2xl"/>
              </div>
              <Box padding="p-1">
                <div class="flex flex-col items-center">
                  <div class="text-2xl font-medium">
                    {{ spaziCasseRimorchio }}
                  </div>
                  <div>
                    Casse
                  </div>
                </div>
              </Box>        
              <Box padding="p-1">
                <div class="flex flex-col items-center">
                  <div class="text-2xl font-medium">
                    {{ spaziBancaleRimorchio }}
                  </div>
                  <div>
                    Bancali
                  </div>
                </div>
              </Box>
              <Box padding="p-1">
                <div class="flex flex-col items-center">
                  <div class="text-2xl font-medium">
                    {{ capacitaCaricoRimorchio/1000 }} t.
                  </div>
                  <div>
                    Peso
                  </div>
                </div>
              </Box>
              </div>

              <div class="grid grid-flow-col justify-stretch gap-2 w-full mt-2">
                <div class="flex items-center">
                  <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-2xl"/>
                </div>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniCasseRimorchio }}
                    </div>
                    <div>
                      Casse
                    </div>
                  </div>
                </Box>        
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniBancaleRimorchio }}
                    </div>
                    <div>
                      Bancali
                    </div>
                  </div>
                </Box>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniCaricoRimorchio/1000 }} t.
                    </div>
                    <div>
                      Peso
                    </div>
                  </div>
                </Box>
              </div>

            </Box>
          </div>

          <!-- LISTE DRAGGABILI -->
          <div class="flex flex-row justify-items-stretch gap-2 w-full mt-4">
            <div class="flex-1">
              
              <h3 class="font-semibold">Carico Motrice</h3>
              <draggable 
                v-model="listMotrice" 
                group="orders" 
                @start="drag=true" 
                @end="drag=false" 
                @change="calculateTotalLoad"
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableObject
                    :element = "element"
                    :index = "index"
                    @edit-item="handleEditItem"
                    @view-item="handleViewItem"
                    @view-map="handleViewMap"
                  />
                </template>
              </draggable>

            </div>
            <div class="flex-1">
              <h3 class="font-semibold">Carico Rimorchio</h3>
              <draggable 
                v-if="trailerEnabled"
                v-model="listRimorchio" 
                group="orders" 
                @start="drag=true" 
                @end="drag=false" 
                @change="calculateTotalLoad"
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableObject
                    :element = "element"
                    :index = "index"
                    @edit-item="handleEditItem"
                    @view-item="handleViewItem"
                    @view-map="handleViewMap"
                  />
                </template>
              </draggable>
            </div>
            <div class="flex-1">
              <h3 class="font-semibold">Carico a riempimento</h3>

              <draggable 
                v-model="listRiempimento" 
                v-if="trailerEnabled"
                group="orders" 
                @start="drag=true" 
                @end="drag=false" 
                @change="calculateTotalLoad"
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableObject
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

          <!-- MASONRY ORDINI -->
          <div class="w-full mb-8 mt-4">
          <h3 class="font-semibold">Ordini Aperti</h3>
            <draggable
              :list="listOrdini"
              group="orders"
              @start="drag=true" 
              @end="drag=false" 
              tag="div"
              class="masonry-grid bg-primary p-1 pb-16 rounded-md"
              itemKey="name"          
            >
              <template #item="{element, index}">
                <div class="masonry-item">
                <DraggableObject
                  :element = "element"
                  :index = "index"
                  @edit-item="handleEditItem"
                  @view-item="handleViewItem"
                  @view-map="handleViewMap"
                />
                </div>
              </template>
            </draggable>
          </div>

        </div>

        <!-- FLOATING INFO OBJECT -->
        <div class="col-span-3">
          <Box>
            <div class="flex flex-row justify-between items-center gap-2 mb-1">
              <font-awesome-icon :icon="['fas', 'id-card']" class="text-2xl"/>              
              <select 
                v-model="form.driver_id" 
                required
                id="driver_id" 
                class="select select-bordered w-full max-w-xs"
                @change=""
              >
                <option disabled value="">Seleziona l'autista</option>
                <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
                  {{ driver.name }}
                </option>
              </select>
            </div>

            <div class="flex flex-row justify-between items-center gap-2 mb-1">
              <font-awesome-icon :icon="['fas', 'person-walking-arrow-right']" class="text-2xl"/>  
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
              <div class="input-error" v-if="form.errors.dt_start">
                {{ form.errors.dt_start }}
              </div>
            </div>

            <div class="flex flex-row justify-between items-center gap-2 mb-1">
              <font-awesome-icon :icon="['fas', 'person-walking-arrow-loop-left']" class="text-2xl"/>  
              <VueDatePicker 
                v-model="form.dt_end" 
                locale="it"
                format="dd/MM/yyyy HH:mm"
                required
                placeholder="Data Ritorno"
                :range=false 
                time-picker-inline
                auto-apply
                minutes-increment="5"
                minutes-grid-increment="5"
                closeOnScroll="false"
              ></VueDatePicker>
              <div class="input-error" v-if="form.errors.dt_end">
                {{ form.errors.dt_end }}
              </div>
            </div>

            </Box>

          <div class="sticky top-24 shadow-md px-4 pb-4 mt-4 rounded-md flex flex-col gap-4 w-full">
            <!-- TOTALE VIAGGIO -->
            <div>
              <div class="w-full">
                <div class="flex flex-row justify-center items-center">
                  Tot. Viaggio &nbsp;
                  <font-awesome-icon :icon="['fas', 'route']" class="text-4xl"/>
                </div>
              </div>

              <div class="grid grid-flow-col justify-stretch gap-2 w-full mt-2">
                <div class="flex items-center">
                  <font-awesome-icon :icon="['fas', 'route']" class="text-2xl"/>
                </div>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ spaziCasseMotrice + spaziCasseRimorchio }}
                    </div>
                    <div>
                      Casse
                    </div>
                  </div>
                </Box>        
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ spaziBancaleMotrice + spaziBancaleRimorchio }}
                    </div>
                    <div>
                      Bancali
                    </div>
                  </div>
                </Box>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ (capacitaCaricoMotrice + capacitaCaricoRimorchio)/1000 }} t.
                    </div>
                    <div>
                      Peso
                    </div>
                  </div>
                </Box>
              </div>

              <div class="grid grid-flow-col justify-stretch gap-2 w-full mt-2">
                <div class="flex items-center">
                  <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-2xl"/>
                </div>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento }}
                    </div>
                    <div>
                      Casse
                    </div>
                  </div>
                </Box>        
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento }}
                    </div>
                    <div>
                      Bancali
                    </div>
                  </div>
                </Box>
                <Box padding="p-1">
                  <div class="flex flex-col items-center">
                    <div class="text-2xl font-medium">
                      {{ (ordiniCaricoMotrice + ordiniCaricoRimorchio+ ordiniCaricoRiempimento)/1000 }} t.
                    </div>
                    <div>
                      Peso
                    </div>
                  </div>
                </Box>
              </div>

            </div>

            <!-- TOTALE VIAGGIO -->
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
                  :order="clickedElement"
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
                Crea Viaggio
              </button>
            </div>

          </div>

        </div>

      </div>

    </form>
  </div>
  <!-- END-OF LIST HALF -->

  <!-- MAP HALF -->
  <div v-if="extendedMapMode" :class="extendedMapMode? 'w-1/2 sticky top-24' : 'w-0'">
    <JourneyMap 
      :orders="props.orders"
      v-model:listMotrice = "listMotrice"
      v-model:listRimorchio = "listRimorchio"
      v-model:listRiempimento = "listRiempimento"
    >

    </JourneyMap>
  </div>
  <!-- END-OF MAP HALF -->

</div>

<!--
    <div>
      <div>
        MOTRICE
      </div>
      <div>
        {{ listMotrice }}
      </div>
      <div>
        ORDINI
      </div>
      <div>
        {{ listOrdini }}
      </div>
    </div>
-->


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
    import DraggableObject from './Components/DraggableObject.vue';
    import EmptyState from '@/Components/UI/EmptyState.vue';
    import OrderInfo from './Components/OrderInfo.vue';
    import JourneyMap from './Components/JourneyMap.vue';
    import eventBus from '@/eventBus';

    const props = defineProps({
      vehicles: Array,
      trailers: Array,
      cargos: Array,
      holders: Array,
      drivers: Array,
      warehouses: Array,
      orders: Array,
      currentUser: Object
    })
    
    const page = usePage()
    const store = useStore();
    const currentSite = computed(() => store.state.currentSite || null );
    
    const user = computed(
      () => page.props.user
    )
    
    const extendedMapMode = ref(false);
    const toggleExtendedMapMode = () => {
      extendedMapMode.value = !extendedMapMode.value;
    }
    const currentDate = computed(
      () => new Date().toLocaleString()
    )

    const form = useForm({
      dt_start: '',
      dt_end: '',
      vehicle_id: '',
      cargo_for_vehicle_id: '',
      trailer_id: '',
      cargo_for_trailer_id: '',
      driver_id: '',
      logistic_id: '',
      orders_truck: [],
      orders_trailer: [],
      orders_fullfill: [], 
    })
    
    const create = () => {
      // Before submitting, format the date correctly
      form.dt_start = dayjs(form.dt_start).format('YYYY-MM-DD HH:mm:ss');
      form.dt_end   = dayjs(form.dt_end  ).format('YYYY-MM-DD HH:mm:ss');
      // Map each list to only send IDs
      form.orders_truck    = listMotrice.value.map(order => order.id);
      form.orders_trailer  = listRimorchio.value.map(order => order.id);
      form.orders_fullfill = listRiempimento.value.map(order => order.id);
      form.logistic_id = user.value.id;
      form.post(route('relator.journey.store'));
    }

    const listMotrice      = ref([]);
    const listRimorchio    = ref([]);
    const listRiempimento  = ref([]);
    const listOrdini       = ref(props.orders);

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

    const calculateTotalLoad = () =>{
      ordiniCasseMotrice.value= 0
      ordiniBancaleMotrice.value= 0
      ordiniCaricoMotrice.value= 0
      listMotrice.value.forEach(order => {
        (order.items).forEach(item => {
          ordiniCaricoMotrice.value += item.weight_declared;
        })
      });

      ordiniCasseRimorchio.value= 0
      ordiniBancaleRimorchio.value= 0
      ordiniCaricoRimorchio.value= 0
      listRimorchio.value.forEach(order => {
        (order.items).forEach(item => {
          ordiniCaricoRimorchio.value += item.weight_declared;
        })
      });
      
      ordiniCasseRiempimento.value= 0
      ordiniBancaleRiempimento.value= 0
      ordiniCaricoRiempimento.value= 0
      listRiempimento.value.forEach(order => {
        (order.items).forEach(item => {
          ordiniCaricoRiempimento.value += item.weight_declared;
        })
      });
      
    }
 
    const trailerEnabled          = ref(false);

    const spaziCasseMotrice       = ref(0);
    const spaziBancaleMotrice     = ref(0);
    const capacitaCaricoMotrice   = ref(0);
    const ordiniCasseMotrice      = ref(0);
    const ordiniBancaleMotrice    = ref(0);
    const ordiniCaricoMotrice     = ref(0);

    const spaziCasseRimorchio     = ref(0);
    const spaziBancaleRimorchio   = ref(0);
    const capacitaCaricoRimorchio = ref(0);
    const ordiniCasseRimorchio    = ref(0);
    const ordiniBancaleRimorchio  = ref(0);
    const ordiniCaricoRimorchio   = ref(0);
        
    const ordiniCasseRiempimento    = ref(0);
    const ordiniBancaleRiempimento  = ref(0);
    const ordiniCaricoRiempimento   = ref(0);
     
    const setPreferredTrailerAndCargo = () => {
      const selectedVehicle = props.vehicles.find(vehicle => vehicle.id === form.vehicle_id)
      capacitaCaricoMotrice.value = selectedVehicle.load_capacity;
      capacitaCaricoRimorchio.value = 0;
      // Verifico se può avere un rimorchio (NON POSSONO Furgoni e Camion con Sponda)
      if (selectedVehicle.has_trailer){
        form.trailer_id = selectedVehicle.trailer_id? selectedVehicle.trailer_id : '';
        trailerEnabled.value = true;
        const selectedTrailer = props.trailers.find(trailer => trailer.id === form.trailer_id );
        capacitaCaricoRimorchio.value = selectedTrailer.load_capacity;
      }
      else{
        form.trailer_id = '';
        trailerEnabled.value = false;
      }

      // Verifico se SPONDA o FURGONE e setto il corretto CASSONE PREDEFINITO
      if (selectedVehicle.type === 'sponda'){
        form.cargo_for_vehicle_id = props.cargos.find(cargo => cargo.name === 'Sponda').id;
      }
      else if (selectedVehicle.type === 'furgone') {
        form.cargo_for_vehicle_id = props.cargos.find(cargo => cargo.name === 'Furgone').id;
      }
      else{
        form.cargo_for_vehicle_id = ''
      }

      checkCargo({target:'internal'});
    }

    const checkCargo = (evt) => {
      //console.log(evt.target.id);
      const fieldName = evt.target.id;
      const selectedVehicle = props.vehicles.find(vehicle => vehicle.id === form.vehicle_id);
      const selectedVehicleCargo = ref(props.cargos.find(cargo => cargo.id === form.cargo_for_vehicle_id));
      const selectedTrailerCargo = ref(props.cargos.find(cargo => cargo.id === form.cargo_for_trailer_id));

      // Gestisco il caso in cui si sia modificato il rimorchio "TRAILER"
      if (fieldName === 'trailer_id') {
        if (form.trailer_id === '' ){
          selectedTrailerCargo.value = null;
          form.cargo_for_trailer_id = '';
          capacitaCaricoRimorchio.value = 0;
        }
        else{
          const selectedTrailer = props.trailers.find(trailer => trailer.id === form.trailer_id );
          capacitaCaricoRimorchio.value = selectedTrailer.load_capacity;
        }
      }
      


      if (selectedVehicleCargo.value && selectedTrailerCargo.value){
        if (selectedVehicleCargo.value.is_long && selectedTrailerCargo.value.is_long){
          alert('ATTENZIONE: autotreno con combinazione di due cassoni LUNGHI')
          // Reset the correct form field based on the event source
          if (fieldName === 'cargo_for_vehicle_id') {
            selectedVehicleCargo.value = null;
            form.cargo_for_vehicle_id = ''; // Reset the form value
          } 
          else if (fieldName === 'cargo_for_trailer_id') {
            selectedTrailerCargo.value = null;
            form.cargo_for_trailer_id = ''; // Reset the form value
          }

          // Also reset the select element visually
          evt.target.value = '';
        }
      }

      if (selectedVehicle.has_trailer && selectedVehicleCargo.value && (
          (selectedVehicleCargo.value.name === 'Sponda') || (selectedVehicleCargo.value.name === 'Furgone')
        )
      ){
        alert('Opzione "' + selectedVehicleCargo.value.name + '" selezionabile solo per motrice di tipo ' + selectedVehicleCargo.value.name);
        selectedVehicleCargo.value = null;
        form.cargo_for_vehicle_id = ''; // Reset the form value
      }

      spaziCasseMotrice.value     = 0;
      spaziBancaleMotrice.value   = 0;
      if(selectedVehicleCargo.value) {
        spaziCasseMotrice.value   = selectedVehicleCargo.value.spazi_casse;
        spaziBancaleMotrice.value = selectedVehicleCargo.value.spazi_bancale;
      }

      spaziCasseRimorchio.value   = 0;
      spaziBancaleRimorchio.value = 0;
      if(selectedTrailerCargo.value) {
        spaziCasseRimorchio.value   = selectedTrailerCargo.value.spazi_casse;
        spaziBancaleRimorchio.value = selectedTrailerCargo.value.spazi_bancale;
      }
    }


// Handlers for moving orders
const handleSetToTruck = (order) => {
  moveOrder(order, listMotrice);
};

const handleSetToTrailer = (order) => {
  moveOrder(order, listRimorchio);
};

const handleSetToRiempimento = (order) => {
  moveOrder(order, listRiempimento);
};

const handleSetToOrders = (order) => {
  moveOrder(order, listOrdini);
};

// Helper function to move an order between lists
const moveOrder = (order, targetList) => {
 
  if ( targetList != listMotrice &&
      listMotrice.value.findIndex( (o) => o.id === order.id) !== -1
    ) {
    const index = listMotrice.value.findIndex((o) => o.id === order.id);
    const [removed] = listMotrice.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }
  if (targetList != listRimorchio &&
      listRimorchio.value.findIndex((o) => o.id === order.id) !== -1
    ) {
    const index = listRimorchio.value.findIndex((o) => o.id === order.id);
    const [removed] = listRimorchio.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }
  if (targetList != listRiempimento &&
      listRiempimento.value.findIndex((o) => o.id === order.id) !== -1
    ) {
    const index = listRiempimento.value.findIndex((o) => o.id === order.id);
    const [removed] = listRiempimento.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }
  if (targetList != listOrdini &&
      listOrdini.value.findIndex((o) => o.id === order.id) !== -1
    ) {
    const index = listOrdini.value.findIndex((o) => o.id === order.id);
    const [removed] = listOrdini.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }

  calculateTotalLoad();

};

const manageDate = () => {
  if (form.dt_start) {
        const startDate = dayjs(form.dt_start);
        const endDate = startDate.add(1, 'hour'); // Aggiunge un'ora di default
        form.dt_end = endDate.toDate();
  }
}

// Set up event listeners on mount and clean up on unmount
onMounted(() => {
  eventBus.on('setOrderToTruckList', handleSetToTruck);
  eventBus.on('setOrderToTrailerList', handleSetToTrailer);
  eventBus.on('setOrderToRiempimentoList', handleSetToRiempimento);
  eventBus.on('setOrderToOrdersList', handleSetToOrders);
});

onUnmounted(() => {
  eventBus.off('setOrderToTruckList', handleSetToTruck);
  eventBus.off('setOrderToTrailerList', handleSetToTrailer);
  eventBus.off('setOrderToRiempimentoList', handleSetToRiempimento);
  eventBus.off('setOrderToOrdersList', handleSetToOrders);
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
    
    input {
      display: inline-block;
      width: 50%;
    }
    
    .text {
      margin: 20px;
    }

    .btn-toggle-map{
      position: fixed;
      left: 32px;
      top: 74px;
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