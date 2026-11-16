<template>
    <Head title="I miei viaggi" />
    <section class="mx-auto w-full max-w-5xl min-w-0 space-y-5" :aria-busy="loading">
        <header class="flex flex-wrap items-center gap-2">
            <h1 class="text-2xl font-bold">I miei viaggi</h1>
            <UiBadge :tone="journeys.length > 0 ? 'success' : 'neutral'" :aria-label="`${journeys.length} ${journeys.length === 1 ? 'viaggio' : 'viaggi'} nel giorno selezionato`">{{ journeys.length }}</UiBadge>
        </header>
        <form class="rounded-2xl border border-base-300 bg-base-100 p-3 space-y-2 sm:flex sm:flex-wrap sm:items-center sm:gap-3 sm:space-y-0" @submit.prevent="navigate(dateInput)">
            <div class="flex items-center justify-between gap-3 sm:contents">
                <h2 class="min-w-0 text-lg font-semibold capitalize">
                    <span class="block">{{ selectedDate === today ? 'Oggi · ' : '' }}{{ weekdayLabel }}</span>
                </h2>
                <AppAction class="sm:order-3" :disabled="loading || selectedDate === today" @click="navigate(today)">Oggi</AppAction>
            </div>
            <div class="grid min-w-0 grid-cols-[3rem_minmax(0,1fr)_3rem] items-center gap-2 sm:ml-auto sm:w-80">
                <AppAction shape="square" aria-label="Giorno precedente" :disabled="loading" @click="navigate(previousDate)"><font-awesome-icon :icon="['fas','chevron-left']" aria-hidden="true" /></AppAction>
                <div class="min-w-0">
                    <label for="driver-day" class="sr-only">Scegli il giorno dei viaggi</label>
                    <input id="driver-day" v-model="dateInput" type="date" required class="input input-bordered h-12 min-h-12 w-full min-w-0 max-w-full text-base" :aria-invalid="!!errors.date" :aria-describedby="errors.date ? 'driver-date-error' : undefined" :disabled="loading" @change="navigate(dateInput)" />
                </div>
                <AppAction shape="square" aria-label="Giorno successivo" :disabled="loading" @click="navigate(nextDate)"><font-awesome-icon :icon="['fas','chevron-right']" aria-hidden="true" /></AppAction>
            </div>
            <p id="driver-date-error" v-if="errors.date" class="text-sm text-error" role="alert">{{ errors.date }}</p>
        </form>
        <p v-if="loading" role="status" class="text-sm">Caricamento viaggi…</p>
        <p v-if="navigationError" role="alert" class="text-error">{{ navigationError }}</p>
        <section v-if="otherActiveJourneys.length" aria-labelledby="active-journeys" class="rounded-2xl border border-success p-4 space-y-3">
            <div><h2 id="active-journeys" class="font-semibold">Ancora in corso · altra data</h2><p class="text-sm opacity-70">Puoi riprendere questi viaggi anche se non appartengono al giorno selezionato.</p></div>
            <DriverJourneyCard v-for="journey in otherActiveJourneys" :key="journey.id" :journey="journey" :return-to="returnTo" show-date />
        </section>
        <div v-if="journeys.length" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <DriverJourneyCard v-for="(journey, index) in journeys" :key="journey.id" :journey="journey" :position="journeys.length > 1 ? index + 1 : null" :return-to="returnTo" />
        </div>
        <div v-else class="rounded-2xl border border-dashed border-base-300 p-6 text-center space-y-2">
            <h2 class="font-semibold">Nessun viaggio per questo giorno</h2><p class="text-sm opacity-70">Scegli un’altra data per consultare i viaggi precedenti o quelli in programma.</p>
        </div>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import DriverJourneyCard from './Components/DriverJourneyCard.vue';
import AppAction from '@/Components/UI/AppAction.vue';
import UiBadge from '@/Components/UI/UiBadge.vue';

const props = defineProps({
    selectedDate: { type: String, required: true }, today: { type: String, required: true },
    previousDate: { type: String, required: true }, nextDate: { type: String, required: true },
    journeys: { type: Array, default: () => [] }, otherActiveJourneys: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});
const page = usePage();
const returnTo = computed(() => page.url);
const dateInput = ref(props.selectedDate);
const loading = ref(false);
const navigationError = ref('');
watch(() => props.selectedDate, (value) => { dateInput.value = value; });
const selectedDay = computed(() => new Date(`${props.selectedDate}T12:00:00Z`));
const weekdayLabel = computed(() => new Intl.DateTimeFormat('it-IT', { weekday: 'long', timeZone: 'Europe/Rome' }).format(selectedDay.value));
const navigate = (date) => {
    if (!date || loading.value) return;
    router.get(route('driver.home'), { date }, {
        preserveState: true,
        onBefore: () => { loading.value = true; navigationError.value = ''; },
        onError: () => { dateInput.value = props.selectedDate; navigationError.value = 'Impossibile cambiare giorno. Controlla la data e riprova.'; },
        onFinish: () => { loading.value = false; },
    });
};
</script>
