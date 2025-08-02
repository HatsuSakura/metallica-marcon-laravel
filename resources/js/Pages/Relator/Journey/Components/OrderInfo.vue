<template>
    <div>
    <span v-if="Boolean(props.order.is_urgent)" class="font-semibold text-error">UGENTE</span>
    <span v-else class="font-semibold text-success">NON urgente</span>
    </div>
    <div>
    <font-awesome-icon :icon="['fas', 'user-tie']" class="text-xl"/>
    {{props.order.customer.ragione_sociale}}
    </div>
    <div>
    <font-awesome-icon :icon="['fas', 'location-dot']" class="text-xl"/>
    {{props.order.site.indirizzo}}
    </div>
    <div>
    <font-awesome-icon :icon="['fas', 'calendar-days']" class="text-xl"/>
    {{formatDate(props.order.requested_at)}}
    </div>
    <div>
    <font-awesome-icon :icon="['fas', 'user-tag']" class="text-xl"/>  
    {{ props.order.logistic.name }}
    </div>
    <div class="flex flex-row justify-items-stretch content-center w-full my-4">
        <div class="flex flex-1" :class="order.site.has_muletto? 'text-error font-medium' : 'font-medium' ">
            <font-awesome-icon :icon="['fas', 'truck-ramp-box']" class="text-2xl"/>
        </div>
        <div class="flex flex-1" :class="order.site.has_transpallet_el? 'text-error font-medium' : 'font-medium' ">
            <font-awesome-icon :icon="['fas', 'cart-flatbed']" class="text-2xl"/>
        </div>
        <div class="flex flex-1" :class="order.site.has_transpallet_ma? 'text-error font-medium' : 'font-medium' ">
            <font-awesome-icon :icon="['fas', 'dolly']" class="text-2xl"/>
        </div>
    </div>

    <div v-for="item in props.order.items">
        <span :class="item.cer_code.is_dangerous? 'text-error font-medium' : 'font-medium' ">{{ item.cer_code.code }}</span>
        {{ item.holder_quantity }} x {{ item.holder.name }} <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl"/> {{ item.weight_declared }}
    </div>

</template>

<script setup>
import dayjs from 'dayjs';

const props = defineProps({
    order : Object,
});

const formatDate = (date) => {
    return dayjs(date).format('DD/MM/YYYY HH:mm'); // Customize the format as needed
}

</script>