<template>
    <div class="font-medium text-lg">
        <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl"/>
        {{ props.order.customer.company_name }}
    </div>
    <div class="text-gray-500">
        <font-awesome-icon :icon="['fas', 'location-dot']" class="text-2xl"/>
        Sede: {{ props.order.site.address }}
    </div>
</template>

<script setup>

import { defineProps, computed } from 'vue';

const props = defineProps({
    order: Object,
});


</script>
