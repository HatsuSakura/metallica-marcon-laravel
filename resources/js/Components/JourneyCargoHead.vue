<template>
        <div class="flex gap-2" :class="props.rows == 1? 'flex-row': 'flex-col'">
            <div class="flex flex-row items-center gap-2">
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-2xl"/>
                    <span>#<ZeroPaddingId :id="props.journeyCargo.journey_id"/></span>
                </div>
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'calendar-day']" class="text-2xl"/>
                    <span>{{ dayjs(props.journeyCargo.journey.real_dt_start).format('DD-MM-YYYY') }}</span>
                </div>
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl"/>
                    <span>{{ props.journeyCargo.warehouse.denominazione }}</span>
                </div>
            </div>
            <div class="flex flex-row items-center gap-2">
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon v-if="props.journeyCargo.carrier.is_vehicle" :icon="['fas', 'truck']" class="text-2xl"/>
                    <font-awesome-icon v-else :icon="['fas', 'trailer']" class="text-2xl"/>
                    {{ props.journeyCargo.carrier.carrier_data.plate }} con 
                    <span>{{ props.journeyCargo.cargo.name }}</span>
                </div>
                
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'box-open']" class="text-2xl"/>
                    <span class="">#<ZeroPaddingId :id="props.journeyCargo.id"/></span>
                </div>
            </div>
        </div>
</template>

<script setup>
import ZeroPaddingId from './UI/ZeroPaddingId.vue';
import dayjs from 'dayjs';

const props = defineProps({
    journeyCargo: Object,
    rows: Number,
})

</script>