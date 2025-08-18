<template>
  <div>
    <!-- TESTATA -->
    <div class="mb-4">
      <Link class="btn btn-ghost" :href="route('warehouse-manager.orders.index')">
      <font-awesome-icon :icon="['fas','arrow-left']" class="text-xl"/>
      Torna a ordini scaricati
      </Link>
    </div>

    <OrderSpace
        :customer="order.customer"
        :order="order"
        :site="order.site"
        class="mb-8"
    />

          <div role="tablist" class="tabs tabs-bordered">
                <template
                  v-for="(group, warehouseId, idx) in itemsByWarehouse"
                  :key="warehouseId"
                >
              <input
                :id="`tab-${warehouseId}`"
                type="radio"
                name="tablist_warehouses"
                role="tab"
                class="tab inline-flex items-center whitespace-nowrap"
                :aria-label="`${group.denominazione} [ ${group.count} ]`"
                :value="Number(warehouseId)"
                :checked="Number(warehouseId) === currentWarehouse"
                :class="Number(warehouseId) === currentWarehouse ? 'current-warehouse' : ''"
              />
              <div role="tabpanel" class="tab-content bg-base-100  pt-4">


                <!-- BLOCCO OPZIONI ORDINE -->
                <div class="flex flex-row justify-between items-center sticky top-16 bg-base-100 z-10 py-4">

                  <!-- BLOCCO + Aggiungi Elemento -->
                  <div>
                    <Link v-if="Number(warehouseId) === currentWarehouse"
                      :href="route('warehouse.orders.items.create', { order: order.id })"
                      method="get"
                      as="button"
                      class="btn btn-primary"
                    >
                      <font-awesome-icon :icon="['fas','plus']" class="text-2xl"/> 
                      Aggiungi Elemento
                    </Link>
                    <button v-else
                      class="btn btn-disabled"
                      tabindex="-1" role="button"
                      aria-disabled="true"
                    >
                      <font-awesome-icon :icon="['fas','plus']" class="text-2xl"/> 
                      Aggiungi Elemento
                    </button>
                  </div>

                  <!-- BLOCCO RAGNO -->
                  <div v-if="Number(warehouseId) === currentWarehouse" class="flex flex-row justify-start items-center gap-4">
                        <div class="flex flex-row gap-4 justify-between">
                          <label class="font-medium">Uso ragno:</label>
                          <input type="checkbox" v-model="form.has_ragno" class="toggle"/>
                        </div>

                        <div v-if="form.has_ragno" class="flex flex-row justify-between items-center gap-4">
                          <label class="font-medium">Ragnista:</label>
                          <select v-model="form.ragnista_id" class="select select-bordered">
                              <option value="" disabled>Seleziona un Ragnista</option>
                              <option v-for="w in warehouseWorkers.filter(w => w.is_ragnista)" :key="w.id" :value="w.id">
                              {{ w.name }} {{ w.surname }}
                              </option>
                          </select>
                          <label class="font-medium">Tempo ragno:</label>
                          <select v-model="form.machinery_time_hh" class="select select-bordered">
                              <option v-for="h in 24" :key="h" :value="h">{{ String(h).padStart(2,'0') }}</option>
                          </select> hh
                          <select v-model="form.machinery_time_mm" class="select select-bordered">
                              <option v-for="m in getRange(0,55,5)" :key="m" :value="m">{{ String(m).padStart(2,'0') }}</option>
                          </select> mm
                        </div>
                  </div>

                  <!-- BLOCCO Salva Tutto -->
                  <div>
                      <button v-if="Number(warehouseId) === currentWarehouse"
                        @click="saveAll" 
                        class="btn btn-outline btn-success"
                        :disabled="!canSaveAll"
                        >
                        <font-awesome-icon :icon="['fas','floppy-disk']" class="text-2xl"/> Salva TUTTO
                      </button>
                      <button v-else
                        class="btn btn-disabled"
                        tabindex="-1" role="button"
                        aria-disabled="true"
                      >
                        <font-awesome-icon :icon="['fas','floppy-disk']" class="text-2xl"/> Salva TUTTO
                    </button>
                  </div>


                </div>
                <!-- FINE BLOCCO OPZIONI ORDINE -->

                <!-- RIGA PER OGNI ITEM DELLA WAREHOUSE CORRENTE -->                
                <div v-if="Number(warehouseId) === currentWarehouse" class="flex flex-col gap-2">
                    <template v-if="group.items.length > 0">

                      <OrderItemRow
                      v-for="(item, idx) in group.items"
                      :key="item.id"
                      :item="item"
                      :index="idx"
                      :warehouseChiefs="filteredChiefs"
                      :warehouseManagers="filteredManagers"
                      :warehouseWorkers="filteredWorkers"
                      :parentHasRagno="form.has_ragno"
                      :parentMachineryTime="form.machinery_time"
                      :resetKey="resetKey"
                      @update="handleItemUpdate"
                      :saving="savingItems.includes(item.id)"
                      @save-one="handleSaveOne"
                      @update-is-ragnabile-toggle="onRowToggle"
                      @update-manual-machinery-time="onRowManual"
                      @reset-manual-machinery-time="onRowReset"
                      @itemNotFound="onItemNotFound"
                      @itemFound="onItemFound"
                      />
                    </template>
                    <div v-else class="alert alert-info">
                      Nessun elemento presente in questo magazzino.
                    </div>
               
                </div>

                <!-- RIGA PER OGNI ITEM DI ALTRE WAREHOUSE -->                
                <div v-else class="flex flex-col gap-2">

                  <template v-if="group.items.length > 0">
                  <OrderItemRowNotMyWarehouse
                    v-for="(item, idx) in group.items"
                    :key="item.id"
                    :item="item"
                    :index="idx"
                    @import="onImportItem"
                  />
                  </template>
                  <div v-else class="alert alert-info">
                      Nessun elemento presente in questo magazzino.
                  </div>

                </div>


                <div v-if="Number(warehouseId) === currentWarehouse" class="my-4 flex flex-row justify-end">
                  <button
                    @click="saveAll" 
                    class="btn btn-outline btn-success"
                    :class="canCloseOrder ? '' : 'btn-disabled'"
                  >
                    <font-awesome-icon :icon="['fas','check']" class="text-2xl"/>
                    CHIUDI ordine per "{{ group.denominazione }}"
                  </button>
                </div>


              </div>
            </template>

          </div>


    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { Link, usePage, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { useStore } from 'vuex'
import OrderItemRow from './Components/OrderItemRow.vue'
import OrderSpace from '@/Components/OrderSpace.vue'
import OrderItemRowSimple from './Components/OrderItemRowSimple.vue'
import OrderItemRowNotMyWarehouse from './Components/OrderItemRowNotMyWarehouse.vue'

const props = defineProps({
  order: Object,
  warehouses: Array,
  warehouseChiefs: Array,
  warehouseManagers: Array,
  warehouseWorkers: Array,
})
const order = reactive({ ...props.order })

const page      = usePage()
const store     = useStore()
const user      = computed(() => page.props.user)
const resetKey  = ref(0)

// Inertia form per has_ragno e ragnista
const form = useForm({
  has_ragno: props.order.has_ragno === 1 ? true : false,
  ragnista_id: props.order.ragnista_id || '',
  machinery_time: props.order.machinery_time || 0,
  machinery_time_hh: Math.floor(props.order.machinery_time / 60) || 0,
  machinery_time_mm: props.order.machinery_time % 60 || 0,   
})

const currentWarehouse = computed(() =>
  user.value.warehouses?.[0]?.id ?? 1 // Imposta Metallica come magazzino predefinito se non c'è nessuno
)

const filteredChiefs   = computed(() =>
  props.warehouseChiefs.filter(w => w.warehouses.some(x => x.id === currentWarehouse.value))
)
const filteredManagers = computed(() =>
  props.warehouseManagers.filter(w => w.warehouses.some(x => x.id === currentWarehouse.value))
)
const filteredWorkers   = computed(() =>
  props.warehouseWorkers.filter(w => w.warehouses.some(x => x.id === currentWarehouse.value))
)

const itemsByWarehouse = computed(() => {
  // 1) inizializza tutte le warehouses come gruppi vuoti
  const map = (props.warehouses || []).reduce((acc, w) => {
    acc[w.id] = { id: w.id, denominazione: w.denominazione, items: [], count: 0 }
    return acc
  }, {})

  // 2) popola dai items dell'ordine
  const src = Array.isArray(order.items) ? order.items : []
  for (const item of src) {
    const wid   = item?.warehouse_download?.id ?? 0
    const denom = item?.warehouse_download?.denominazione ?? 'none'

    // se arriva un wid non presente in props.warehouses, crealo on-the-fly
    if (!map[wid]) {
      map[wid] = { id: wid, denominazione: denom, items: [], count: 0 }
    }

    map[wid].items.push(item)
  }

  // 3) aggiorna i contatori
  Object.values(map).forEach(g => { g.count = g.items.length })

  return map
})




const modifiedItems = ref({}) // lista degli item modificati, da salvare tutti insieme
const savingItems = ref([])   // lista di item.id in corso di salvataggio a fronte della pressione del save() sul singolo itemRow


/*
 * Gestisce la modifica tra NOT FOUND e FOUND per rendere l'item accessibile dall'altro magazzino
 */
function optimisticUpdate(id, patch) {
  if (!order?.items || !Array.isArray(order.items)) {
    return { revert: () => {} }
  }
  const idx = order.items.findIndex(i => i.id === id)
  if (idx === -1) return { revert: () => {} }

  const prev = { ...order.items[idx] }
  order.items[idx] = { ...order.items[idx], ...patch }

  return {
    revert: () => { order.items[idx] = prev }
  }
}

async function onItemNotFound(payload) {
  const { id, updated_at } = payload
  const { revert } = optimisticUpdate(id, { is_not_found: 1 })

  try {
    await axios.patch(
      route('warehouse-order-items.flag-not-found', { orderItem: id }),
      { is_not_found: true, updated_at }
    )
    store.dispatch('flash/queueMessage', {
      type: 'success',
      text: `Item ${id} dichiarato NON TROVATO.`,
    })
  } catch (error) {
    revert()
    console.error('Errore nel flag NOT FOUND:', error)
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: `Errore nel gestire lo stato NON TROVATO per l'item ${id}.`,
    })
  }
}

