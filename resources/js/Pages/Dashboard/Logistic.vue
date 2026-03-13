<template>
    <DashboardHeader>
        Dashboard Logistica
    </DashboardHeader>

    <div class="flex gap-2">
        <Link
            :href="route('order.index')"
            class="btn btn-primary"
        >
            Ordini
        </Link>
        <Link
            :href="route('journey.index')"
            class="btn btn-primary"
        >
            Viaggi
        </Link>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import { Link } from '@inertiajs/vue3';
</script>

