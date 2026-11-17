<template>
    <AppPage v-if="currentSite" :title="`Modifica ordine #${props.order.id}`">
<template #back><AppAction :as="Link" :href="route('customer.show', {customer:currentSite.customer_id})"><font-awesome-icon :icon="['fas','arrow-left']" aria-hidden="true" />Torna al cliente</AppAction></template> <!-- SERVE per evitare errori in apertura se VUEX STORE non è in sync-->
      <template #actions><SectionVisibilityActions :disabled="form.processing" @open-all="openAll" @close-all="closeAll" /></template>
      <RegistryForm :form="form" editing :ignored-fields="['post_action']" v-slot="{busy,canSubmit}" @submit="edit('save_exit')">

        <!-- Static HTML Sections -->
        <div class="accordion-custom">

          <!-- SEZIONE 0-->
          <AccordionRow
            id="0"
            title="Dati ordine"
            :initialOpen=true
            @register="registerSection" @unregister="unregisterSection"
          >
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">

              <div class="flex min-w-0 items-center gap-2">
                <label for="dp-input-requested_at" class="shrink-0 text-sm font-semibold">Richiesta</label>
                <VueDatePicker class="min-w-0 flex-1" style="max-width: 14rem" uid="requested_at" name="requested_at" :aria-labels="{input:'Data richiesta'}" v-model="requestedAtPicker" format="dd/MM/yyyy, HH:mm"></VueDatePicker>
              </div>

              <div class="flex items-center ">
                <label class="text-sm font-semibold" for="is_urgent">Urgenza  &nbsp;</label>
                <input v-model="form.is_urgent" id="is_urgent" type="checkbox" class="toggle" />
              </div>

              <div class="flex items-center ">
                <span class="text-sm font-semibold">Commerciale: &nbsp;</span> {{ currentSite.customer?.seller?.name || 'Non assegnato'}}
              </div>

              <div class="flex items-center ">
                <span class="text-sm font-semibold">Logistica: &nbsp;</span> {{ user.name }} {{ user.surname }}
              </div>

            </div>
          </AccordionRow>

          <AccordionRow id="1" title="Cliente e sede" :initial-open="true" @register="registerSection" @unregister="unregisterSection"><OrderSiteSummary :site="currentSite" /></AccordionRow>
          <!-- SEZIONE 2-->
          <AccordionRow
            id="2"
            title="Informazioni per il ritiro"
            :initialOpen=true
            @register="registerSection" @unregister="unregisterSection"
          >
            <div class="space-y-2"><h3 class="font-semibold">Mezzi di sollevamento</h3><SiteMezziDiSollevamento :site="currentSite" /></div>
              <hr class="my-4"/>

              <div class="space-y-1"><h3 class="text-sm font-semibold">Note della sede</h3><p class="whitespace-pre-line break-words text-sm">{{ siteNotesText }}</p></div>
<div class="mt-4">
                <div class="font-medium">Referenti Interni:</div>
                <SiteInternalContacts :contacts="currentSite.internal_contacts" />
              </div>

              <div class="mt-4">

                <div class="font-medium">Verifica Data/Orari:</div>

                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                  <div class="min-w-0">
                    <label for="dp-input-expected_withdraw_at" class="label">Data presunta ritiro</label>
                    <VueDatePicker
                      uid="expected_withdraw_at" name="expected_withdraw_at"
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

                  <div class="min-w-0">
                    <label for="orariApertura" class="label">Orari sede: </label>
                    <output id="orariApertura" class="block min-h-12 rounded-lg border border-base-300 px-3 py-2 text-sm">{{ orariApertura }}</output>
                  </div>
                </div>

                <div class="mt-4 grid min-w-0 grid-cols-1 items-start gap-3 md:grid-cols-2">
                    <div class="space-y-1"><label for="fixed_withdraw_toggle" class="flex min-h-12 w-fit items-center gap-2 text-sm font-semibold"><input id="fixed_withdraw_toggle" :checked="hasFixedWithdrawAt" type="checkbox" class="toggle toggle-primary" @change="toggleFixedWithdraw($event.target.checked)" />Data fissa</label><p class="text-sm">Se attiva, la data presunta coincide con la data fissa.</p></div>
                    <div class="min-w-0"><label for="dp-input-fixed_withdraw_at" class="label">Data e ora fisse</label>
