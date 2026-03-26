<template>
    <DashboardHeader>
        Chiusura Viaggio #{{ localJourney.id }} 
        <span class="badge ml-1" :class="dispatchStatusBadgeClass(localJourney.dispatch_status)">
            {{ dispatchStatusLabel(localJourney.dispatch_status) }}
        </span>
    </DashboardHeader>

    <div class="mb-4">
        <Link
            :href="route('logistic-dispatch.index')"
            class="btn btn-ghost"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
            Torna a gestione chiusure  
        </Link>
    </div>

    <div class="space-y-4">
        <DispatchWorkspacePanel
            :journey="localJourney"
            :warehouses="warehouses"
            @journey-updated="onJourneyWorkspaceUpdated"
        />

        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="font-semibold mb-2">
                    Ordini e note operative
                </div>
                <div
                    v-for="order in localJourney.orders"
                    :key="order.id"
                    class="border border-base-200 rounded-lg p-3 mb-2"
                >
                    <div class="font-medium">
                        Ordine {{ order.legacy_code ?? 'no code' }} - {{ order.customer?.company_name ?? '-' }}
                    </div>
                    <div class="text-sm opacity-80">
                        Sito: {{ order.site?.name ?? '-' }} - {{ order.site?.address ?? '-' }}
                    </div>
                    <div class="text-sm opacity-80">
                        Note ordine: {{ order.notes || '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import DispatchWorkspacePanel from '@/Pages/LogisticDispatch/Partials/DispatchWorkspacePanel.vue';
import { Link } from '@inertiajs/vue3';
import { reactive } from 'vue';
import {
    dispatchStatusBadgeClass as getDispatchStatusBadgeClass,
    dispatchStatusLabel as getDispatchStatusLabel,
} from '@/Constants/dispatchStatus';

const props = defineProps({
    journey: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
});

const localJourney = reactive({ ...props.journey });

function onJourneyWorkspaceUpdated(journey) {
    Object.assign(localJourney, journey);
}

function dispatchStatusLabel(status) {
    return getDispatchStatusLabel(status);
}

function dispatchStatusBadgeClass(status) {
    return getDispatchStatusBadgeClass(status);
}
</script>

