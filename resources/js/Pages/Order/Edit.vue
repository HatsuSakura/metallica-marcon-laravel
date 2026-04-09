<template>
    <section v-if="currentSite" class="mb-8"> <!-- SERVE per evitare errori in apertura se VUEX STORE non è in sync-->
      <form @submit.prevent="edit('save_exit')">   

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
            <div v-if="!form.id" class="text-xl font-medium pr-2">Modulo d'Ordine non ancora salvato </div>
            <div v-else class="text-xl font-medium pr-2">
              Ordine {{ props.order.legacy_code ?? ('#' + form.id) }} del {{ dayjs(form.requested_at).format('YYYY-MM-DD HH:mm:ss') }}
            </div> 
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
                <VueDatePicker v-model="requestedAtPicker" format="dd/MM/yyyy, HH:mm"></VueDatePicker> 
              </div>

              <div class="flex items-center ">
                <label class="font-medium" for="is_urgent">Urgenza  &nbsp;</label>
                <input v-model="form.is_urgent" id="is_urgent" type="checkbox" class="toggle" />
              </div>

              <div class="flex items-center ">
                <span class="font-medium">Commerciale: &nbsp;</span> {{ currentSite.customer.seller.name}}
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
                    {{ currentSite.customer.company_name }}
                  </div>
                  <div>
                    {{ currentSite.customer.legal_address }}
                  </div>
              </div>
            </div>

            <div class="flex content-center">
              <div>
                <div class="flex content-center pr-4">
                  <div class="radial-progress" :style="{ '--value': riskPercent, color: backgroundColor }" role="progressbar">{{ riskPercent }}%</div>
                  <font-awesome-icon :icon="['fas', buildingFaIcon]" class="text-4xl p-4" :style="{ color: backgroundColor }" />
                </div>
                
              </div>
              <div>
                  <div class="font-medium">
                    Sede di carico
                  </div>
                  <div>
                    {{ currentSite.name }}
                  </div>
                  <div>
                    {{ currentSite.address }}
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
                <div class="flex flex-row justify-between">
                  <div class="flex align-middle gap-2">Muletto:
                    <font-awesome-icon v-if="currentSite.has_muletto":icon="['fas', 'thumbs-up']" class="text-2xl text-success"/>
                    <font-awesome-icon v-else :icon="['fas', 'thumbs-down']" class="text-2xl text-error"/>
                  </div>
                  <div class="flex align-middle gap-2">Transpallet Elettrico:
                    <font-awesome-icon v-if="currentSite.has_electric_pallet_truck":icon="['fas', 'thumbs-up']" class="text-2xl text-success"/>
                    <font-awesome-icon v-else :icon="['fas', 'thumbs-down']" class="text-2xl text-error"/>
                  </div>
                  <div class="flex align-middle gap-2">Transpallet Manuale:
                    <font-awesome-icon v-if="currentSite.has_manual_pallet_truck":icon="['fas', 'thumbs-up']" class="text-2xl text-success"/>
                    <font-awesome-icon v-else :icon="['fas', 'thumbs-down']" class="text-2xl text-error"/>
                  </div>
                </div>
                <div>Altro: 
                  <span v-if="currentSite.other_machines">{{ currentSite.other_machines }}</span>
                  <span v-else>Nessuna indicazione specifica</span>
                </div>
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
                    <label for="expected_withdraw_at" class="label w-64">Data presunta ritiro</label>
                    <VueDatePicker 
                      id="expected_withdraw_at" 
                      v-model="expectedWithdrawAtPicker" 
                      format="dd/MM/yyyy, HH:mm" 
                      auto-apply 
                      :enableTimePicker="true"
                      :disabled="hasFixedWithdrawAt">
                    </VueDatePicker> 
                    <div class="input-error" v-if="form.errors.expected_withdraw_at">
                      {{ form.errors.expected_withdraw_at }}
                    </div>
                  </div>

                  <div class="flex w-full">
                    <label for="orariApertura" class="label w-48">Orari sede: </label>
                    <input 
                      v-model.text="orariApertura" 
                      id="orariApertura"
                      type="text" 
                      class="input input-bordered w-full"
                      placeholder="Orari popolati automaticamente"
                    />
                  </div>
                </div>

                <div class="mt-4 flex flex-row items-center gap-4">
                  <label for="fixed_withdraw_toggle" class="label w-64">Data fissa</label>
                  <input
                    id="fixed_withdraw_toggle"
                    :checked="hasFixedWithdrawAt"
                    type="checkbox"
                    class="toggle"
                    @change="toggleFixedWithdraw($event.target.checked)"
                  />
                  <span class="text-sm opacity-70 flex-1">Se attiva, blocca la data presunta e la sincronizza con la data fissa.</span>
                  <div class="flex items-center gap-2 flex-1">
	                    <VueDatePicker
	                      id="fixed_withdraw_at"
		                      v-model="fixedWithdrawAtPicker"
		                      format="dd/MM/yyyy, HH:mm"
                      auto-apply
                      :enableTimePicker="true"
	                      :disabled="!hasFixedWithdrawAt"
	                    />
                  </div>
                </div>
                <div class="mt-1">
                  <div class="input-error" v-if="form.errors.fixed_withdraw_at">
                    {{ form.errors.fixed_withdraw_at }}
                  </div>
                </div>

              </div>
          </AccordionRow>

          <!-- SEZIONE 3 NOTE ORDINE -->
          <AccordionRow
            id="3"
            title="Note Ordine"
            :initialOpen=true
            @register="registerSection"
          >
            <div class="flex flex-row gap-4 justify-stretch w-full">
              <div class="w-1/2">
                <label class="label">Annotazioni aggiuntive per il ritiro</label>
                <textarea
                  v-model="form.notes"
                  class="textarea textarea-bordered w-full"
                  rows="4"
                  placeholder="Inserisci eventuali note operative per l'ordine"
                />
                <div class="input-error" v-if="form.errors.notes">
                  {{ form.errors.notes }}
                </div>
              </div>

              <div class="w-1/2">
                <label class="label">Note di questa sede</label>
                <textarea
                  :value="siteNotesText"
                  class="textarea textarea-bordered w-full"
                  rows="4"
                  readonly
                />
              </div>
            </div>
          </AccordionRow>


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
            :title="'Quantitativi Richiesti: ' + form.holders.length + ' tipologia/i di contenitore'"
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

        <div class="mt-4 flex justify-end gap-2">
          <button
            type="button"
            class="btn btn-outline"
            :disabled="form.processing"
            @click="edit('save_stay')"
          >
            <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-lg"/>
            Salva
          </button>
          <button type="submit" class="btn btn-primary" :disabled="form.processing">
            Salva & Esci
          </button>
        </div>

        <AuditCollapse
          :audits="props.audits || []"
          :is-admin="Boolean(user?.is_admin)"
          :field-labels="auditFieldLabels"
        />
    
      </form>
    </section>
    </template>
    
    <script setup>
    import { computed, watch, ref, watchEffect , onMounted } from 'vue';
    import { createStore, useStore } from 'vuex';
    import { useForm, usePage, router } from '@inertiajs/vue3'
	    import dayjs from 'dayjs';
	    import VueDatePicker from '@vuepic/vue-datepicker';
	    import { getIconForSite } from '@/Composables/getIconForSite';
	    import { DataTable } from 'datatables.net-vue3';
	import { formatServerDateTime, parseServerDateTime } from '@/utils/serverDateTime';
	import ItemRow from './Components/ItemRow.vue';
