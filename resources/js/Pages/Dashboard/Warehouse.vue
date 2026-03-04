<template>
    <DashboardHeader>
        Dashboard Magazzino
    </DashboardHeader>

    <div>
        <Link 
            :href="route('warehouse-manager.orders.index')"
            class="btn btn-primary"
            >Ordini Scaricati
        </Link>

    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import { Link, usePage } from '@inertiajs/vue3';

</script>

<style scoped>
h1 {
    color: #333;
    font-family: Arial, sans-serif;
}
</style>