<template>
<section v-if="currentSite"> <!-- SERVE per evitare errori in apertura se VUEX STORE non è in sync-->
  <form @submit.prevent="create">



    <div>
      <!-- Intestazione + Buttons to Open/Close All -->
      <div class="mb-4 flex items-center gap-2">
        <div class="text-xl font-medium pr-2">Modulo d'Ordine</div> 
        <button @click="openAll" type="button" class="btn btn-sm btn-success">Apri tutto <font-awesome-icon :icon="['fas', 'chevron-down']" /></button>
        <button @click="closeAll" type="button" class="btn btn-sm btn-error">Chiudi tutto <font-awesome-icon :icon="['fas', 'chevron-up']" /></button>
      </div>  

      <!-- Static HTML Sections -->
      <div class="accordion-custom">

        <!-- SEZIONE 0-->      
        <div class="accordion-custom-item border-b" data-id="section-0">
          <!-- Header -->
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-0')">
            <div>Intestazione</div>
              <div>
                <font-awesome-icon 
                  v-if="isOpen('section-0')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
              </div>
          </div>
          <!-- Body -->
          <div class="accordion-custom-body p-4">

            
            {{ currentDate }}
            {{ form.code == null ? 'ritiro non ancora salvato' : form.code }}
            
            <div class="flex flex-row place-content-between flex-wrap content-stretch w-full">


              <div class="flex items-center">
                <label class="label" for="doubleLoad">Doppio Scarico</label>
                <input v-model="form.doubleLoad" id="doubleLoad" type="checkbox" class="toggle" checked="checked" />
              </div>

              <div class="flex items-center">
                <label class="label" for="temporaryStorage">Stoccaggio</label>
                <input v-model="form.temporaryStorage" id="temporaryStorage" type="checkbox" class="toggle" checked="checked" />
              </div>

              <div class="flex items-center">
                <label class="label" for="urgent">Urgenza</label>
                <input v-model="form.urgent" id="urgent" type="checkbox" class="toggle" checked="checked" />
              </div>

              <div class="flex items-center">
                <label class="label" for="driver">Guidatore</label>
                <select v-model="form.driver_id" id="driver" class="select select-bordered w-full">
                  <option disabled value="">Seleziona un guidatore</option>
                  <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                    {{ driver.name }} <!-- Assuming user model has a 'name' field -->
                  </option>
                </select>
                <div class="input-error" v-if="form.errors.driver_id">
                  {{ form.errors.driver_id }}
                </div>
              </div>

              <div class="flex items-center">
                <label class="label" for="vehicle">Veicolo</label>
                <select v-model="form.vehicle_id" id="vehicle" class="select select-bordered w-full">
                  <option disabled selected value="">Seleziona un veicolo</option>
                  <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                    {{ vehicle.plate }} | {{ vehicle.name }} <!-- Assuming vehicle model has a 'name' field -->
                  </option>
                </select>
                <div class="input-error" v-if="form.errors.vehicle_id">
                  {{ form.errors.vehicle_id }}
                </div>
              </div>

              <div class="flex items-center">
                <label class="label" for="trailer">Rimorchio</label>
                <select v-model="form.trailer_id" id="trailer" class="select select-bordered w-full">
                  <option disabled selected value="">Seleziona un rimorchio</option>
                  <option v-for="trailer in trailers" :key="trailer.id" :value="trailer.id">
                    {{ trailer.plate }} | {{ trailer.name }} <!-- Assuming trailer model has a 'name' field -->
                  </option>
                </select>
                <div class="input-error" v-if="form.errors.trailer_id">
                  {{ form.errors.trailer_id }}
                </div>
              </div>
            </div>   




          </div>
        </div>


        <!-- SEZIONE 1-->
        <div class="accordion-custom-item border-b" data-id="section-1">
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-1')">
            <h3>Dati Cliente</h3>
            <div>
                <font-awesome-icon 
                  v-if="isOpen('section-1')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
            </div>
          </div>
          <div class="accordion-custom-body p-4 bg-gray-100">
            <div class="flex flex-row place-content-between w-full">

              <div class="flex content-center">
                <div>
                  <font-awesome-icon :icon="['fas', 'user-tie']" class="text-4xl p-4"/>
                </div>
                <div>
                    <div class="font-medium">
                      Produttore
                    </div>
                    <div>
                      {{ currentSite.owner.ragioneSociale }}
                    </div>
                    <div>
                      {{ currentSite.owner.indirizzoLegale }}
                    </div>
                </div>
              </div>
              <div class="flex content-center">
                <div>
                  <div class="flex content-center pr-4">
                    <div class="radial-progress" :style="{ '--value': currentSite.fattore_rischio_calcolato * 100, color: backgroundColor }" role="progressbar">{{ currentSite.fattore_rischio_calcolato * 100 }}%</div>
                    <font-awesome-icon :icon="['fas', buildingFaIcon]" class="text-4xl p-4" :style="{ color: backgroundColor }" />
                  </div>
                  
                </div>
                <div>
                    <div class="font-medium">
                      Sede di carico
                    </div>
                    <div>
                      {{ currentSite.denominazione }}
                    </div>
                    <div>
                      {{ currentSite.indirizzo }}
                    </div>
                </div>
              </div>

            </div>
          </div>
        </div>




        <!-- SEZIONE 2-->
        <div class="accordion-custom-item border-b" data-id="section-2">
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-2')">
            <h3>Annotazioni Logistiche</h3>
            <div>
                <font-awesome-icon 
                  v-if="isOpen('section-2')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
              </div>
          </div>
          <div class="accordion-custom-body p-4 bg-gray-100">

            <div class="">
              <label class="label">Data Ritiro</label>
              <VueDatePicker v-model="form.dataRitiro"  class="input input-bordered" @closed="manageDate"></VueDatePicker>
              <div class="input-error" v-if="form.errors.dataRitiro">
                {{ form.errors.dataRitiro }}
              </div>
            </div>

            <div class="mt-4">
              <input 
                v-model.text="form.orariApertura" 
                type="text" 
                class="input input-bordered w-full"
                placeholder="Orari popolati automaticamente"
              />
            </div>


          </div>
        </div>

        <!-- SEZIONE 3-->
        <div class="accordion-custom-item border-b" data-id="section-3">
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-3')">
            <h3>Annotazioni magazzino</h3>
            <div>
                <font-awesome-icon 
                  v-if="isOpen('section-3')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
              </div>
          </div>
          <div class="accordion-custom-body p-4 bg-gray-100">
            <p>Sezione 3 content</p>
          </div>
        </div>

        <!-- SEZIONE 4-->
        <div class="accordion-custom-item border-b" data-id="section-4">
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-4')">
            <h3>Quantitativi Richiesti</h3>
            <div>
                <font-awesome-icon 
                  v-if="isOpen('section-4')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
              </div>
          </div>
          <div class="accordion-custom-body p-4 bg-gray-100">
            <p>Sezione 4 content</p>
          </div>
        </div>


        <!-- SEZIONE 5-->
        <div class="accordion-custom-item border-b" data-id="section-5">
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-5')">
            <h3>Descrizione Materiali</h3>
            <div>
                <font-awesome-icon 
                  v-if="isOpen('section-5')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
              </div>
          </div>
          <div class="accordion-custom-body p-4 bg-gray-100">

            

            
      <!-- Dynamic Rows -->
      <div v-for="(item, index) in form.items" :key="index" class="flex gap-4 items-center mb-2">
        <!-- Dropdown for CerCode -->
         <div :class="getCerStyle(item.cer_code_id)" class="w-full">
          <v-select 
            v-model="item.cer_code_id" 
            :id="'cer-' + index" 
            :options="cerList"
            label="code"
            :reduce="cer => cer.id" 
            :filterable="true" 
            :searchable="true"
            placeholder="Codice CER"
            class="custom-style-chooser w-full"
          >
            <template #option="{ code, description, isDangerous }">
              <span :class="isDangerous == 1 ? 'cer-list-dangerous' : 'cer-list-normal'">{{ code }}</span>
              <br />
              <span class="text-xs"><cite>{{ description }}</cite></span>
            </template>
          </v-select>
          <div class="input-error" v-if="form.errors.items">
            {{ form.errors.items }}
          </div>
        </div>
        
        <label class="label" for="holder">Contenitore</label>

        <!-- Quantity Input -->
        <input 
          v-model.number="item.quantity" 
          type="number" 
          class="input input-bordered w-16"
          placeholder="Qty"
          min="1"
        />

        <select v-model="item.holder_id" id="holder" class="select select-bordered">
          <option disabled value="">Seleziona un contenitore</option>
          <option v-for="holder in holders" :key="holder.id" :value="holder.id">
            {{ holder.name }} <!-- Assuming user model has a 'name' field -->
          </option>
        </select>
        <div class="input-error" v-if="form.errors.items">
          {{ form.errors.items }}
        </div>

        <input 
          v-model.number="item.description" 
          type="text" 
          class="input input-bordered w-full"
          placeholder="Descrizione"
        />

        <input 
          v-model.number="item.weight" 
          type="number" 
          step="0.1"
          class="input input-bordered w-32"
          placeholder="Peso"
        />


          <div class="flex items-center">
            <label class="label" for="adr">ADR</label>
            <input v-model="item.adr" id="adr" type="checkbox" class="toggle" checked="checked" />
          </div>


        <!-- Remove Row Button -->
        <button 
          type="button" 
          @click="removeItem(index)" 
          class="btn btn-error btn-circle"
        >
        <font-awesome-icon :icon="['fas', 'xmark']" />
        </button>
      </div>

      <!-- Add Row Button -->
      <button 
        type="button" 
        @click="addItem" 
        class="btn btn-primary btn-sm"
      >
        <font-awesome-icon :icon="['fas', 'diagram-successor']" class="text-2xl"/>
        Aggiungi riga
      </button>



      <pre>{{ form.items }}</pre>


          </div>
        </div>


        <!-- SEZIONE 6-->
        <div class="accordion-custom-item border-b" data-id="section-6">
          <div class="accordion-custom-header p-4 cursor-pointer flex place-content-between" @click="toggleSection('section-6')">
            <h3>Sezione 6</h3>
            <div>
                <font-awesome-icon 
                  v-if="isOpen('section-6')"
                  :icon="['fas', 'chevron-up']" 
                />
                <font-awesome-icon
                  v-else
                  :icon="['fas', 'chevron-down']" 
                  />
              </div>
          </div>
          <div class="accordion-custom-body p-4 bg-gray-100">
            {{  currentSite  }}
          </div>
        </div>

      </div>
    </div>



    <div class="card bg-base-100 w-full shadow-2xl p-4">



      <div class="">
        <label class="label">Percentuale Residua
          <div class="radial-progress" :style="{ '--value': form.percentualeResidua }" role="progressbar">{{ form.percentualeResidua }}%</div>
        </label>
        <input v-model.number="form.percentualeResidua" type="range" min="0" max="100" step="5"
          class="w-full h-4 bg-gray-200 dark:bg-gray-700 runded-lg appearence-none cursor-pointer" />
        <div class="input-error" v-if="form.errors.percentualeResidua">
          {{ form.errors.percentualeResidua }}
        </div>
      </div>




      <div class="">
        <label class="label" for="insManuale">Inserimento Manuale</label>
        <input v-model="form.insManuale" id="insManuale" type="checkbox" class="toggle" checked="checked" />
      </div>



      <div class="mt-4">
        <button type="submit" class="btn btn-primary">Crea Ritiro</button>
      </div>
    </div>



  </form>
