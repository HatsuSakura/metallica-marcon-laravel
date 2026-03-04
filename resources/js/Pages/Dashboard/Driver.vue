<template>
    <DashboardHeader>
        Dashboard Autista
    </DashboardHeader>

    <div>
        <Link 
            :href="route('driver.journey.index')"
            class="btn btn-primary"
        >
        Viaggi in carico a me
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