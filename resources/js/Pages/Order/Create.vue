<template>
    <section v-if="site" class="mb-8"> <!-- SERVE per evitare errori in apertura se VUEX STORE non è in sync-->
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
                <span class="font-medium">Commerciale: &nbsp;</span> {{ site.customer.seller.name}}
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
                    {{ site.customer.company_name }}
                  </div>
                  <div>
                    {{ site.customer.legal_address }}
                  </div>
              </div>
            </div>

            <div class="flex content-center">
              <div>
                <div class="flex content-center pr-4">
                  <div class="radial-progress" :style="{ '--value': site.calculated_risk_factor * 100, color: backgroundColor }" role="progressbar">{{ site.calculated_risk_factor * 100 }}%</div>
                  <font-awesome-icon :icon="['fas', buildingFaIcon]" class="text-4xl p-4" :style="{ color: backgroundColor }" />
                </div>
                
              </div>
              <div>
                  <div class="font-medium">
                    Sede di carico
                  </div>
                  <div>
                    {{ site.name }}
                  </div>
                  <div>
                    {{ site.address }}
                  </div>
              </div>
            </div>

            <div class="flex flex-col items-center justify-evenly">
              <div class="flex font-medium">
                Consulente ADR&nbsp;<span v-if="site.has_adr_consultant">PRESENTE</span><span v-else>ASSENTE</span>
              </div>
              <div class="flex">
                <font-awesome-icon :icon="['fas', 'vial-circle-check']" class="text-4xl" :class="site.has_adr_consultant? 'text-success' : 'text-error'" />
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
                <SiteMezziDiSollevamento :site="site" />
            </div>

              <hr class="my-4"/>

              <div class="mt-4">
                <div class="font-medium">Referenti Interni:</div>
                <DataTable
                  :data="site.internal_contacts"
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
                    <label for="expected_withdraw_at" class="label w-64">Data presunta ritiro</label>
                    <VueDatePicker 
                      id="expected_withdraw_at" 
                      v-model="form.expected_withdraw_at" 
                      format="dd/MM/yyyy" 
                      auto-apply 
                      :enableTimePicker="false"
                      @update:model-value="manageDate()">
                    </VueDatePicker> 
                    <div class="input-error" v-if="form.errors.expected_withdraw_at">
                      {{ form.errors.expected_withdraw_at }}
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
            <div v-for="group in groupedItems" :key="group.key" class="mb-4 border border-base-300 rounded-box">
              <div class="px-3 py-2 bg-base-200 rounded-box flex items-center gap-2">
                <div class="badge" :class="group.isUnassigned ? 'badge-warning' : 'badge-primary'">
                  {{ group.isUnassigned ? 'CER non selezionato' : `CER ${group.cerCode}` }}
                </div>
                <div class="font-medium">{{ group.groupLabel }}</div>
                <div class="text-xs opacity-70">({{ group.items.length }} item)</div>
              </div>

              <div class="p-2">
                <ItemRow
                  v-for="row in group.items"
                  :key="row.item.id ?? row.index"
                  v-model:item="form.items[row.index]"
                  :index="row.index"
                  :items="form.items"
                  :cerList="cerList"
                  :holders="holders"
                  :warehouses="warehouses"
                  @remove="removeItem(row.index)"
                />
              </div>
            </div>
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
            title="Contenitori da Ritirare/Consegnare"
            :initialOpen=true
            @register="registerSection"
          >
<!--
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
-->
            <HolderRow
              v-for="(holder, index) in form.holders"
              :key="holder.holder_id ?? index"
              :index="index"
              :holder="holder"
              :holders="holders"
              @change-holder-id="onChangeHolderId"
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
      site: Object,
      customer: Object,
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
    const site = props.site;
    const customer = props.customer;

    const currentDate = computed(
      () => new Date()
    )

const totalItemsWeight = computed(() => {
      if (form.items.length){
        return form.items.reduce((total, element) => total + (Number(element.weight_declared) || 0), 0);
      }
      return 0;
    });

