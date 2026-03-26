<template>
    <DashboardHeader>
        Dashboard chiusura viaggi - Logistica
    </DashboardHeader>

    <div class="flex flex-wrap items-center gap-2 mb-4">
        <Link
            :href="route('logistic.home')"
            class="btn btn-ghost btn-sm"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />    
            Torna a Dashboard
        </Link>
        <Link
            :href="route('logistic-dispatch.index', { status: 'to_manage' })"
            class="btn btn-sm"
            :class="status === 'to_manage' ? 'btn-primary' : 'btn-outline'"
        >
            Viaggi da gestire
        </Link>
        <Link
            :href="route('logistic-dispatch.index', { status: 'managed' })"
            class="btn btn-sm"
            :class="status === 'managed' ? 'btn-primary' : 'btn-outline'"
        >
            Viaggi gestiti
        </Link>
    </div>

    <div class="space-y-3">
        <div
            v-for="journey in journeys.data"
            :key="journey.id"
            class="card bg-base-100 border border-base-200 shadow-sm"
        >
            <div class="card-body p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="font-semibold">
                        Viaggio #{{ journey.id }} - {{ journey.status }}
                    </div>
                    <Link
                        :href="route('logistic-dispatch.show', journey.id)"
                        class="btn btn-sm btn-outline"
                    >
                        Apri dettaglio
                    </Link>
                </div>
                <div class="text-sm opacity-80">
                    Driver: {{ fullName(journey.driver) }} |
                    Mezzo: {{ journey.vehicle?.plate ?? '-' }} |
                    Rimorchio: {{ journey.trailer?.plate ?? '-' }}
                </div>
                <div class="text-sm opacity-80">
                    Chiusura viaggio:
                    <span class="badge ml-1" :class="dispatchStatusBadgeClass(journey.dispatch_status)">
                        {{ dispatchStatusLabel(journey.dispatch_status) }}
                    </span>
                </div>
                <div class="text-sm opacity-80">
                    Ordini: {{ journey.orders?.length ?? 0 }} |
                    Primario: {{ journey.primary_warehouse?.name ?? '-' }} |
                    Secondario: {{ journey.secondary_warehouse?.name ?? '-' }}
                </div>
            </div>
        </div>

        <div
            v-if="journeys.data.length === 0"
            class="alert"
        >
            Nessun viaggio disponibile per questo filtro.
        </div>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import { Link } from '@inertiajs/vue3';
import {
    dispatchStatusBadgeClass as getDispatchStatusBadgeClass,
    dispatchStatusLabel as getDispatchStatusLabel,
} from '@/Constants/dispatchStatus';

defineProps({
    journeys: {
        type: Object,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
});

function fullName(user) {
    if (!user) return '-';
    return `${user.name ?? ''} ${user.surname ?? ''}`.trim();
}

function dispatchStatusLabel(status) {
    return getDispatchStatusLabel(status);
}

function dispatchStatusBadgeClass(status) {
    return getDispatchStatusBadgeClass(status);
}
</script>