</section>
</template>

<script setup>
import { computed, watch, ref, onMounted } from 'vue';
import { useStore } from 'vuex';
import { useForm, usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs';
    import VueDatePicker from '@vuepic/vue-datepicker';
    import '@vuepic/vue-datepicker/dist/main.css';
import "vue-select/dist/vue-select.css";
import vSelect from "vue-select";
import { getIconForSite } from '@/Composables/getIconForSite';

const props = defineProps({
  vehicles: Array,
  trailers: Array,
  holders: Array,
  drivers: Array,
  cerList: Array,
  currentUser: Object
})

const page = usePage()
const store = useStore();
const currentSite = computed(() => store.state.currentSite || null );

const user = computed(
  () => page.props.user
)

const currentDate = computed(
  () => new Date().toLocaleString()
)


const form = useForm({
  dataRitiro: null,
  orariApertura: null,
  percentualeResidua: 0,
  customer_id: currentSite.customer_id,
  site_id: currentSite.id,
  user_id: user ? user.id : null, // Fallback to null if user is not defined
  vehicle_id: null,
  trailer_id: null,
  driver_id: null,
  insManuale: true,
  code: null,
  items: [] // Start with an empty items array
})

// Declare separate constants for each value
const buildingType = ref(null);
const buildingFaIcon = ref(null);
const backgroundColor = ref(null);
const borderColor = ref(null);

// Watch for changes in currentSite and update form fields accordingly
watch(currentSite, (newSite) => {
  if (newSite) {
    form.customer_id = newSite.customer_id;
    form.site_id = newSite.id;
    const icons = getIconForSite(newSite);
          buildingType.value = icons.buildingType.value;
          buildingFaIcon.value = icons.buildingFaIcon.value;
          backgroundColor.value = icons.backgroundColor.value;
          borderColor.value = icons.borderColor.value;
  }
}, { immediate: true }); // This ensures it also runs initially


watch(user, (newUser) => {
  if (newUser) {
    form.user_id = newUser.id;
  }
}, { immediate: true });

const create = () => {
  // Before submitting, format the date correctly
  form.dataRitiro = dayjs(form.dataRitiro).format('YYYY-MM-DD HH:mm:ss');
  form.post(route('relator.withdraw.store'));
}



const addItem = () => {
  form.items.push({ 
    cer_code_id: null, 
    holder_id: null, 
    quantity: 1, 
    description: null, 
    weight: null,
    adr: null
  });
};

const removeItem = (index) => {
  form.items.splice(index, 1);
};

// Computed function to dynamically assign a class
const getCerStyle = (cerId) => {
  if (!cerId) return ""; // Return an empty class for undefined or null values
  const cer = props.cerList.find((c) => c.id === cerId);
  return cer?.isDangerous === 1 ? "cer-selected-dangerous" : "cer-selected-normal";
};





const manageDate = (date) => {
  console.log('gestisco la data')
  if(form.dataRitiro){
    console.log('data inserita', form.dataRitiro)
    console.log('giorno della settimana', form.dataRitiro.getDay())
    console.log('array Orari', currentSite.value.timetable.hours_array)

    const daySchedule = JSON.parse(currentSite.value.timetable.hours_array).find(
        (item) => item.position === form.dataRitiro.getDay()
    );
      if (daySchedule) {
        form.orariApertura = daySchedule.orarioApM + " - " + daySchedule.orarioChM + " e " + daySchedule.orarioApP + " - " + daySchedule.orarioChP;
      }


  }
  else{
    console.log('data non inserita o NON modificata')
  }
}


// CUSTOM ACCORDION SECTIONS MANAGMENT
const sections = ref({}); // Will hold section states dynamically

const toggleSection = (id) => {
  console.log("sections " , sections.value[id])
  const section = sections.value[id];
  if (section) {
    section.isOpen = !section.isOpen;
    section.body.classList.toggle("hidden", !section.isOpen);
  }
};

const isOpen = (id) => {
  const section = sections.value[id];
  if (section) {
    return section.isOpen;
  }
}

const openAll = () => {
  Object.values(sections.value).forEach((section) => {
    section.isOpen = true;
    section.body.classList.remove("hidden");
  });
};

const closeAll = () => {
  Object.values(sections.value).forEach((section) => {
    section.isOpen = false;
    section.body.classList.add("hidden");
  });
};

onMounted(() => {
  // Initialize sections dynamically based on DOM structure
  const items = document.querySelectorAll(".accordion-custom-item");
  items.forEach((item) => {
    const id = item.dataset.id;
    const body = item.querySelector(".accordion-custom-body");
    if (id && body) {
      sections.value[id] = {
        isOpen: !body.classList.contains("hidden"),
        body,
      };
    }
  });
});






</script>

<style scoped>
.cer-list-dangerous {
  color:red;
  font-weight:bold;
}

.cer-selected-dangerous {
  background-color: #ffe6e6; /* Light red background for dangerous items */
}

.accordion-custom-header{
  background-color: #f8f8f8;
}

.accordion-custom-body{
  background-color: #fff;
}
</style>

<style>

.custom-style-chooser .vs__dropdown-toggle,
.custom-style-chooser .vs__dropdown-menu {
  padding: 8px 2px;
}

.custom-style-chooser .vs__search::placeholder {
  color: rgb(107 114 128);
  padding: 8px 2px;
}

.custom-style-chooser .vs__clear,
.custom-style-chooser .vs__open-indicator {
  fill: #394066;
}
</style>