async function onItemFound(payload) {
  const { id, updated_at } = payload
  const { revert } = optimisticUpdate(id, { is_not_found: 0 })

  try {
    await axios.patch(
      route('warehouse-order-items.flag-not-found', { orderItem: id }),
      { is_not_found: false, updated_at }
    )
    store.dispatch('flash/queueMessage', {
      type: 'success',
      text: `Item ${id} ripristinato come TROVATO.`,
    })
  } catch (error) {
    revert()
    console.error('Errore nel flag FOUND:', error)
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: `Errore nel gestire lo stato TROVATO per l'item ${id}.`,
    })
  }
}


// opzionale: spinner per item
const importingItems = ref([])

async function onImportItem({ id, journey_cargo_id }) {
  // evita doppio click
  if (importingItems.value.includes(id)) return
  importingItems.value.push(id)

  // Trova il primo journey_cargo id disponibile nel magazzino corrente
  const currentJourneyCargo = (() => {
    const group = itemsByWarehouse.value?.[currentWarehouse.value]
    if (group?.items?.length){

      for (const it of group.items) {
        const jc = it?.journey_cargo
        if (!jc) continue
        // preferisci jc.id; fallback jc.cargo_id
        const found = jc?.id ?? jc?.cargo_id ?? null
        if (found != null) return found
      }

    }
    else{
      const list = order?.journey?.journey_cargos
      console.log('list', list, currentWarehouse.value)
      if (!Array.isArray(list)) return null
      const hit = list.find(jc => Number(jc?.warehouse_id) === Number(currentWarehouse.value))
      return hit?.id ?? null
    }
    // se non trovi nessun cargo, ritorna null
    return null
  })()

    if (currentJourneyCargo == null) {
    importingItems.value = importingItems.value.filter(x => x !== id)
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: `Il viaggio di cui questo ordine faceva parte non è passato da questo magazzino.`,
    })
    return
  }

  try {
    const res = await axios.post(
      `/api/warehouse-order-items/move-journey-cargo/${id}`,
      { journey_cargo_id: currentJourneyCargo, warehouse_id: currentWarehouse.value }
    )

    // può arrivare { orderItem: {...} } o l’oggetto diretto
const updated = res.data?.orderItem ?? res.data

// 🔒 Forza SEMPRE la proiezione locale del magazzino di atterraggio
const wh = (props.warehouses || []).find(w => Number(w.id) === Number(currentWarehouse.value))
updated.warehouse_download = wh
  ? { id: wh.id, denominazione: wh.denominazione }
  : { id: Number(currentWarehouse.value), denominazione: '—' }

// opzionale ma utile: allinea anche il journey cargo mostrato in UI
if (updated.journey_cargo && updated.journey_cargo.id !== currentJourneyCargo) {
  updated.journey_cargo = { ...(updated.journey_cargo || {}), id: currentJourneyCargo }
}

// 🔁 Rimpiazza nell’array reattivo e CAMBIA LA REFERENCE
const idx = order.items.findIndex(i => i.id === updated.id)
if (idx !== -1) {
  const prev = order.items[idx]

  // unisci preservando relazioni/fields che il backend non rimanda
  const merged = {
    ...prev,
    ...updated,

    // salva le relazioni usate in OrderItemRow se mancano nel payload
    holder:        updated.holder        ?? prev.holder,
    cerCode:       updated.cerCode       ?? prev.cerCode,   // se usi camelCase
    cer_code:      updated.cer_code      ?? prev.cer_code,  // se usi snake_case altrove
    images:        updated.images        ?? prev.images,
    warehouse:     updated.warehouse     ?? prev.warehouse, // se la row mostra dati del warehouse “principale”
    journey_cargo: updated.journey_cargo ?? prev.journey_cargo,
  }

  order.items.splice(idx, 1, merged)
} else {
  order.items.push(updated)
}
order.items = [...order.items]    // <-- cambia la reference per invalidare le computed
resetKey.value++                  // <-- se i figli hanno watcher “immediate”

    // flash di successo
    store.dispatch('flash/queueMessage', {
      type: 'success',
      text: `Materiale importato correttamente (item ${updated.id}).`,
    })
  } catch (error) {
    console.error('Error moving warehouse item:', error)
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: `Errore durante la procedura di import per l'item ${id}.`,
    })
  } finally {
    importingItems.value = importingItems.value.filter(x => x !== id)
  }
}







