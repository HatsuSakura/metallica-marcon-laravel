<template>
    <section v-if="currentSite" class="mb-8"> <!-- SERVE per evitare errori in apertura se VUEX STORE non è in sync-->
      <form @submit.prevent="create">   

        <!-- Intestazione + Buttons to Open/Close All -->
        <div class="mb-4 flex items-center justify-between">
          <div class="flex">
            <button
                type="button"
                class="btn btn-ghost" 
                  @click="goBack()"
            >
              <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
              Torna al Cliente
          </button>
          </div>

          <div class="flex items-center gap-2">
            <div class="text-xl font-medium pr-2">Modulo d'Ordine {{ form.id == null ? 'non ancora salvato' : '#' + String(form.id).padStart('9', '0') + ' del ' + dayjs(form.requested_at).format('YYYY-MM-DD HH:mm:ss') }}</div> 
            <button @click="openAll()" type="button" class="btn btn-sm btn-success">Apri tutto <font-awesome-icon :icon="['fas', 'chevron-down']" /></button>
            <button @click="closeAll()" type="button" class="btn btn-sm btn-error">Chiudi tutto <font-awesome-icon :icon="['fas', 'chevron-up']" /></button>
          </div>
        </div>  
  
        <!-- Static HTML Sections -->
        <div class="accordion-custom">
  
          <!-- SEZIONE 0-->      
          <AccordionRow
            id="0"
            title="Intestazione"
            :initialOpen=true
            @register="registerSection"
          >
            <div class="flex flex-row justify-between content-center">
                
              <div class="flex items-center">
                <span class="font-medium">Data Richiesta: &nbsp;</span>  
                <VueDatePicker v-model="form.requested_at" format="dd/MM/yyyy, HH:mm"></VueDatePicker> 
              </div>

              <div class="flex items-center ">
                <label class="font-medium" for="is_urgent">Urgenza  &nbsp;</label>
                <input v-model="form.is_urgent" id="is_urgent" type="checkbox" class="toggle" />
              </div>

              <div class="flex items-center ">
                <span class="font-medium">Commerciale: &nbsp;</span> {{ currentSite.owner.seller.name}}
              </div>

              <div class="flex items-center ">
                <span class="font-medium">Logistica: &nbsp;</span> {{ user.name }} {{ user.surname }}
              </div>

            </div>
          </AccordionRow>

          <!-- SEZIONE 1-->
          <AccordionRow
            id="1"
            title="Dati Cliente"
            :initialOpen=true
            @register="registerSection"
          >
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
                    {{ currentSite.owner.ragione_sociale }}
                  </div>
                  <div>
                    {{ currentSite.owner.indirizzo_legale }}
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

            <div class="flex flex-col items-center justify-evenly">
              <div class="flex font-medium">
                Consulente ADR&nbsp;<span v-if="currentSite.has_adr_consultant">PRESENTE</span><span v-else>ASSENTE</span>
              </div>
              <div class="flex">
                <font-awesome-icon :icon="['fas', 'vial-circle-check']" class="text-4xl" :class="currentSite.has_adr_consultant? 'text-success' : 'text-error'" />
              </div>
            </div>

          </div>
          </AccordionRow>
  
          <!-- SEZIONE 2-->
          <AccordionRow
            id="2"
            title="Annotazioni Logistiche"
            :initialOpen=true
            @register="registerSection"
          >
            <div>
                <div class="font-medium">Mezzi di sollevamento:</div>
                <SiteMezziDiSollevamento :site="currentSite" />
            </div>

              <hr class="my-4"/>

              <div class="mt-4">
                <div class="font-medium">Referenti Interni:</div>
                <DataTable
                  :data="currentSite.internal_contacts"
                  :columns="columns_internal_contacts"
                  data-paging="false"
                  data-filter="false"
                  data-info="false"
                >
                  <thead>
                      <tr>
                          <th>ID</th>
                          <th>Ruolo</th>
                          <th>Nome</th>
                          <th>Cognome</th>
                          <th>Telefono</th>
                          <th>Cellulare</th>
                          <th>E-Mail</th>
                      </tr>
                  </thead>
                </DataTable>

              </div>

              <div class="mt-4">

                <div class="font-medium">Verifica Data/Orari:</div>

                <div class="mt-2 flex flex-row justify-between align-middle gap-4">
                  <div class="flex w-full">
                    <label for="expected_withdraw_dt" class="label w-64">Data presunta ritiro</label>
                    <VueDatePicker 
                      id="expected_withdraw_dt" 
                      v-model="form.expected_withdraw_dt" 
                      format="dd/MM/yyyy" 
                      auto-apply 
                      :enableTimePicker="false"
                      @update:model-value="manageDate()">
                    </VueDatePicker> 
                    <div class="input-error" v-if="form.errors.expected_withdraw_dt">
                      {{ form.errors.expected_withdraw_dt }}
                    </div>
                  </div>

                  <div class="flex w-full">
                    <label for="orariApertura" class="label w-48">Orari sede in data: </label>
                    <input 
                      v-model.text="orariApertura" 
                      id="orariApertura"
                      type="text" 
                      class="input input-bordered w-full"
                      placeholder="Orari popolati automaticamente"
                    />
                  </div>
                </div>

              </div>
          </AccordionRow>

          <!-- SEZIONE ANNOTAZIONI MAGAZZINO 3-->
           <!-- NON PRESNETI nel modulo di CREAZIONE ORDINE-->


          <!-- SEZIONE 4 ITEMS-->
          <AccordionRow
            id="4"
            :title= "'Descrizione Materiali - Peso totale attuale : ' + totalItemsWeight + ' Kg = ' + totalItemsWeight / 1000 + ' Ton'"
            :initialOpen=true
            @register="registerSection"
          >
          <ItemRow
                v-for="(item, index) in form.items"
                :key="item.id"
                :item="item"
                :index="index"
                :cerList="cerList"
                :holders="holders"
                :warehouses="warehouses"
                @update="updateItem(index, $event)"
                @remove="removeItem(index)"
              />
              <button type="button" @click="addItem" class="btn btn-primary btn-sm">
                <font-awesome-icon :icon="['fas', 'diagram-successor']" class="text-2xl"/>
                <font-awesome-icon :icon="['fas', 'plus']" class="text-2xl"/>
                Materiale
              </button>
              <!--
              <pre>{{ form.items }}</pre>
              -->
          </AccordionRow>

          <!-- SEZIONE 5 HOLDERS-->
          <AccordionRow
            id="5"
            title="Quantitativi Richiesti"
            :initialOpen=true
            @register="registerSection"
          >
          <div class="flex flex-row items-center gap-2 mb-2">
            <div class="flex">
              <button type="button" @click="calculateHolders" class="btn btn-primary btn-circle">
                <font-awesome-icon :icon="['fas', 'calculator']" class="text-2xl"/>
              </button>
            </div>
            <div v-if="form.holders.length > 0" class="flex font-medium">
              Rialcola quantitativi di base
            </div>
            <div v-else class="flex font-medium">
              Calcola quantitativi di base
            </div>
          </div>

            <HolderRow
              v-for="(holder, index) in form.holders"
              :key="holder.id"
              :holder="holder"
              :holders="holders"
              @update="updateHolder(index, $event)"
              @remove="removeHolder(index)"
            />
            
            <button type="button" @click="addHolder" class="btn btn-primary btn-sm">
              <font-awesome-icon :icon="['fas', 'diagram-successor']" class="text-2xl"/>
              <font-awesome-icon :icon="['fas', 'plus']" class="text-2xl"/>
              Elemento contenitore
            </button>

          <!--
            <pre>{{ form.holders }}</pre>
            <pre>{{ holderCounter }}</pre>
          -->
          </AccordionRow>

        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Crea Ordine</button>
        </div>
    
      </form>
    </section>
    </template>
    
    <script setup>
    import { computed, watch, ref, watchEffect , onMounted } from 'vue';
    import { createStore, useStore } from 'vuex';
    import { useForm, usePage } from '@inertiajs/vue3'
    import dayjs from 'dayjs';
    import VueDatePicker from '@vuepic/vue-datepicker';
    import { getIconForSite } from '@/Composables/getIconForSite';
    import { DataTable } from 'datatables.net-vue3';
    import ItemRow from './Components/ItemRow.vue';
    import HolderRow from './Components/HolderRow.vue';
    import AccordionRow from './Components/AccordionRow.vue';
    import { uuid } from '@/utils/uuid';
