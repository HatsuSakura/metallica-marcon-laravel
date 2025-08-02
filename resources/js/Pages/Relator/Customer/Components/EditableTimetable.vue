<template>


<form @submit.prevent="saveTimetable">
        <table class="table-auto border-collapse border border-gray-400 w-full">
    <thead>
      <tr>
        <th class="border border-gray-300 p-2">Giorno</th>
        <th class="border border-gray-300 p-2">Apertura AM</th>
        <th class="border border-gray-300 p-2">Chiusura AM</th>
        <th class="border border-gray-300 p-2">Apertura PM</th>
        <th class="border border-gray-300 p-2">Chiusura PM</th>
        <th class="border border-gray-300 p-2">Azioni</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="day in days" :key="day.id">
        <td class="border border-gray-300 p-2">{{ weekDays[day.position - 1] }}</td>
        <td class="border border-gray-300 p-2">
            <input
                id="time"
                type="time"
                v-model="day.orarioApM"
                @change="onTimeChange"
                :disabled= "!modificaAbilitata"
            />
        </td>
        <td class="border border-gray-300 p-2">
            <input
                id="time"
                type="time"
                v-model="day.orarioChM"
                @change="onTimeChange"
                :disabled= "!modificaAbilitata"
            />
        </td>
        <td class="border border-gray-300 p-2">
            <input
                id="time"
                type="time"
                v-model="day.orarioApP"
                @change="onTimeChange"
                :disabled= "!modificaAbilitata"
            />
        </td>
        <td class="border border-gray-300 p-2">
            <input
                id="time"
                type="time"
                v-model="day.orarioChP"
                @change="onTimeChange"
                :disabled= "!modificaAbilitata"
            />
        </td>
        <td class="border border-gray-300 p-0">
            <button 
                type="button"
                class="btn btn-ghost flex flex-row flex-nowrap text-xl"
                :class="classeModificaAbilitata"
                @click="copyRow(day.position-1)"
            >
                <font-awesome-icon :icon="['fas', 'copy']" />
                <font-awesome-icon :icon="['fas', 'turn-down']" />
            </button>
        </td>
      </tr>
    </tbody>
  </table>
    <div class="flex flex-row flex-nowrap gap-4">
        <button v-if="!modificaAbilitata" 
            class="btn btn-primary mt-4"
            @click="toggleAbilitaModifica()"
        >
            <font-awesome-icon :icon="['fas', 'lock']" class="text-xl"/>
            Dati NON modificabili
        </button>
        <button v-else
            class="btn btn-error mt-4"
            @click="toggleAbilitaModifica()"
        >
            <font-awesome-icon :icon="['fas', 'lock-open']" class="text-xl"/>
            Dati modificabili
        </button>
        <button 
            type="submit" 
            class="btn btn-primary mt-4"
            :class="classeModificaAbilitata"
        >
            <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-xl"/>
            Salva Modifiche
        </button>
    </div>
    </form>


</template>


<script setup>
import { computed, reactive, ref } from 'vue';
import { useStore } from 'vuex';
import axios from 'axios';

const props = defineProps({
    site: Object,
});

const store = useStore();

const modificaAbilitata = ref(false);

const classeModificaAbilitata = computed(() => {
    return modificaAbilitata.value ? "" : "btn-disabled";
});

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
  const parsedTimetable = JSON.parse(props.site.timetable.hours_array);
  days.forEach((day, index) => {
    Object.assign(day, parsedTimetable[index]);
  });
}

const weekDays = ["Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica"];

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
    axios.post(`/api/timetable/${props.site.id}`, { timetable_data: days })
        .then(response => {
            console.log('Timetable saved:', response.data);
            toggleAbilitaModifica();
            store.dispatch('flash/queueMessage', { type: 'success', text: 'Orari aggiornati correttamente' });
        })
        .catch(error => {
            console.error('Error saving timetable:', error);
        });
}

</script>