function handleItemUpdate(updatedItem) {
  // 1) mark it dirty for saveAll
  modifiedItems.value[updatedItem.id] = updatedItem

  // 2) sync into the live order.items so children re-render
  const idx = order.items.findIndex(i => i.id === updatedItem.id)
  if (idx !== -1) {
    // either overwrite the whole object:
    order.items[idx] = { ...order.items[idx], ...updatedItem }
    // —or mutate in place if you prefer:
    // Object.assign(order.items[idx], updatedItem)
  }
}

function getRange(start, stop, step = 1) {
  const length = Math.floor((stop - start) / step) + 1
  return Array.from({ length }, (_, i) => start + i * step)
}


/* IS_RAGNABILE */
// track which rows have a manual fraction
const manualRows = ref({})

/**
 * Re‐distribute the parent machinery_time (hh/mm) into each
 * ragnabile row, respecting any manual overrides.
 */
function recalcMachineryFractions() {
  const active = order.items.filter(i => i.is_ragnabile)
  if (!active.length) return
  const manualSum = Object.values(manualRows.value).reduce((a, b) => a + b, 0)
  const leftover = Math.max(0, form.machinery_time - manualSum)
  const auto = active.filter(i => manualRows.value[i.id] == null)
  const perAuto = auto.length ? Math.floor(leftover / auto.length) : 0
  active.forEach(item => {
    const mins = manualRows.value[item.id] != null ? manualRows.value[item.id] : perAuto
    handleItemUpdate({
      ...item,
      machinery_time_fraction: mins,
      machinery_time_fraction_hh: Math.floor(mins / 60),
      machinery_time_fraction_mm: mins % 60,
      is_machinery_time_manual: manualRows.value[item.id] != null ? 1 : 0,
    })
  })
}

