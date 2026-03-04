<template>
    <DashboardHeader>
        Dashboard Logistica
    </DashboardHeader>

    <div>
        <Link
            :href="route('journey.index')"
            class="btn btn-primary"
        >
            Viaggi
        </Link>
        &nbsp;
        <Link
            :href="route('dashboard.logistic.full')"
            class="btn btn-primary"
        >
            Vecchia Dashboard Logistica
        </Link>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import { Link } from '@inertiajs/vue3';
</script>

