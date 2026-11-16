<template>
    <form class="space-y-3" @submit.prevent="saveTimetable">
        <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg font-semibold">Orari della sede</h2><AppAction v-if="!modificaAbilitata" variant="function" @click="toggleAbilitaModifica">Modifica orari sede</AppAction></div>
        <fieldset :disabled="busy" class="min-w-0 space-y-3">
            <div class="max-w-full overflow-x-auto rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary" :tabindex="modificaAbilitata ? 0 : undefined" :role="modificaAbilitata ? 'region' : undefined" :aria-label="modificaAbilitata ? 'Modifica orari della sede, tabella scorrevole' : undefined">
                <table class="table table-sm timetable-grid w-full text-sm" :class="modificaAbilitata ? 'timetable-edit' : 'table-fixed'" aria-label="Orari settimanali della sede">
                    <thead>
                        <tr><th rowspan="2" scope="col" class="text-left">Giorno</th><th colspan="2" scope="colgroup">Mattina</th><th colspan="2" scope="colgroup">Pomeriggio</th><th v-if="modificaAbilitata" rowspan="2" scope="col">Copia orari</th></tr>
                        <tr><th scope="col">Apertura</th><th scope="col">Chiusura</th><th scope="col">Apertura</th><th scope="col">Chiusura</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="day in days" :key="day.id">
                            <th scope="row" class="text-left font-medium"><span :class="!modificaAbilitata && 'xl:hidden'" :aria-label="weekDays[day.position - 1]">{{ weekDays[day.position - 1].slice(0,3) }}</span><span v-if="!modificaAbilitata" class="hidden xl:inline">{{ weekDays[day.position - 1] }}</span></th>
                            <td v-for="field in timeFields" :key="field.key" class="tabular-nums">
                                <input v-if="modificaAbilitata" v-model="day[field.key]" type="time" class="input input-bordered" :aria-label="`${weekDays[day.position - 1]}: ${field.label}`" />
                                <template v-else>{{ day[field.key] === '00:00' ? 'Chiuso' : day[field.key] }}</template>
                            </td>
                            <td v-if="modificaAbilitata"><AppAction variant="function" :aria-label="`Copia gli orari di ${weekDays[day.position - 1]} al giorno successivo`" @click="copyRow(day.position-1)"><font-awesome-icon :icon="['fas','copy']" aria-hidden="true" />Copia al successivo</AppAction></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="modificaAbilitata" class="flex flex-wrap justify-end gap-2">
                <AppAction variant="cancel" @click="cancelEdit">Annulla</AppAction><AppAction variant="confirm" type="submit" :busy="busy" :disabled="!changed">Salva orari</AppAction>
            </div>
        </fieldset>
        <p v-if="error" role="alert" class="text-sm text-error">{{ error }}</p>
    </form>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'

import { computed, reactive, ref } from 'vue';
import { useStore } from 'vuex';
import axios from 'axios';

const props = defineProps({
    site: Object,
});

const store = useStore();

const modificaAbilitata = ref(false);
const busy = ref(false);
const error = ref('');
const timeFields = [{key:'orarioApM',label:'Apertura mattina'},{key:'orarioChM',label:'Chiusura mattina'},{key:'orarioApP',label:'Apertura pomeriggio'},{key:'orarioChP',label:'Chiusura pomeriggio'}];

const days = reactive([
  { id: 0, position: 1, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
  { id: 1, position: 2, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
  { id: 2, position: 3, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
  { id: 3, position: 4, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
  { id: 4, position: 5, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
  { id: 5, position: 6, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
  { id: 6, position: 7, orarioApM: "00:00", orarioChM: "00:00", orarioApP: "00:00", orarioChP: "00:00" },
]);

// If timetable data is present, overwrite `days`
if (props.site.timetable) {
  const hours = props.site.timetable.hours_json;
  const parsedTimetable = JSON.parse(hours);
  days.forEach((day, index) => {
    Object.assign(day, parsedTimetable[index]);
  });
}

const weekDays = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica"];

const baseline = ref(JSON.stringify(days));
const changed = computed(() => JSON.stringify(days) !== baseline.value);
function cancelEdit() {
    JSON.parse(baseline.value).forEach((day, index) => Object.assign(days[index], day));
    modificaAbilitata.value = false;
    error.value = '';
}

function toggleAbilitaModifica(){
    modificaAbilitata.value = !modificaAbilitata.value;
}

function copyRow(currentRow){
  // Determine the target row index
  const targetRow = (currentRow + 1) % 7;
  // Copy each property of the current row to the target row
  Object.assign(days[targetRow], {
    orarioApM: days[currentRow].orarioApM,
    orarioChM: days[currentRow].orarioChM,
    orarioApP: days[currentRow].orarioApP,
    orarioChP: days[currentRow].orarioChP
  });

}

function saveTimetable() {
    if (busy.value || !changed.value || !modificaAbilitata.value) return;
    busy.value = true; error.value = '';
    axios.post(`/api/timetable/${props.site.id}`, { timetable_data: days })
        .then(response => {
            baseline.value = JSON.stringify(days);
            toggleAbilitaModifica();
            store.dispatch('flash/queueMessage', { type: 'success', text: 'Orari aggiornati correttamente' });
        })
        .catch(() => {
            error.value = 'Salvataggio orari non riuscito. Riprova.';
        }).finally(() => {busy.value = false});
}

</script>

<style scoped>
.timetable-grid th, .timetable-grid td { padding: .375rem .125rem; text-align: center; }
.timetable-grid th:first-child[rowspan], .timetable-grid tbody th { text-align: left; }
.timetable-grid thead { font-size: .75rem; color: oklch(var(--bc)); }
.timetable-grid tbody { font-size: .875rem; }
.timetable-edit { min-width: 42rem; table-layout: fixed; }
.timetable-edit th:first-child[rowspan] { width: 4rem; }
.timetable-edit th:last-child[rowspan] { width: 11rem; }
.timetable-edit td .input { min-width: 0; width: 100%; padding-inline: .375rem; font-size: 1rem; }
</style>
