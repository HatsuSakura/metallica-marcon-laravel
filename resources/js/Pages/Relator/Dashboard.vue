<template>
    <DashboardHeader>
        Dashboard Logistica
    </DashboardHeader>

    <div>
        <Link 
            :href="route('relator.journey.index')"
            class="btn btn-primary"
        >
            Viaggi
        </Link>
        &nbsp;
        <Link 
            :href="route('relator.dashboard')"
            class="btn btn-primary"
        >
            Vecchia Dashboard Logistica
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