import SiteMezziDiSollevamento from './Components/SiteMezziDiSollevamento.vue';
 
    
    const props = defineProps({
      vehicles: Array,
      trailers: Array,
      holders: Array,
      drivers: Array,
      cerList: Array,
      warehouses: Array,
      currentUser: Object
    })
    
    const page = usePage();
    const user = computed(
      () => page.props.user
    )
    const goBack = () => {
      window.history.back(); 
    }

    const store = useStore();
    const currentSite = computed(() => store.state.currentSite || null );

    const currentDate = computed(
      () => new Date()
    )

    const totalItemsWeight = computed(() => {
      if (form.items.length){
        return form.items.reduce((total, element) => total + element.weight_declared, 0);
      }
      return 0;
    });

    // Declare the ref
    const orariApertura = ref('');

    // Watch for changes in currentSite and update orariApertura dynamically
    watchEffect(() => {
      if (currentSite.value.timetable?.hours_array) {
        orariApertura.value = 'Orari compilati automaticamente';
      } else {
        orariApertura.value = 'ATTENZIONE: non ci sono orari salvati per questa sede!';
      }
    });

    const columns_internal_contacts = [
      { data: 'id', visible: false },
      { data: 'role' },
      { data: 'name' },
      { data: 'surname' },
      { data: 'phone' },
      { data: 'mobile' },
      { data: 'email' },
    ];

    const form = useForm({
      is_urgent: false,
      requested_at: currentDate.value,
      expected_withdraw_dt: null,
      logistic_id: user ? user.value.id : null, // Fallback to null if user is not defined
      has_adr_consultant: currentSite.value?.has_adr_consultant ?? '',
      customer_id: currentSite.customer_id,
      site_id: currentSite.id,
      user_id: user ? user.id : null, // Fallback to null if user is not defined
      code: null,
      items: [], // Start with an empty items array
      holders: []
    })
    
    // form ITEMS
    const addItem = () => {
      form.items.push({
        //id: uuid.v4(),
        cer_code_id: '', 
        holder_id: '', 
        holder_quantity: '', 
        description: '', 
        weight_declared: '',
        warehouse_id: '',
        adr: null,
        adr_hp: null,
        adr_onu_code: null,
        adr_totale: false,
        adr_esenzione_totale: false,
        adr_esenzione_parziale: false
      });
    };
    
    const updateItem = (index, item) => {
      form.items[index] = item
    };

    const removeItem = (index) => {
      form.items.splice(index, 1);
      updateAdrFieldsVisibility();
    };

    const holderCounter = ref([]); // Use a reactive array to persist data

    const calculateHolders = () => {

      holderCounter.value = [] // svuota l'array

      form.items.forEach((item) => {
        if (item.holder_id && item.holder_quantity) {
          console.log('dentro forEach per ITEMS')
          const existingHolder = holderCounter.value.find(holder => Number(holder.holder_id) === Number(item.holder_id) );
          console.log('existingHolder = ', existingHolder)
          if (existingHolder) {
            existingHolder.holder_piene = Number(existingHolder.holder_piene) + Number(item.holder_quantity);
          } 
          else {
            countHolder(item.holder_id, item.holder_quantity);
          }
        }
      });

      // Algoritmo per rimuovere gli holders modificati o cancellati.
      // Lo tolgo perchè non sa distinguere un holder aggiunto a mano da uno inserito autometicamente
      // eventualmente verificare con la logistica la necessità
      /*
      form.holders.forEach((savedHolder) => {
        const foundInCountHolder = holderCounter.value.find(holder => Number(holder.id) === Number(savedHolder.id) );
        if (!foundInCountHolder) {
          savedHolder.exists = 0
          removeHolder(savedHolder)
        }
      })
      */

      holderCounter.value.forEach((countedHolder) => {
        console.log(countedHolder)
        const existingHolder = form.holders.find(holder => Number(holder.holder_id) === Number(countedHolder.holder_id) );
        if (existingHolder) {
          existingHolder.holder_piene = countedHolder.holder_piene;
        }
        else{
          addHolder(countedHolder.holder_id, countedHolder.holder_piene, countedHolder.holder_vuote)
        }
      })
    }

