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
                class="tab inline-flex items-center whitespace-nowrap;"
                :aria-label="group.denominazione"
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

                <!-- RIGA PER OGNI ITEM -->                
                <div v-if="Number(warehouseId) === currentWarehouse" class="flex flex-col gap-2">
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
                  />
                </div>
                <div v-else class="flex flex-col gap-2">
                  <OrderItemRowNotMyWarehouse
                    v-for="(item, idx) in group.items"
                    :key="item.id"
                    :item="item"
                    :index="idx"
                  />
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
  return props.order.items.reduce((acc, item) => {
    // prendi l’ID del magazzino di download (può tornarti stringa o numero)
    const wid = item.warehouse_download.id ?? 0
    // prendi la denominazione
    const denom = item.warehouse_download.denominazione ?? 'none'
    if (!acc[wid]) {
      acc[wid] = {
        id: wid,
        denominazione: denom,
        items: []
      }
    }
    acc[wid].items.push(item)
    return acc
  }, {})
})


const modifiedItems = ref({}) // lista degli item modificati, da salvare tutti insieme
const savingItems = ref([])   // lista di item.id in corso di salvataggio a fronte della pressione del save() sul singolo itemRow

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
