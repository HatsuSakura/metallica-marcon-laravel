<template>
    <AppPage v-if="site" title="Nuovo ordine">
<template #back><AppAction :as="Link" :href="route('customer.show', {customer:site.customer_id})"><font-awesome-icon :icon="['fas','arrow-left']" aria-hidden="true" />Torna al cliente</AppAction></template> <!-- SERVE per evitare errori in apertura se VUEX STORE non è in sync-->
      <template #actions><SectionVisibilityActions :disabled="form.processing" @open-all="openAll" @close-all="closeAll" /></template>
      <RegistryForm :form="form" v-slot="{busy}" @submit="create('create_stay')">

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
                <VueDatePicker class="min-w-0 flex-1" style="max-width: 14rem" uid="requested_at" name="requested_at" :aria-labels="{input:'Data richiesta'}" v-model="form.requested_at" format="dd/MM/yyyy, HH:mm"></VueDatePicker>
              </div>

              <div class="flex items-center ">
                <label class="text-sm font-semibold" for="is_urgent">Urgenza  &nbsp;</label>
                <input v-model="form.is_urgent" id="is_urgent" type="checkbox" class="toggle" />
              </div>

              <div class="flex items-center ">
                <span class="text-sm font-semibold">Commerciale: &nbsp;</span> {{ site.customer?.seller?.name || 'Non assegnato'}}
              </div>

              <div class="flex items-center ">
                <span class="text-sm font-semibold">Logistica: &nbsp;</span> {{ user.name }} {{ user.surname }}
              </div>

            </div>
          </AccordionRow>

          <AccordionRow id="1" title="Cliente e sede" :initial-open="true" @register="registerSection" @unregister="unregisterSection"><OrderSiteSummary :site="site" /></AccordionRow>
          <!-- SEZIONE 2-->
          <AccordionRow
            id="2"
            title="Informazioni per il ritiro"
            :initialOpen=true
            @register="registerSection" @unregister="unregisterSection"
          >
            <div>
                <div class="font-medium">Mezzi di sollevamento:</div>
                <SiteMezziDiSollevamento :site="site" />
            </div>

              <hr class="my-4"/>

              <div class="space-y-1"><h3 class="text-sm font-semibold">Note della sede</h3><p class="whitespace-pre-line break-words text-sm">{{ siteNotesText }}</p></div>
              <div class="mt-4">
                <div class="font-medium">Referenti Interni:</div>
                <SiteInternalContacts :contacts="site.internal_contacts" />
              </div>

              <div class="mt-4">

                <div class="font-medium">Verifica Data/Orari:</div>

                <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                  <div class="min-w-0">
                    <label for="dp-input-expected_withdraw_at" class="label">Data presunta ritiro</label>
                    <VueDatePicker
                      uid="expected_withdraw_at" name="expected_withdraw_at"
                      v-model="form.expected_withdraw_at"
                      format="dd/MM/yyyy, HH:mm"
                      auto-apply
                      :enableTimePicker="true"
                      :disabled="hasFixedWithdrawAt"
                      @update:model-value="manageDate()">
                    </VueDatePicker>
                    <div class="input-error" v-if="form.errors.expected_withdraw_at">
                      {{ form.errors.expected_withdraw_at }}
                    </div>
                  </div>

                  <div class="min-w-0">
                    <label for="orariApertura" class="label">Orari sede in data: </label>
                    <output id="orariApertura" class="block min-h-12 rounded-lg border border-base-300 px-3 py-2 text-sm">{{ orariApertura }}</output>
                  </div>
                </div>

                <div class="mt-4 grid min-w-0 grid-cols-1 items-start gap-3 md:grid-cols-2">
                    <div class="space-y-1"><label for="fixed_withdraw_toggle" class="flex min-h-12 w-fit items-center gap-2 text-sm font-semibold"><input id="fixed_withdraw_toggle" :checked="hasFixedWithdrawAt" type="checkbox" class="toggle toggle-primary" @change="toggleFixedWithdraw($event.target.checked)" />Data fissa</label><p class="text-sm">Se attiva, la data presunta coincide con la data fissa.</p></div>
                    <div class="min-w-0"><label for="dp-input-fixed_withdraw_at" class="label">Data e ora fisse</label>
                    <VueDatePicker
                      uid="fixed_withdraw_at" name="fixed_withdraw_at"
                      v-model="form.fixed_withdraw_at"
                      format="dd/MM/yyyy, HH:mm"
                      auto-apply
                      :enableTimePicker="true"
                      :disabled="!hasFixedWithdrawAt"
                      @update:model-value="onFixedWithdrawChanged()"
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
            title="Contenitori da Ritirare/Consegnare"
            :initialOpen=true
            @register="registerSection" @unregister="unregisterSection"
          >