function onRowToggle({ id, isRagnabile }) {
  if (!isRagnabile) delete manualRows.value[id]
  recalcMachineryFractions()
}
function onRowManual({ id, minutes }) {
  manualRows.value[id] = minutes
  recalcMachineryFractions()
}
function onRowReset({ id }) {
  delete manualRows.value[id]
  recalcMachineryFractions()
}


// remember the initial state
const initialOrder = {
  has_ragno: props.order.has_ragno,
  ragnista_id: props.order.ragnista_id,
  machinery_time: props.order.machinery_time,
};

// compute whether the order form itself has changed
const orderDirty = computed(() => {
  const currentMachinery = form.machinery_time_hh * 60 + form.machinery_time_mm;
  return (
    form.has_ragno !== initialOrder.has_ragno ||
    String(form.ragnista_id)   !== String(initialOrder.ragnista_id) ||
    currentMachinery           !== initialOrder.machinery_time
  );
});

// compute whether any child item has been modified
const itemsDirty = computed(() => Object.keys(modifiedItems.value).length > 0);

// overall flag
const canSaveAll = computed(() => orderDirty.value || itemsDirty.value);

const canCloseOrder = computed(() => {
  const group = itemsByWarehouse.value?.[currentWarehouse.value]
  if (!group) return false

  const items = group.items || []
  if (items.length === 0) return false // opzionale: non chiudere se vuoto

  return items.every(it =>
    !!it.warehouse_downaload_dt &&   // scarico
    !!it.warehouse_weighing_dt &&    // pesatura
    !!it.warehouse_selection_dt      // selezione
  )
})



