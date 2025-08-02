<template>   
    <section>

      <!-- HEAD and BACK BUTTON -->
      <div class="mb-4 flex flex-row items-center gap-4">
        <!--
              <Link
              class="btn btn-ghost" 
              :href="route('relator.dashboard')"
          >
              <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
              Torna a  Dashboard
          </Link>
        -->
  
          <div class="flex flex-row justify-between items-center w-full">
              <div class="text-lg font-medium">
                  Gestione Materiali Scaricati
              </div>
          </div>
  
      </div>

        <div class="col-span-10 flex flex-col justify-between gap-2 mb-2">
            <div v-for="journeyCargo in props.journeyCargos" class="flex flex-col gap-2">

                <div class="flex flex-row justify-between gap-2">
                    <JourneyCargoHead :journeyCargo="journeyCargo"/>


                    <div>
                        <div>
                        In gestione a: 
                        {{ journeyCargo.warehouse_chief? journeyCargo.warehouse_chief.name : 'Non ancora gestito' }}
                        {{ journeyCargo.warehouse_chief? journeyCargo.warehouse_chief.surname : '' }}
                        </div>

                        <div>
                            Uso RAGNO: 
                            <span v-if="journeyCargo.has_ragno" class="badge badge-primary badge-lg">Richiesto</span>
                            <span v-else class="badge badge-primary badge-lg">NON richiesto</span>   
                        </div>

                        
                    </div>

                    <Link
                        :href="route('worker.journeyCargo.index' )"
                        method="get"
                        as="button"
                        class="btn btn-primary btn-outline btn-success"
                    >
                        <font-awesome-icon :icon="['fas', 'list-check']" class="text-2xl"/>
                        Salva tutte le modifiche
                    </Link>
                </div>


                <h2>Materiali assegnati a me ({{ user.name }} {{ user.surname }})</h2>
                
                

                <div v-for="(item, index) in journeyCargo.items">
                    <WorkerItemRow
                        v-if="item.warehouse_manager_id == user.id"
                        :key="item.id"
                        :item="item"
                        :index="index"
                        :canModify="true"
                        :warehouseWorkers="props.warehouseWorkers"
                        :warehouse="journeyCargo.warehouse"
                        :manualModified="false"
                        :parentHasRagno="journeyCargo.has_ragno"
                        :parentMachineryTime="journeyCargo.machinery_time"
                    />
                </div>

                <h2>Materiali assegnati ad altri magazzinieri/non assegnati</h2>

                <div v-for="(item, index) in journeyCargo.items">
                    <WorkerItemRowEmpty
                        v-if="item.warehouse_manager_id != user.id"
                        :key="item.id"
                        :item="item"
                        :index="index"
                        :warehouse="journeyCargo.warehouse"
                    />
                </div>

            <hr class="my-8">
            </div>
            
        </div>
  
    </section>   
</template>
    
    <script setup>
    import Box from '@/Components/UI/Box.vue';
    import dayjs from 'dayjs'; 
    import { Link, usePage } from '@inertiajs/vue3';
    import { defineProps, computed, watch, ref, reactive, watchEffect , onMounted } from 'vue';
import WorkerItemRow from '../Components/WorkerItemRow.vue';
import JourneyCargoHead from '@/Components/JourneyCargoHead.vue';
import WorkerItemRowEmpty from '../Components/WorkerItemRowEmpty.vue';

    const props = defineProps({
        journeyCargos : Object,
        warehouseWorkers: Object,
    })

    const page = usePage();
    const user = computed(
        () => page.props.user
    )

    
    </script>