<!--
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

        <div class="mt-4 flex flex-wrap justify-end gap-2"><AppAction :as="Link" variant="cancel" :disabled="form.processing" :href="route('customer.show', {customer:site.customer_id})">Annulla</AppAction>
          <AppAction
            type="button"

            :disabled="form.processing"
            @click="create('create_exit')"
           variant="confirm" :busy="form.processing">
            Crea e torna all’elenco
          </AppAction>
          <AppAction type="submit" :disabled="form.processing" variant="confirm" :busy="form.processing">
            Crea
          </AppAction>
        </div>

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
    import { useForm, usePage } from '@inertiajs/vue3'
    import dayjs from 'dayjs';
    import VueDatePicker from '@/Components/UI/AppDateTimePicker.vue';
    import SiteInternalContacts from '@/Components/SiteInternalContacts.vue';
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
    const siteNotesText = site?.notes?.trim()
      ? site.notes
      : 'Nessuna nota particolare per questa sede';
    const customer = props.customer;

    const currentDate = computed(
      () => floorToFiveMinutes()
    )

const totalItemsWeight = computed(() => {
      if (form.items.length){
        return form.items.reduce((total, element) => total + (Number(element.weight_declared) || 0), 0);
      }
      return 0;
    });

const riskPercent = computed(() => {
  const value = Number(site?.calculated_risk_factor ?? 0) * 100;
  return Number.isFinite(value) ? Number(value.toFixed(1)) : 0;
});

const groupedItems = computed(() => groupOrderItems(form.items, props.cerList || []));

    const orariApertura = computed(() => siteOpeningHours(site, form.expected_withdraw_at));

    const form = useForm({
	      is_urgent: false,
	      requested_at: currentDate.value,
	      expected_withdraw_at: null,
	      fixed_withdraw_at: null,
	      notes: '',
	      post_action: 'create_stay',
	      logistics_user_id: user ? user.value.id : null, // Fallback to null if user is not defined
      has_adr_consultant: site.has_adr_consultant ?? '',
      customer_id: site.customer_id,
      site_id: site.id,
      user_id: user ? user.id : null, // Fallback to null if user is not defined
      code: null,
      items: [], // Start with an empty items array
	      holders: []
	    })

      const hasFixedWithdrawAt = computed(() => form.fixed_withdraw_at !== null);

      const normalizeDateOrNull = (value) => {
        if (!value) return null;

        const normalized = value instanceof Date ? value : new Date(value);

        return Number.isNaN(normalized.getTime()) ? null : normalized;
      };

      const syncExpectedWithFixed = () => {
        if (!hasFixedWithdrawAt.value || !form.fixed_withdraw_at) return;

        form.expected_withdraw_at = normalizeDateOrNull(form.fixed_withdraw_at);
        manageDate();
      };

      const toggleFixedWithdraw = (enabled) => {
        if (!enabled) {
          form.fixed_withdraw_at = null;
          return;
        }

        form.fixed_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at) ?? floorToFiveMinutes();
        syncExpectedWithFixed();
      };

      const onFixedWithdrawChanged = () => {
        form.fixed_withdraw_at = normalizeDateOrNull(form.fixed_withdraw_at);
        syncExpectedWithFixed();
      };

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
      const holderId = id == null || id === '' ? null : Number(id)
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




    const manageDate = () => { form.expected_withdraw_at = normalizeDateOrNull(form.expected_withdraw_at); };

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

	    const create = (postAction = 'create_stay') => {
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
	      form.requested_at = dayjs(form.requested_at).format('YYYY-MM-DD HH:mm:ss');
          form.expected_withdraw_at = form.expected_withdraw_at
            ? dayjs(form.expected_withdraw_at).format('YYYY-MM-DD HH:mm:ss')
            : null;
          form.fixed_withdraw_at = form.fixed_withdraw_at
            ? dayjs(form.fixed_withdraw_at).format('YYYY-MM-DD HH:mm:ss')
            : null;

		      form.is_urgent = form.is_urgent === true;
		      form.post_action = postAction;

      if (!runOrderPrecheck()) {
        return;
      }

	      form.post(route('order.store'));
	    }



    </script>
