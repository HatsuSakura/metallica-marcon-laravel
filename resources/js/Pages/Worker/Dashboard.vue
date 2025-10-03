<template>
    <div>
        <h1>Dahboard WAREHOUSE</h1>
        <Link 
            :href="route('warehouse-manager.orders.index')"
            class="btn btn-primary"
            >Dashboard Ordini Capo Magazziniere
        </Link>

    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';

</script>

<style scoped>
h1 {
    color: #333;
    font-family: Arial, sans-serif;
}
</style>