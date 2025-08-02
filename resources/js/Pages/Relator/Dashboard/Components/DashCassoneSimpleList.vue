<template>
<Box>
    <div class="flex flex-row justify-between items-center">

        <JourneyCargoHead :journeyCargo="props.journeyCargo"/>

        <div>
            <Link
                :href="route('relator.journeyCargo.show', {journeyCargo: props.journeyCargo.id} )"
                method="get"
                as="button"
                class="btn btn-primary btn-circle btn-outline mr-2"
            >
                <font-awesome-icon :icon="['fas', 'eye']" class="text-lg" />
            </Link>
            <Link
                :href="route('relator.journeyCargo.edit', {journey: props.journeyCargo.journey_id} )"
                method="get"
                as="button"
                class="btn btn-primary btn-circle btn-outline mr-2"
            >
                <font-awesome-icon :icon="['fas', 'pen']" class="text-lg" />
            </Link>
        </div>

    </div>
</Box>

</template>

<script setup>
import ZeroPaddingId from '@/Components/UI/ZeroPaddingId.vue';
import Box from '@/Components/UI/Box.vue';
import dayjs from 'dayjs';
import { Link, usePage } from '@inertiajs/vue3';
import JourneyCargoHead from '@/Components/JourneyCargoHead.vue';

const props = defineProps({
    journeyCargo: Object,
})

</script>