import HolderRow from './Components/HolderRow.vue';
import AccordionRow from './Components/AccordionRow.vue';
import AuditCollapse from '@/Components/AuditCollapse.vue';
import { uuid } from '@/utils/uuid';
 
    
    const props = defineProps({
        order: Object,
        order_items: Array,
        order_holders: Array,
        site: Object,
        vehicles: Array,
        trailers: Array,
        holders: Array,
        drivers: Array,
        cerList: Array,
        warehouses: Array,
        currentUser: Object,
        audits: Array,
    })
    
    const page = usePage();
    const user = computed(
      () => page.props.user
    )
    const auditFieldLabels = {
      customer_id: 'Cliente',
      site_id: 'Sede',
      logistics_user_id: 'Operatore Logistica',
      journey_id: 'Viaggio',
      status: 'Stato ordine',
      documents_status: 'Stato documenti',
      requested_at: 'Data richiesta',
      expected_withdraw_at: 'Data presunta ritiro',
      fixed_withdraw_at: 'Data fissa',
      actual_withdraw_at: 'Data ritiro effettiva',
      is_urgent: 'Urgenza',
      cargo_location: 'Posizione carico',
      has_crane: 'Uso gru',
      crane_operator_user_id: 'Operatore gru',
      machinery_time_minutes: 'Tempo macchinario',
      notes: 'Note',
    }
    const goBack = () => {
      window.history.back(); 
    }

    const store = useStore();
    const currentSite = computed(() => props.site );
    const siteNotesText = computed(() =>
      currentSite.value?.notes?.trim()
        ? currentSite.value.notes
        : 'nessuna nota particoalre per questa sede'
    );
const totalItemsWeight = computed(() => {
      if (form.items.length){
        return form.items.reduce((total, element) => total + element.weight_declared, 0);
      }
      return 0;
    });

