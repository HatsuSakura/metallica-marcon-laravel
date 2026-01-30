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

    <div v-for="item in props.order.items" :key="item.id">
        <div class="flex flex-row justify-start items-center gap-2 my-1">
            <!-- bullet column -->
            <div class="text-primary">
                <font-awesome-icon :icon="['fas', 'circle-dot']" class="text-xs"/>
            </div>
            <!-- bullet column -->
            <div>
                <div>
                    <span :class="item.cer_code.is_dangerous? 'text-error font-medium' : 'font-medium' ">{{ item.cer_code.code }}</span> &nbsp;
                    <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-xl"/> {{ item.weight_declared }} Kg
                </div>
                <div class="text-gray-500 text-sm">
                    {{ holderLabel(item) }}
                    <span v-if="customDimsLabel(item)">
                        <br />{{ customDimsLabel(item) }}
                    </span>
                </div>
            </div>
        </div>
        
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

const holderLabel = (item) => {
    if (item?.is_bulk) return 'Sfuso';
    if (!item?.holder) return 'Senza contenitore';
    return `${item.holder_quantity} x ${item.holder.name}`;
}

const customDimsLabel = (item) => {
    if (!item?.holder?.is_custom) return '';
    const L = item?.custom_l_cm;
    const W = item?.custom_w_cm;
    const H = item?.custom_h_cm;
    if (!L || !W || !H) return '';
    return `L ${L} x W ${W} x H ${H} cm`;
}

</script>
