<template>
    <div>
        <h2>Dashboard</h2>

        <div v-if="user.role == 'logistic' || user.role == 'developer'" class="flex flex-col gap-2 w-full p-4 mb-4">

            <!-- VIAGGI IN CORSO E CASSONI -->
            <div class="flex flex-row gap-2 w-full">

                <div class="w-1/2">
                    <h2>Viaggi in Corso</h2>
                    <div class="flex flex-col gap-2">
                        <DashViaggioSimpleList v-for="journey in props.journeys.data" v-bind:key="journey.id" :journey=journey class="w-full"/>
                    </div>
                </div>

                <div class="w-1/2">
                    <h2>Cassoni da gestire</h2>
                    <div class="flex flex-col gap-2">
                        <DashCassoneSimpleList v-for="journeyCargo in props.journeyCargos" v-bind:key="journeyCargo.id" :journeyCargo="journeyCargo" class="w-full"/>
                    </div>
                </div>
            </div>

            <div class="flex flex-row gap-2 w-full">
                
                <div class="w-1/2">
                    <h2>Messe a Terra</h2>
                    <div v-if="props.groundings.length > 0" class="flex flex-col gap-2">
                        <DashCassoneSimpleList v-for="journeyCargo in props.groundings" v-bind:key="journeyCargo.id" :journeyCargo="journeyCargo" class="w-full"/>
                    </div>
                    <EmptyState v-else>
                        Nessun cassone messo a terra in appoggio
                    </EmptyState>
                </div>

                <div class="w-1/2">
                    <h2>Materiali da Trasbordare</h2>
                    <div v-if="props.transshipments.length > 0" class="flex flex-col gap-2">
                        <DashItemSimpleList v-for="item in props.transshipments" v-bind:key="item.id" :item=item class="w-full"/>
                    </div>
                    <EmptyState v-else>
                        Nessun materiale da trasbordare
                    </EmptyState>
                </div>

            </div>


        </div>


        <div v-if="user.role == 'warehouse_chief' || user.role == 'developer'">
            <h2>Gestione Cassoni <span v-if="user.role == 'developer'"> | CAPO MAGAZZINO</span></h2>
            <DashCapoMagazzinoGestioneCassoni 
                :journeyCargos="props.journeyCargos"
                :user="user"
                :warehouses="props.warehouses"
            />
        </div>

        <div v-if="user.role == 'warehouse_chief' || user.role == 'developer'">
            <h2>Gestione Materiali scaricati da altro cassone (Doppio Scarico) <span v-if="user.role == 'developer'"> | CAPO MAGAZZINO</span></h2>
            <div v-for="(item, index) in itemsFromOtherCargosDoubleLoad">
                <WorkerItemRow
                    :key="item.id"
                    :item="item"
                    :index="index"
                    :canModify="true"
                    :warehouseWorkers="props.warehouseWorkers"
                    :warehouse="item.warehouse"
                    :manualModified="false"
                    :parentHasRagno="false"
                    :parentMachineryTime="0"
                />
            </div>
            
        </div>

        <div v-if="user.role == 'warehouse_chief' || user.role == 'developer'">
            <h2>Gestione Materiali/Cassoni Magazziniere</h2>
            <div v-if="props.itemsCount.length > 0">
                <DashMagazziniereItemsCount v-for="row in props.itemsCount" v-bind:key="row.id" :row=row class="w-full"/>
            </div>
            <div v-else>
                <EmptyState>
                    Nessun materiale da assegnare ai magazzinieri
                </EmptyState>
            </div>
            
            
        </div>
        <div v-if="user.role == 'warehouse_manager' || user.role == 'developer'">
            <h2>Gestione Materiali/Cassoni Magazziniere</h2>
            <DashMagazziniereItemsCount 
                v-if="currentManagerRow" 
                :row="currentManagerRow" 
                class="w-full" 
            />
            <div v-else>
                <EmptyState>
                    Nessun materiale da gestire per {{ user.name }} {{ user.surname }}
                </EmptyState>
            </div>
        </div>



    </div>
</template>

<script setup>
import { computed, watch, ref, reactive, watchEffect , onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import DashCassoneSimpleList from './Components/DashCassoneSimpleList.vue';
import DashCapoMagazzinoGestioneCassoni from './Components/DashCapoMagazzinoGestioneCassoni.vue';
import DashViaggioSimpleList from './Components/DashViaggioSimpleList.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import DashItemSimpleList from './Components/DashItemSimpleList.vue';
import DashMagazziniereItemsCount from './Components/DashMagazziniereItemsCount.vue';
import WorkerItemRow from '@/Pages/Warehouse/Components/WorkerItemRow.vue';

const props = defineProps({ 
    journeys: Object,
    transshipments: Object,
    groundings: Object, 
    journeyCargos: Object,
    doubleLoadItems: Object,
    itemsCount: Object,
    warehouses: Object,
})

const page = usePage();
const user = computed(
    () => page.props.user
)

const currentManagerRow = computed(() => {
  return props.itemsCount.find(row => row.warehouse_manager_id === user.value.id) || null;
});


const myWarehouseCargos = computed(() => {
  return props.journeyCargos.filter(cargo => {
    const warehouse = props.warehouses.find(w => w.id === cargo.warehouse_id);
    // If there's no matching warehouse, or it has no chiefs, skip this cargo.
    if (!warehouse || !warehouse.chiefs) return false;
    // Check if the current user is present in the warehouse's chiefs list.
    return warehouse.chiefs.some(chief => chief.id === user.value.id);
  });
});


const itemsFromOtherCargosDoubleLoad = computed(() => {
    return props.doubleLoadItems.filter(item => {
        const warehouse = props.warehouses.find(w => w.id === item.warehouse_download_id);
        // If there's no matching warehouse, or it has no chiefs, skip this cargo.
        if (!warehouse || !warehouse.chiefs) return false;
        // Check if the current user is present in the warehouse's chiefs list.
        return warehouse.chiefs.some(chief => chief.id === user.value.id);
  });
});

</script>
