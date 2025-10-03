<template>

<section>

    <h2 class="mb-4">
        Viaggi Assegnati a me
    </h2>
    <div v-if="props.journeys.data.length > 0" class="flex flex-col items-center justify-center gap-2">
        <Box v-for="journey in props.journeys.data" :key="journey.id" class="w-3/4">
        
            <div class="flex flex-row justify-between items-center mb-4">
                <div>
                    <JourneyMainData :journey="journey" />
                </div>
                <div>
                    <Link v-if="journey.state != 'eseguito'"
                        :href="route('driver.journey.edit', {journey: journey.id} )"
                        method="get"
                        as="button"
                        class="btn btn-primary btn-outline"
                        >
                        <font-awesome-icon :icon="['fas', 'pen']" class="text-2xl"/>
                        Gestisci Viaggio
                    </Link>
                    <button v-else
                        disabled
                        class="btn btn-success btn-outline cursor-not-allowed"
                        >
                        <font-awesome-icon :icon="['fas', 'check']" class="text-2xl"/>
                        Viaggio Concluso
                    </button>
                </div>
            </div>
            
            <JourneyOrdersList :journey="journey" :warehouses="props.warehouses" :is_double_load="form.is_double_load"/>

        </Box>
    </div>
    <EmptyState v-else>
        <div class="flex flex-col items-center gap-4">
            <div>Non ci sono viaggi da gestire</div>
        </div>
    </EmptyState>


</section>



</template>

<script setup>
import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';
import { useForm, usePage } from '@inertiajs/vue3'
import JourneyMainData from './Components/JourneyMainData.vue';
import JourneyOrdersList from './Components/JourneyOrdersList.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';

const props = defineProps({
    journeys : Object,
    warehouses: Object,
})

const form = useForm({
    is_double_load: false,
    is_temporary_storage: false,
    warehouse_id: null,
})


</script>