/**
 * Salva un singolo orderItem:
 * - lo invia al backend
 * - aggiorna order.items con la risposta
 * - toglie quell’id da modifiedItems
 */
async function handleSaveOne(itemData) {
  const id = itemData.id
  // evita salvataggi multipli concorrenti
  if (savingItems.value.includes(id)) return

  savingItems.value.push(id)

  try {
    const res = await axios.put(
      `/api/warehouse-order-items/${id}`,
      itemData
    )
    const saved = res.data

    // 1) ricalcolo lo split della frazione in hh/mm
    const total = Number(saved.machinery_time_fraction) || 0
    saved.machinery_time_fraction_hh = Math.floor(total / 60)
    saved.machinery_time_fraction_mm = total % 60

    // 2) Aggiorna l’item nel reactive `order.items`
    const idx = order.items.findIndex(i => i.id === saved.id)
   if (idx !== -1) {
    order.items.splice(idx, 1, saved)
  }

    // 3) Rimuovi quest’id dai dirty items
    delete modifiedItems.value[saved.id]

    // 4) Feedback
    store.dispatch('flash/queueMessage', {
      type: 'success',
      text: `Item ${saved.id} salvato con successo.`,
    })
    resetKey.value++    // ← forza il child a ritriggerare tutti i watcher “immediate”
  }
  catch (e) {
    if (e.response?.status === 409) {
      const c = e.response.data.conflicts[0]
      // ricarica i dati corretti
      order.items[order.items.findIndex(i=> i.id===c.id)] = c.updated_item
      delete modifiedItems.value[c.id]
      store.dispatch('flash/queueMessage', {
        type: 'error',
        text: `Item ${c.id} in conflitto. Dati ricaricati.`,
      })
    } else {
      store.dispatch('flash/queueMessage', {
        type: 'error',
        text: `Errore nel salvataggio di item ${id}.`,
      })
    }
  }
  finally {
    // tolgo lo spinner
    savingItems.value = savingItems.value.filter(x => x !== id)
  }
}


