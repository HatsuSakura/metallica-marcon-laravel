<template>
        <h3 class="font-bold">Item scaricati in {{ warehouse.denominazione }}</h3>

        <div v-if="itemsWarehouse.length === 0" class="text-gray-500">
            Nessun item scaricato in questo magazzino

        </div>
        <div v-else>
            <div v-for="(item, index) in itemsWarehouse" :key="index">
                <div class="card">
                    <div class="card-header">
                        <h5>Item PRESNETE {{ item.id }}</h5>
                    </div>
                    <div class="card-body">
                        <p>Data: {{ item.created_at }}</p>
                        <p>Stato: {{ item.state }}</p>
                        <p>Warehouse: {{ item.warehouse_id }}</p>
                    </div>
                </div>
            </div>
        </div>
        

        <h3 class="font-bold">Item scaricati in altri magazzini</h3>

                <div v-if="itemsOthers.length === 0" class="text-gray-500">
            Nessun item scaricato in questo magazzino

        </div>
        <div v-else>
            <div v-for="(item, index) in itemsOthers" :key="index">
                <div class="card">
                    <div class="card-header">
                        <h5>Item {{ item.id }}</h5>
                    </div>
                    <div class="card-body">
                        <p>Data: {{ item.created_at }}</p>
                        <p>Stato: {{ item.state }}</p>
                        <p>Warehouse: {{ item.warehouse_id }}</p>
                    </div>
                </div>
            </div>
        </div>
</template>

<script setup>
    import { computed } from 'vue';

    const props = defineProps({
        items: Object,
        warehouse: Object,
    })

// Filtra gli item del magazzino corrente
const itemsWarehouse = computed(() =>
  props.items.filter(item =>
    item.journey_cargos[0]?.pivot?.warehouse_download_id === props.warehouse.id
  )
);

// Filtra tutti gli altri
const itemsOthers = computed(() =>
  props.items.filter(item =>
    item.journey_cargos[0]?.pivot?.warehouse_download_id !== props.warehouse.id
  )
);

</script>