// form HOLDERS
    const countHolder = (id, piene, vuote) => {
      holderCounter.value.push({ 
        holder_id: id? Number(id) : '', 
        holder_piene: piene? Number(piene) : '', 
        holder_vuote: '', 
        holder_totale: '',
      });
    }

    const addHolder = (id, piene, vuote) => {
      console.log('aggiungo holder', piene, vuote )
      form.holders.push({ 
        holder_id: id? Number(id) : '', 
        holder_piene: piene? Number(piene) : '', 
        holder_vuote: vuote? Number(vuote) : '', 
        holder_totale: '',
      });
    };
    
    const removeHolder = (index) => {
      form.holders.splice(index, 1);
    };
    
    // Watch the form.holders array for changes
    watch(
      () => form.holders, // Watching the holders array
      (newHolders) => {
        // Calculate holder_totale for each holder when holder_piene or holder_vuote changes
        newHolders.forEach((holder) => {
          console.log('dentro watch per HOLDERS', holder)
          const piene = Number(holder.holder_piene) || 0; // Convert to number or use 0 if empty
          const vuote = Number(holder.holder_vuote) || 0;
          holder.holder_totale = piene + vuote; // Calculate holder_totale
        });
      },
      { deep: true } // Ensure deep watching for nested properties
    );
  
    
    const manageDate = (date) => {
      console.log('gestisco la data')
      if(form.expected_withdraw_dt){
        console.log('data inserita', form.expected_withdraw_dt)
        console.log('giorno della settimana', form.expected_withdraw_dt.getDay())
        console.log('array Orari', currentSite.value.timetable.hours_array)
    
        const daySchedule = JSON.parse(currentSite.value.timetable.hours_array).find(
            (item) => item.position === form.expected_withdraw_dt.getDay()
        );
          if (daySchedule) {
            orariApertura.value = daySchedule.orarioApM + " - " + daySchedule.orarioChM + " e " + daySchedule.orarioApP + " - " + daySchedule.orarioChP;
          }
      }
      else{
        console.log('data non inserita o cancellata')
        orariApertura.value = '';
      }
    }
    
    const updateAdrFieldsVisibility = () => {
      form.items.forEach((item, index) => {
        const adrField = document.querySelector(`#adr-fields-${index}`);
        if (adrField) {
          adrField.classList.toggle("hidden", !item.adr);
        }
      });
    };

    watch(
      () => form.items,
      (newItems) => {
        updateAdrFieldsVisibility();
      },
      { deep: true }
    );
    
    // CUSTOM ACCORDION SECTIONS MANAGMENT
    const sections = ref([]); // Will hold section states dynamically
       
    // Register a section
    const registerSection = (section) => {
      if (section && !sections.value.includes(section)) {
        sections.value.push(section);
      }
    };

    // Close all sections
    const closeAll = () => {
      console.log("Closing all sections");
      sections.value.forEach((accordion) => accordion?.closeSection());
    };

    // Open all sections
    const openAll = () => {
      console.log("Opening all sections");
      sections.value.forEach((accordion) => accordion?.openSection());
    };
        
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
        form.logistic_id = newUser.id;
      }
    }, { immediate: true });
    
    const create = () => {
      // Ensure holder_piene and holder_vuote is set to 0 if empty
      form.holders.forEach(holder => {
        if (holder.holder_piene === "") {
          holder.holder_piene = 0;
        }
        if (holder.holder_vuote === "") {
          holder.holder_vuote = 0;
        }
      });

      // Before submitting, format the date correctly
      form.requested_at = dayjs(form.requested_at).format('YYYY-MM-DD HH:mm:ss');

      form.is_urgent = form.is_urgent === true;
      form.post(route('relator.order.store'));
    }
    
    
    
    
    </script>
    
   
    <style>
    
    .custom-style-chooser .vs__dropdown-toggle,
    .custom-style-chooser .vs__dropdown-menu {
      padding: 8px 2px;
      border-radius: 0.375rem;
    }
    
    .custom-style-chooser .vs__search::placeholder {
      color: rgb(107 114 128);
      padding: 8px 2px;
    }
    
    .custom-style-chooser .vs__clear,
    .custom-style-chooser .vs__open-indicator {
      fill: #394066;
    }

    .dp__input{
      padding: 10px 30px;
      border-radius: 0.375rem;
    }
    </style>