<template>
        <div class="flex gap-2" :class="props.rows == 1? 'flex-row': 'flex-col'">
            <div class="flex flex-row items-center gap-2">
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-2xl"/>
                    <span>#<ZeroPaddingId :id="props.journey.id"/></span>
                </div>
                <div>
                    <font-awesome-icon  :icon="['fas', 'id-card']" class="text-2xl"/>
                    {{ props.journey.driver.name }} {{ props.journey.driver.surname }}
                </div>

            </div>
            <div class="flex flex-row items-center gap-2">
                <font-awesome-icon  :icon="['fas', 'truck']" class="text-2xl"/>
                {{ props.journey.vehicle.plate }} con <br/> {{ props.journey.cargo_for_vehicle.name }}
                <span v-if="props.journey.trailer_id">
                    <font-awesome-icon  :icon="['fas', 'trailer']" class="text-2xl"/>
                    {{ props.journey.trailer.plate }} con <br/> {{ props.journey.cargo_for_trailer.name }}
                </span>

            </div>
            <div class="flex flex-row items-center gap-2">
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'person-walking-arrow-right']" class="text-2xl"/>
                    <span>{{ props.journey.real_dt_start? dayjs(props.journey.real_dt_start).format('DD-MM-YYYY') : 'non ancora partito' }}</span>
                </div>
                <div class="flex flex-row items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'person-walking-arrow-loop-left']" class="text-2xl"/>
                    <span>{{ props.journey.real_dt_end? dayjs(props.journey.real_dt_end).format('DD-MM-YYYY') : 'non ancora tornato'}}</span>
                </div>
            </div>
        </div>
</template>

<script setup>
import ZeroPaddingId from './UI/ZeroPaddingId.vue';
import dayjs from 'dayjs';

const props = defineProps({
    journey: Object,
    rows: Number,
})

</script>