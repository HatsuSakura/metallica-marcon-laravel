<template>

<section>
<Link @click="closeDrawer" :href="route('journey.create')" class="btn btn-primary">
    <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-2xl"/>
    Pianifica nuovo viaggio
</Link>
</section>

<section class="mt-4">

    <EmptyState v-if="!props.journeys.data">
        Nessun Viaggio Pianificato
    </EmptyState>
    <div v-else>
    <Box v-for="journey in props.journeys.data" class="mb-2">
        <div class="flex flex-row justify-between items-center">
            <div>
                Viaggio # {{ journey.id }} previsto il  {{ dayjs(journey.dt_start).format("DD/MM/YYYY HH:mm") }}
                <br>
                Autista: {{ journey.driver.name }}  {{ journey.driver.surname }}
                <br>
                Mezzo: {{ journey.vehicle.name }} -  {{ journey.vehicle.plate }} <span v-if="journey.trailer"> con {{ journey.trailer.name }} -  {{ journey.trailer.plate }}</span>
            </div>

            <div>
                <Link
                    :href="route('journey.edit', {journey: journey.id} )"
                    method="get"
                    as="button"
                    class="btn btn-primary btn-circle mr-2"
                >
                    <font-awesome-icon :icon="['fas', 'pen']" class="text-lg" />
                </Link>

                <Link
                    :href="route('journey.destroy', {journey: journey.id} )"
                    method="delete"
                    as="button"
                    class="btn btn-error btn-circle"
                >
                    <font-awesome-icon :icon="['fas', 'trash-can']" class="text-lg" />
                </Link>
            </div>

        </div>
    </Box>
    </div>
</section>



</template>

<script setup>
import Box from '@/Components/UI/Box.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    journeys : Object,
})

</script>