async function saveAll() {
  if (!canSaveAll.value) {
    // nothing to do
    return;
  }

  // …rest of your existing logic…
  const formData = new FormData();

  // 1) append order fields
  formData.append('has_ragno',    form.has_ragno ? '1' : '0');
  formData.append('ragnista_id',  form.ragnista_id || '');
  formData.append('machinery_time', form.machinery_time_hh * 60 + form.machinery_time_mm);

  // 2) append items…
  const items = Object.values(modifiedItems.value);
  items.forEach((it, i) => {
    Object.entries(it).forEach(([key, val]) => {
      if (key === 'images' && Array.isArray(val)) {
        val.forEach((f, j) => formData.append(`items[${i}][images][${j}]`, f));
      } else {
        formData.append(`items[${i}][${key}]`, val ?? '');
      }
    });
  });

  formData.append('_method', 'PUT');

  try {
    const res = await axios.post(
      `/api/warehouse-orders/${props.order.id}`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );

    // merge back the freshly returned order + items
    const fresh = res.data.order;
    Object.assign(order, {
      has_ragno:   fresh.has_ragno,
      ragnista_id: fresh.ragnista_id,
      machinery_time: fresh.machinery_time,
      items:       fresh.items,
    });
    form.machinery_time_hh = Math.floor(fresh.machinery_time / 60);
    form.machinery_time_mm = fresh.machinery_time % 60;

    // clear out the dirty flags
    modifiedItems.value = {};
    resetKey.value++;

    store.dispatch('flash/queueMessage', {
      type: 'success',
      text: 'Tutti gli elementi modificati sono stati aggiornati con successo'
    });
  } catch (e) {
    if (e.response?.status === 409) {
      e.response.data.conflicts.forEach(c => {
        modifiedItems.value[c.id] = c.updatedItem;
        store.dispatch('flash/queueMessage', {
          type: 'error',
          text: `Item ${c.id} in conflitto`
        });
      });
    } else {
      store.dispatch('flash/queueMessage', {
        type: 'error',
        text: 'Errore nel salvataggio dei singoli elementi'
      });
    }
  }
}


</script>

<style>
/* Add any specific styles for this component here */
.collapse-title {
    padding: 0.25rem 1rem !important;
    min-height: 2.5rem !important;
}
</style>

<style scoped>

.tab.current-warehouse {
  display: inline-flex;
  align-items: center;
  flex-wrap: nowrap;
  white-space: nowrap;
}

.tab.current-warehouse::before {
  content: attr(aria-label);
  display: inline-block;
  margin-right: 0.25rem; /* same as Tailwind’s mr-1 */
}

.tab.current-warehouse::after {
  content: "\f521";                   /* codice unicode di “crown” */
  display: inline-block;
  font-family: "Font Awesome 5 Free"; /* o il nome esatto che hai importato */
  font-weight: 900;                   /* solid */
  margin-left: 0.25rem;
  vertical-align: middle;
}
</style>