const riskPercent = computed(() => {
  const value = Number(currentSite.value?.calculated_risk_factor ?? 0) * 100;
  return Number.isFinite(value) ? Number(value.toFixed(1)) : 0;
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
	    // Watch for changes in currentSite and update orariApertura dynamically
	    watchEffect(() => {
      if (currentSite.value.timetable?.hours_json) {
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
	      id: props.order.id,
	      is_urgent: Boolean(props.order.is_urgent),
	      requested_at: formatServerDateTime(parseServerDateTime(props.order.requested_at)) ?? formatServerDateTime(new Date()),
	      expected_withdraw_at: formatServerDateTime(parseServerDateTime(props.order.expected_withdraw_at)),
          fixed_withdraw_at: formatServerDateTime(parseServerDateTime(props.order.fixed_withdraw_at)),
	      notes: props.order.notes ?? '',
	      post_action: 'save_exit',
	      logistics_user_id: props.order.logistics_user_id ? props.order.logistics_user_id : user ? user.value.id : null, // Fallback to null if user is not defined
      has_adr_consultant: currentSite.value?.has_adr_consultant ?? '',
      customer_id: currentSite.customer_id,
      site_id: props.site.id,
      user_id: user ? user.id : null, // Fallback to null if user is not defined
      code: null,
      items: props.order_items.map((item) => ({
        ...item,
        order_item_group_label: item.order_item_group?.label ?? null,
      })),
	      holders: props.order_holders,
	    })

	      const hasFixedWithdrawAt = computed(() => form.fixed_withdraw_at !== null);

		      const normalizeDateOrNull = (value) => {
		        if (!value) return null;

		        const normalized = value instanceof Date ? value : parseServerDateTime(value);

		        return !normalized || Number.isNaN(normalized.getTime())
                  ? null
                  : formatServerDateTime(normalized);
		      };

              const requestedAtPicker = computed({
                get: () => parseServerDateTime(form.requested_at),
                set: (value) => {
                  form.requested_at = normalizeDateOrNull(value);
                },
              });

              const expectedWithdrawAtPicker = computed({
                get: () => parseServerDateTime(form.expected_withdraw_at),
                set: (value) => {
                  form.expected_withdraw_at = normalizeDateOrNull(value);
                  manageExpectedDate();
                },
              });

              const fixedWithdrawAtPicker = computed({
                get: () => parseServerDateTime(form.fixed_withdraw_at),
                set: (value) => {
                  form.fixed_withdraw_at = normalizeDateOrNull(value);
                  syncExpectedWithFixed();
                },
              });

      const syncExpectedWithFixed = () => {
        if (!hasFixedWithdrawAt.value || !form.fixed_withdraw_at) return;

        form.expected_withdraw_at = normalizeDateOrNull(form.fixed_withdraw_at);
        manageExpectedDate();
      };

      const toggleFixedWithdraw = (enabled) => {
        if (!enabled) {
          form.fixed_withdraw_at = null;
          return;
        }

	        form.fixed_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at) ?? formatServerDateTime(new Date());
	        syncExpectedWithFixed();
	      };

	    // form ITEMS
    const addItem = () => {
      form.items.push({
        id: uuid.v4(),
        cer_code_id: '', 
        order_item_group_id: null,
        order_item_group_label: null,
        is_bulk: false,
        holder_id: '', 
        holder_quantity: '', 
        description: '', 
        weight_declared: '',
        warehouse_id: '',
        adr: null,
        adr_hp: null,
        adr_un_code: null,
        is_adr_total: false,
        has_adr_total_exemption: false,
        has_adr_partial_exemption: false
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
            existingHolder.filled_holders_count = Number(existingHolder.filled_holders_count) + Number(item.holder_quantity);
          } 
          else {
            countHolder(item.holder_id, item.holder_quantity);
          }
        }
      });

      // Algoritmo per rimuovere gli holders modificati o cancellati.
      // Lo tolgo perchè non sa distinguere un holder aggiunto a mano da uno inserito automaticamente
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
          existingHolder.filled_holders_count = countedHolder.filled_holders_count;
        }
        else{
          addHolder(countedHolder.holder_id, countedHolder.filled_holders_count, countedHolder.empty_holders_count)
        }
      })
    }