const groupedItems = computed(() => {
  const cerById = new Map((props.cerList || []).map((c) => [Number(c.id), c]));
  const groups = new Map();

  form.items.forEach((item, index) => {
    const cerId = Number(item.cer_code_id || 0);
    const cer = cerById.get(cerId);
    const isUnassigned = !cerId;
    const fallbackLabel = cer ? `${cer.code}.1` : 'Gruppo 1';
    const groupLabel = item.order_item_group_label || fallbackLabel;

    const key = isUnassigned
      ? 'unassigned'
      : `cer:${cerId}|${item.order_item_group_id ? `id:${item.order_item_group_id}` : `label:${groupLabel}`}`;

    if (!groups.has(key)) {
      groups.set(key, {
        key,
        isUnassigned,
        cerCode: cer?.code ?? '-',
        groupLabel: isUnassigned ? 'Assegna CER agli item' : groupLabel,
        items: [],
      });
    }

    groups.get(key).items.push({ item, index });
  });

  return Array.from(groups.values());
});

    // Declare the ref
    const orariApertura = ref('');

    // Watch for changes in site and update orariApertura dynamically
    watchEffect(() => {
      if (site.timetable?.hours_json) {
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
      expected_withdraw_at: null,
      logistics_user_id: user ? user.value.id : null, // Fallback to null if user is not defined
      has_adr_consultant: site.has_adr_consultant ?? '',
      customer_id: site.customer_id,
      site_id: site.id,
      user_id: user ? user.id : null, // Fallback to null if user is not defined
      code: null,
      items: [], // Start with an empty items array
      holders: []
    })
    
    // form ITEMS
const addItem = () => {
  form.items.push({
    cer_code_id: null,
    order_item_group_id: null,
    order_item_group_label: null,
    is_bulk: false,
    holder_id: '',
    holder_quantity: 1,

    // dimensioni custom (solo per holder is_custom)
    custom_l_cm: null,
    custom_w_cm: null,
    custom_h_cm: null,

    description: '',
    weight_declared: null,
    warehouse_id: '',

    adr: false,
    adr_hp: null,
    adr_un_code: null,
    is_adr_total: false,
    has_adr_total_exemption: false,
    has_adr_partial_exemption: false,
  })
}

const removeItem = (index) => {
  form.items.splice(index, 1);
};
   


// form HOLDERS

    const removeHolder = (index) => {
      form.holders.splice(index, 1);
    };
    
    // Watch the form.holders array for changes
    // 1) Ricalcolo automatico: solo filled_holders_count, mai toccare empty_holders_count
    watch(
      () => form.items,
      (items) => {
        // conta per holder_id gli auto (dai items non sfusi)
        const autoCounts = items.reduce((acc, it) => {
          if (!it.is_bulk && it.holder_id && Number(it.holder_quantity) > 0) {
            const id = Number(it.holder_id)
            acc[id] = (acc[id] ?? 0) + Number(it.holder_quantity)
          }
          return acc
        }, {})

        const byId = new Map(form.holders.map(h => [Number(h.holder_id), h]))

        // aggiorna/crea righe per ogni holder auto
        Object.entries(autoCounts).forEach(([idStr, auto]) => {
          const id = Number(idStr)
          let row = byId.get(id)
          if (!row) {
            row = {
              holder_id: id,
              filled_holders_count: 0,
              empty_holders_count: 0,     // ± regolato dall’utente
              total_holders_count: 0,
            }
            form.holders.push(row)
            byId.set(id, row)
          }
          row.filled_holders_count   = Number(auto)
          row.total_holders_count = Number(row.filled_holders_count) + Number(row.empty_holders_count)
        })

        // per righe esistenti non più presenti come auto → azzera filled_holders_count ma non cancellare
        form.holders.forEach(row => {
          if (!autoCounts.hasOwnProperty(row.holder_id)) {
            row.filled_holders_count = 0
            row.total_holders_count = Number(row.filled_holders_count) + Number(row.empty_holders_count)
          }
        })
      },
      { deep: true, immediate: true }
    )

    // 2) Aggiunta manuale di una riga holder (niente duplicati)
    function addHolder(id) {
      const holderId = Number(id)
      const existing = form.holders.find(h => Number(h.holder_id) === holderId)
      if (existing) {
        window.alert("Contenitore già presente: gestisci i totali con 'Vuoti richiesti' senza aggiungere altre righe.")
        // opzionale: highlight
        existing._highlight = true
        setTimeout(() => { existing._highlight = false }, 1200)
        return
      }
      form.holders.push({
        holder_id: holderId,
        filled_holders_count: 0,
        empty_holders_count: 0,
        total_holders_count: 0,
      })
    }

    // 3) Quando nel child cambiano l’holder selezionato, blocca duplicati o accetta
    function onChangeHolderId({ index, newId, oldId }) {
      const newNum = Number(newId)

      // se è già presente in un'altra riga
      if (form.holders.some((h, i) => i !== index && Number(h.holder_id) === newNum)) {
        window.alert("Contenitore già presente: gestisci i totali con 'Vuoti richiesti' senza aggiungere altre righe.")

        // 🔸 reset della riga “sbagliata”
        form.holders[index].holder_id = null     // resetta la select
        form.holders[index]._highlight = true

        // opzionale: feedback visivo temporaneo
        setTimeout(() => { form.holders[index]._highlight = false }, 1200)
        return
      }

      // ✅ caso valido
      form.holders[index].holder_id = newNum
    }


    // 4) Ricalcolo totale quando cambiano vuoti o auto (senza manual_piene)
    watch(
      () => form.holders,
      (rows) => {
        rows.forEach(row => {
          row.empty_holders_count = Number(row.empty_holders_count ?? 0)
          row.filled_holders_count   = Number(row.filled_holders_count ?? 0)
          row.total_holders_count = row.filled_holders_count + row.empty_holders_count
        })
      },
      { deep: true }
    )



  
    const manageDate = (date) => {
      console.log('gestisco la data')
      if(form.expected_withdraw_at){
        console.log('data inserita', form.expected_withdraw_at)
        console.log('giorno della settimana', form.expected_withdraw_at.getDay())
        const hours = site.timetable.hours_json
        console.log('array Orari', hours)
    
        const daySchedule = JSON.parse(hours).find(
            (item) => item.position === form.expected_withdraw_at.getDay()
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
    
    // Watch for changes in site and update form fields accordingly
    watch(site, (newSite) => {
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
        form.logistics_user_id = newUser.id;
      }
    }, { immediate: true });
    
    const create = () => {
      // Ensure filled_holders_count and empty_holders_count is set to 0 if empty
      form.holders.forEach(holder => {
        if (holder.filled_holders_count === "") {
          holder.filled_holders_count = 0;
        }
        if (holder.empty_holders_count === "") {
          holder.empty_holders_count = 0;
        }
      });

      // Before submitting, format the date correctly
      form.requested_at = dayjs(form.requested_at).format('YYYY-MM-DD HH:mm:ss');

      form.is_urgent = form.is_urgent === true;
      form.post(route('order.store'));
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



