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
  
          <div class="flex flex-row justify-between items-center w-full">
              <div class="text-lg font-medium">
                  Gestione Materiali Scaricati in {{ props.journeyCargo.warehouse.denominazione }} da cassone {{ props.journeyCargo.truck_location === 'vehicle'? 'Motrice': 'Rimorchio' }}
              </div>
              <div>
                  <JourneyCargoHead :journeyCargo="props.journeyCargo"/>
              </div>
          </div>
  
      </div>
      
  
          <Box class="flex flex-row gap-8 mb-2 w-full">
            <div class="flex flex-row items-center gap-2 justify-evenly">
              <label for="utente" class="font-medium">Magazziniere:</label>
              {{ user.name }} 
              {{ user.surname }}
            </div>
  
            <div class="flex flex-row items-center gap-2 justify-evenly">
                <label for="has_ragno" class="font-medium">Uso del Ragno:</label>
                <input type="checkbox" id="has_ragno" v-model="form.has_ragno" class="toggle">
            </div>
  
            <div v-if="form.has_ragno" class="flex flex-row items-center gap-2 justify-evenly">
              <label for="ragnista_id" class="font-medium">Ragnista:</label>
              <select id="ragnista_id" v-model="form.ragnista_id" class="select select-bordered">
                <option value="" selected disabled>Seleziona un Ragnista</option>
                <option v-for="worker in props.warehouseWorkers.filter(worker => worker.is_ragnista)" :key="worker.id" :value="worker.id">
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
          </Box>
  
  
          <div>
              <WorkerItemRow
                  v-for="(item, index) in journeyCargo.items"
                  :key="item.id"
                  :item="item"
                  :index="index"
                  :warehouseManagers="props.warehouseManagers"
                  :warehouse="props.journeyCargo.warehouse"
                  class="mb-2"
                />
          </div>
  

  
      </div>
  
  </template>
  
  <script setup>
  import { computed, watch, ref, watchEffect , onMounted } from 'vue';
  import { Link, usePage, useForm } from '@inertiajs/vue3';
  import Box from '@/Components/UI/Box.vue';
  import ZeroPaddingId from '@/Components/UI/ZeroPaddingId.vue';
  import WorkerItemRow from '@/Pages/Warehouse/Components/WorkerItemRow.vue';
  import JourneyCargoHead from '@/Components/JourneyCargoHead.vue';
  import EmptyState from '@/Components/UI/EmptyState.vue';
  
  const props = defineProps({
      journeyCargo: Object,
      otherJourneyCargo: Object,
      orders: Object,
      warehouseManagers: Object,
      warehouseWorkers: Object,
  })
  
  const page = usePage();
  const user = computed(
      () => page.props.user
  )
  
  const form = useForm({
      has_ragno: false,
      ragnista_id: null,
      machinery_time_hh: 0,
      machinery_time_mm: 0,
      user_id: user ? user.id : null, // Fallback to null if user is not defined
      //items: [], // Start with an empty items array
      //holders: [],
  });
  
  
  
  </script>
  