// form HOLDERS
    const countHolder = (holder_id, piene, vuote) => {
      holderCounter.value.push({ 
        holder_id: holder_id? Number(holder_id) : '', 
        filled_holders_count: piene? Number(piene) : '', 
        empty_holders_count: '', 
        total_holders_count: '',
      });
    }

    const addHolder = (holder_id, piene, vuote) => {
      form.holders.push({ 
        holder_id: holder_id? Number(holder_id) : '', 
        filled_holders_count: piene? Number(piene) : '', 
        empty_holders_count: vuote? Number(vuote) : '', 
        total_holders_count: '',
      });
    };
    
    const removeHolder = (index) => {
      form.holders.splice(index, 1);
    };
    
    // Watch the form.holders array for changes
    watch(
      () => form.holders, // Watching the holders array
      (newHolders) => {
        // Calculate totale for each holder when filled_holders_count or empty_holders_count changes
        newHolders.forEach((holder) => {
          const piene = Number(holder.filled_holders_count) || 0; // Convert to number or use 0 if empty
          const vuote = Number(holder.empty_holders_count) || 0;
          holder.totale = piene + vuote; // Calculate totale
        });
      },
      { deep: true } // Ensure deep watching for nested properties
    );
  
    
	    onMounted(() => {
	      manageExpectedDate();
	      parseBooleanValues();
	    });

    const parseBooleanValues = () => {
      form.items.forEach((item) => {
        item.adr = item.adr === 1;
        item.is_adr_total = item.is_adr_total === 1;
        item.has_adr_total_exemption = item.has_adr_total_exemption === 1;
        item.has_adr_partial_exemption = item.has_adr_partial_exemption === 1;
      });
    }

	    const manageExpectedDate = () => {
//	      console.log('gestisco la data')
	          form.expected_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at);
              const expectedWithdrawAt = parseServerDateTime(form.expected_withdraw_at);
		      if(expectedWithdrawAt){
//	        console.log('data inserita', form.expected_withdraw_at)
//	        console.log('giorno della settimana', form.expected_withdraw_at.getDay())
	        const hours = currentSite.value.timetable.hours_json
//        console.log('array Orari', hours)
	    
	        const daySchedule = JSON.parse(hours).find(
	            (item) => item.position === expectedWithdrawAt.getDay()
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

    const isBlank = (value) => value === null || value === undefined || `${value}`.trim() === '';
    const isTruthy = (value) => value === true || value === 1 || value === '1';
    const cerById = computed(() => {
      const map = new Map();
      (props.cerList || []).forEach((cer) => map.set(Number(cer.id), cer));
      return map;
    });

    const runOrderPrecheck = () => {
      const errors = {};

      if (!form.expected_withdraw_at) {
        errors.expected_withdraw_at = 'Data presunta ritiro obbligatoria.';
      }

      (form.items || []).forEach((item, index) => {
        const prefix = `items.${index}`;
        const cer = cerById.value.get(Number(item.cer_code_id));
        const isBulk = isTruthy(item.is_bulk);
        const adrActive = isTruthy(item.adr);

        if (isBlank(item.cer_code_id)) {
          errors[`${prefix}.cer_code_id`] = 'CER obbligatorio.';
        }
        if (!isBulk && isBlank(item.holder_id)) {
          errors[`${prefix}.holder_id`] = 'Tipo contenitore obbligatorio se non sfuso.';
        }
        if (isBlank(item.description)) {
          errors[`${prefix}.description`] = 'Descrizione obbligatoria.';
        }
        if (isBlank(item.weight_declared)) {
          errors[`${prefix}.weight_declared`] = 'Peso stimato obbligatorio.';
        }
        if (isBlank(item.warehouse_id)) {
          errors[`${prefix}.warehouse_id`] = 'Magazzino obbligatorio.';
        }
        if ((cer?.is_dangerous ?? false) && isBlank(item.adr_hp)) {
          errors[`${prefix}.adr_hp`] = 'HP obbligatorio per CER pericoloso.';
        }
        if (adrActive) {
          if (isBlank(item.adr_un_code)) {
            errors[`${prefix}.adr_un_code`] = 'Codice UN obbligatorio quando ADR è attivo.';
          }
          const hasAdrFlag = isTruthy(item.is_adr_total)
            || isTruthy(item.has_adr_total_exemption)
            || isTruthy(item.has_adr_partial_exemption);
          if (!hasAdrFlag) {
            errors[`${prefix}.adr`] = 'Se ADR è attivo, seleziona almeno una modalità ADR.';
          }
        }
      });

      if (Object.keys(errors).length > 0) {
        form.setError(errors);
        const firstError = Object.values(errors)[0] ?? "Verifica i campi obbligatori dell'ordine.";
        if (store?.dispatch) {
          store.dispatch('flash/queueMessage', { type: 'error', text: firstError });
        } else {
          window.alert(firstError);
        }
        return false;
      }
      return true;
    };
    
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
        form.logistics_user_id = newUser.id;
      }
    }, { immediate: true });
    
	    const edit = (postAction = 'save_exit') => {
      form.clearErrors();
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
		      form.requested_at = normalizeDateOrNull(form.requested_at);
		      form.expected_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at);
	          form.fixed_withdraw_at = normalizeDateOrNull(form.fixed_withdraw_at);
		      form.post_action = postAction;

          if (!runOrderPrecheck()) {
            return;
          }

		      form.put(route('order.update', {order: props.order.id }));
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
