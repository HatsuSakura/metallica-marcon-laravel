<template>
<Box>
    <div class="flex flex-row justify-between items-center">
        <div>
            <JourneyHead :journey ="props.journey"/>
        </div>
        <Link v-if=" props.journey.journey_cargo_count==0" :href="route('relator.journeyCargo.create', {journey:  props.journey.id})" class="btn btn-primary btn-outline font-medium text-center">
            Gestisci Viaggio <br/>[Cassoni/Scarico]
            <font-awesome-icon :icon="['fas', 'circle']" class="text-secondary"/>
        </Link>
        <Link v-else :href="route('relator.journeyCargo.edit', {journey:  props.journey.id})" class="btn btn-primary btn-outline font-medium text-center">
            Gestisci Viaggio <br/>[Cassoni/Scarico]
            <font-awesome-icon :icon="['fas', 'circle']" class="text-success"/>
        </Link>
    </div>
</Box>
</template>

<script setup>
import JourneyHead from '@/Components/JourneyHead.vue';
import Box from '@/Components/UI/Box.vue';
import { Link, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';

const props = defineProps({
    journey: Object,
})
</script>