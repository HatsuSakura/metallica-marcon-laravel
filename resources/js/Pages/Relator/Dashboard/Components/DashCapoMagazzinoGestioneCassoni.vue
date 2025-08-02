<template>

    <div class="flex flex-row gap-2">
        <DashCassoneMagazzino v-for="journeyCargo in myWarehouseCargos" v-bind:key="journeyCargo.id" :journeyCargo="journeyCargo" 
        class="mb-2 w-1/2"
        />
    </div>


</template>

<script setup>
import { computed } from 'vue';
import DashCassoneMagazzino from './DashCassoneMagazzinoList.vue';
const props = defineProps({
    journeyCargos: Object,
    user: Object,
    warehouses: Object,
});


const myWarehouseCargos = computed(() => {
  return props.journeyCargos.filter(cargo => {
    const warehouse = props.warehouses.find(w => w.id === cargo.warehouse_id);
    // If there's no matching warehouse, or it has no chiefs, skip this cargo.
    if (!warehouse || !warehouse.chiefs) return false;
    // Check if the current user is present in the warehouse's chiefs list.
    return warehouse.chiefs.some(chief => chief.id === props.user.id);
  });
});


</script>