<VueDatePicker
	                      uid="fixed_withdraw_at" name="fixed_withdraw_at"
		                      v-model="fixedWithdrawAtPicker"
		                      format="dd/MM/yyyy, HH:mm"
                      auto-apply
                      :enableTimePicker="true"
	                      :disabled="!hasFixedWithdrawAt"
	                    /></div>
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
            @register="registerSection" @unregister="unregisterSection"
          >
            <div class="grid grid-cols-1 gap-3">
              <div class="min-w-0">
                <label for="order-notes" class="label">Annotazioni aggiuntive per il ritiro</label>
                <textarea
                  v-model="form.notes" id="order-notes" name="notes" :aria-invalid="!!form.errors.notes"
                  class="textarea textarea-bordered w-full"
                  rows="4"
                  placeholder="Inserisci eventuali note operative per l'ordine"
                />
                <div class="input-error" v-if="form.errors.notes">
                  {{ form.errors.notes }}
                </div>
              </div>


            </div>
          </AccordionRow>


          <!-- SEZIONE 4 ITEMS-->
          <AccordionRow
            id="4"
            :title="'Materiali · ' + totalItemsWeight + ' kg (' + totalItemsWeight / 1000 + ' t)'"
            :initialOpen=true
            @register="registerSection" @unregister="unregisterSection"
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
              <button type="button" @click="addItem" class="btn btn-primary btn-outline">
                <font-awesome-icon :icon="['fas', 'plus']" class="text-2xl"/>
                Aggiungi materiale / CER
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
            @register="registerSection" @unregister="unregisterSection"
          >
          <div class="flex flex-row items-center gap-2 mb-2">
            <div class="flex">
              <button type="button" @click="calculateHolders" class="btn btn-primary btn-outline btn-circle">
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

            <button type="button" @click="addHolder(null)" class="btn btn-primary btn-outline">
              <font-awesome-icon :icon="['fas', 'plus']" class="text-2xl"/>
              Aggiungi contenitore
            </button>

          <!--
            <pre>{{ form.holders }}</pre>
            <pre>{{ holderCounter }}</pre>
          -->
          </AccordionRow>

        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2"><AppAction :as="Link" variant="cancel" :disabled="form.processing" :href="route('customer.show', {customer:currentSite.customer_id})">Annulla</AppAction>
          <AppAction
            type="button"

            :disabled="!canSubmit"
            @click="edit('save_stay')"
           variant="confirm" :busy="form.processing">
            <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-lg"/>
            Salva
          </AppAction>
          <AppAction type="submit" :disabled="!canSubmit" variant="confirm" :busy="form.processing">
            Salva e torna all’elenco
          </AppAction>
        </div>

        <AuditCollapse
          :audits="props.audits || []"
          :is-admin="Boolean(user?.is_admin)"
          :field-labels="auditFieldLabels"
        />

      </RegistryForm>
    </AppPage>
    </template>

    <script setup>
import {siteOpeningHours} from '@/utils/siteOpeningHours';
import {floorToFiveMinutes} from '@/utils/dateTimeProposal'
import AppPage from '@/Components/UI/AppPage.vue';
import AppAction from '@/Components/UI/AppAction.vue';
import RegistryForm from '@/Components/Registry/RegistryForm.vue';
import SectionVisibilityActions from '@/Components/UI/SectionVisibilityActions.vue';
import OrderSiteSummary from '@/Components/Site/OrderSiteSummary.vue';
import {useDisclosureSections} from '@/Composables/useDisclosureSections';
import {Link} from '@inertiajs/vue3';

import { groupOrderItems } from '@/utils/orderItemGroups';
    import { computed, watch, ref, watchEffect , onMounted } from 'vue';
    import { createStore, useStore } from 'vuex';
    import { useForm, usePage, router } from '@inertiajs/vue3'
	    import dayjs from 'dayjs';
	    import VueDatePicker from '@/Components/UI/AppDateTimePicker.vue';
	    import SiteMezziDiSollevamento from './Components/SiteMezziDiSollevamento.vue';
	    import SiteInternalContacts from '@/Components/SiteInternalContacts.vue';
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
        : 'Nessuna nota particolare per questa sede'
    );
const totalItemsWeight = computed(() => {
      if (form.items.length){
        return form.items.reduce((total, element) => total + (Number(element.weight_declared) || 0), 0);
      }
      return 0;
    });

const riskPercent = computed(() => {
  const value = Number(currentSite.value?.calculated_risk_factor ?? 0) * 100;
  return Number.isFinite(value) ? Number(value.toFixed(1)) : 0;
});

const groupedItems = computed(() => groupOrderItems(form.items, props.cerList || []));

    const orariApertura = computed(() => siteOpeningHours(currentSite.value, form.expected_withdraw_at));

    const form = useForm({
	      id: props.order.id,
	      is_urgent: Boolean(props.order.is_urgent),
	      requested_at: formatServerDateTime(parseServerDateTime(props.order.requested_at)) ?? formatServerDateTime(floorToFiveMinutes()),
	      expected_withdraw_at: formatServerDateTime(parseServerDateTime(props.order.expected_withdraw_at)),
          fixed_withdraw_at: formatServerDateTime(parseServerDateTime(props.order.fixed_withdraw_at)),
	      notes: props.order.notes ?? '',
	      post_action: 'save_exit',
	      logistics_user_id: props.order.logistics_user_id ? props.order.logistics_user_id : user ? user.value.id : null, // Fallback to null if user is not defined
      has_adr_consultant: currentSite.value?.has_adr_consultant ?? '',
      customer_id: currentSite.value.customer_id,
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

	        form.fixed_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at) ?? formatServerDateTime(floorToFiveMinutes());
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

    const manageExpectedDate = () => { form.expected_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at); };

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
        if (Number(cer?.is_dangerous) === 1 && isBlank(item.adr_hp)) {
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
        openAll();
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

    const {registerSection, unregisterSection, openAll, closeAll} = useDisclosureSections();
    watch(user, (newUser) => {
      if (newUser) {
        form.user_id = newUser.id;
        form.logistics_user_id = newUser.id;
      }
    }, { immediate: true });

	    const edit = (postAction = 'save_exit') => {
      if (form.processing) return;
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
