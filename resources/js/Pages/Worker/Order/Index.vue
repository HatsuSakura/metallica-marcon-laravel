<template>


<div class="flex flex-row flex-wrap justify-stretch items-stretch mb-4 gap-0 w-full">

    <div v-for="(order, index) in orders" :key="index" class="w-full md:w-1/2 lg:w-1/3 xl:w-1/4 p-2">
        <div class="card bg-base-100 shadow-xl p-2 w-full">
            <div class="card-header">
                <h5>Ordine {{ order.legacy_code }}</h5>
            </div>
            <div class="card-body">
                <p>Data: {{ dayjs(order.created_at).format('DD-MM-YYYY') }}</p>
                <p>Stato: {{ order.state }}</p>
                <div class="flex flex-row gap-2">
                    <Link 
                        class="btn btn-outline text-xs font-medium" 
                        :href="route('warehouse-manager.orders.show', {order: order.id})"
                        target="_blank"
                    >
                    <font-awesome-icon :icon="['fas', 'eye']" class="text-2xl"/>
                    Preview
                    </Link>
                    <Link 
                        class="btn btn-outline text-xs font-medium"
                        :href="route('warehouse-manager.orders.edit', {order: order.id})"
                    >
                        <font-awesome-icon :icon="['fas', 'pencil']" class="text-2xl"/>
                        Edit
                    </Link>
                </div>
            </div>
        </div>

    </div>

</div>

</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';


const props = defineProps({
    orders: Object,
})
</script>