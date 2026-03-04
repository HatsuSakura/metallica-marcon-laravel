<template>
    <div v-if="user.id == props.row.warehouse_manager_id">
        Hai {{ props.row.total }} materiale/i da gestire.
        
        <Link @click="closeDrawer" class="btn btn-primary btn-outline" :href="route('worker.journeyCargo.index')">
            Gestisci materiale/i
        </Link>

    </div>
    <div v-else>
        <div v-if="props.row.warehouse_manager_id">
            L'utente {{ props.row.warehouse_manager.name }} {{ props.row.warehouse_manager.surname }} ha {{ props.row.total }} materiale/i da gestire.
        </div>
        <div v-else>
            {{ props.row.total }} materiale/i non ancora assegnati.
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';
import { computed, watch, ref, reactive, watchEffect , onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    row: Object,
})

const page = usePage();
const user = computed(
    () => page.props.user
)
</script>