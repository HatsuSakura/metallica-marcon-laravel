<template>
    <DashboardHeader>
        Dispatch Journey #{{ localJourney.id }}
    </DashboardHeader>

    <div class="mb-4">
        <Link
            :href="route('logistic-dispatch.index')"
            class="btn btn-ghost"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl" />
            Torna a Dispatch
        </Link>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="xl:col-span-2 space-y-4">
            <DispatchPlanCard
                :journey="localJourney"
                :warehouses="warehouses"
                @updated="onPlanUpdated"
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
                            Ordine #{{ order.id }} - {{ order.customer?.company_name ?? '-' }}
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

        <div class="space-y-4">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <div class="font-semibold mb-2">
                        Azioni operative
                    </div>
                    <textarea
                        v-model="actionNotes"
                        class="textarea textarea-bordered w-full"
                        rows="4"
                        placeholder="Annotazioni operative"
                    />
                    <button
                        class="btn btn-sm btn-warning mt-2"
                        @click="sendAction('hold')"
                    >
                        Metti in attesa
                    </button>
                    <button
                        class="btn btn-sm btn-info mt-2"
                        @click="sendAction('resume')"
                    >
                        Riprendi
                    </button>
                    <button
                        class="btn btn-sm btn-success mt-2"
                        @click="sendAction('complete')"
                    >
                        Completa dispatch
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import DashboardHeader from '@/Components/UI/HeaderForDashboard.vue';
import DispatchPlanCard from '@/Pages/LogisticDispatch/Partials/DispatchPlanCard.vue';
import { Link } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useStore } from 'vuex';

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
const actionNotes = ref('');
const store = useStore();

function onPlanUpdated(journey) {
    Object.assign(localJourney, journey);
}

async function sendAction(type) {
    try {
        if (type === 'hold') {
            await axios.post(route('api.logistic-dispatch.hold', localJourney.id), {
                notes: actionNotes.value,
            });
            store.dispatch('flash/queueMessage', {
                type: 'success',
                text: 'Viaggio messo in attesa.',
            });
            return;
        }

        if (type === 'resume') {
            await axios.post(route('api.logistic-dispatch.resume', localJourney.id), {
                notes: actionNotes.value,
            });
            store.dispatch('flash/queueMessage', {
                type: 'success',
                text: 'Viaggio ripreso correttamente.',
            });
            return;
        }

        await axios.post(route('api.logistic-dispatch.complete', localJourney.id), {
            completion_code: localJourney.is_double_load ? 'double_load_done' : 'single_load_done',
            notes: actionNotes.value,
        });
        store.dispatch('flash/queueMessage', {
            type: 'success',
            text: 'Dispatch completato correttamente.',
        });
    } catch (error) {
        store.dispatch('flash/queueMessage', {
            type: 'error',
            text: error?.response?.data?.message ?? 'Errore durante l\'operazione dispatch.',
        });
    }
}
</script>
