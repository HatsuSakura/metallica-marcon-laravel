<template>
    <div v-if="historyJourneys?.data?.length > 0" class="flex flex-col items-center justify-center gap-2">
        <Box v-for="journey in historyJourneys.data" :key="journey.id" class="w-3/4">
            <div class="flex flex-row justify-between items-start">
                <div class="flex items-center gap-4">
                    <JourneyMainData :journey="journey" />
                    <span class="badge badge-outline">{{ journey.status }}</span>
                </div>
                <Link
                    :href="route('driver.journey.show', {journey: journey.id, return_to: returnTo})"
                    method="get"
                    as="button"
                    class="btn btn-primary btn-outline btn-sm"
                >
                    <font-awesome-icon :icon="['fas', 'eye']" class="text-2xl"/>
                    Dettagli Viaggio
                </Link>
            </div>
        </Box>

        <Pagination :links="historyJourneys.links" />
    </div>
    <EmptyState v-else>
        <div class="flex flex-col items-center gap-4">
            <div>Non ci sono viaggi nello storico</div>
        </div>
    </EmptyState>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Box from '@/Components/UI/Box.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import JourneyMainData from './JourneyMainData.vue';

defineProps({
    historyJourneys: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const returnTo = computed(() => page.url || route('driver.journey.index', { tab: 'storico' }));
</script>
