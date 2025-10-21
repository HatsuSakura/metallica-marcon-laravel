// Order/Edit.vue
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
                      :saving="savingItems.includes(item.id)"
                      :recipes="props.recipes"
                      :catalog="props.catalog"
                      @update="handleItemUpdate"
                      @images-updated="onImagesUpdated"
                      @images-staged="onImagesStaged"
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
import { normalizeExplosionsArray } from '@/utils/orderItemExplosions - OLD.js'

const props = defineProps({
  order: Object,
  catalog:    { type: Array, required: true },   // [{id,name,type}]
  recipes:    { type: Array, default: () => []}, // [{id,name,version}]
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

// --- helpers esplosioni: preferisci già-albero, altrimenti costruisci da piatto
function buildTreeFromFlat(list) {
  const arr = Array.isArray(list) ? list : []
  const byId = new Map()
  const roots = []

  // clona shallow e prepara children
  arr.forEach(n => byId.set(n.id, { ...n, children: [] }))

  byId.forEach(n => {
    const pid = n.parent_explosion_id
    if (pid) {
      const p = byId.get(pid)
      if (p) p.children.push(n)
      else roots.push(n) // orfani: considera root
    } else {
      roots.push(n)
    }
  })


  // opzionale: ordina per sort ad ogni livello se disponibile
  const sortTree = (nodes) => {
    nodes.sort((a, b) => (a.sort ?? 0) - (b.sort ?? 0))
    nodes.forEach(ch => Array.isArray(ch.children) && sortTree(ch.children))
  }
  sortTree(roots)

  return roots
}

  // sotto agli import o vicino ad altri helper
  const deepClone = (x) => (x == null ? x : JSON.parse(JSON.stringify(x)))

  function hasTreeChildren(list) {
    if (!Array.isArray(list)) return false
    return list.some(n =>
      Array.isArray(n?.children) ||
      Array.isArray(n?.children_recursive) ||
      Array.isArray(n?.childrenRecursive)
    )
  }

function pickExplosionTree(item) {
  // prova tutte le possibili chiavi note
  const candidates = [
    item?.explosions,
    item?.explosions_root,
    item?.explosionsRoot,
    item?.explosionsTree,
    item?.explosionsRecursive,
  ].filter(Array.isArray)

  if (candidates.length) {
    const arr = candidates[0]
    // ✅ se è già ad albero (almeno un nodo con children*), usalo così com'è
    if (hasTreeChildren(arr)) return deepClone(arr)
    // 🧱 altrimenti è piatto → ricostruisci l'albero
    return buildTreeFromFlat(arr)
  }

  return []
}


// produce un item “solo per la UI” con explosions già ad albero
function withTreeExplosions(item) {
  // non mutiamo l’oggetto originale per non rompere reattività a valle
  return {
    ...item,
    explosions: pickExplosionTree(item), // <- sempre children[] se presenti
  }
}


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

    //map[wid].items.push(item)
    map[wid].items.push(withTreeExplosions(item))
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


const stagedImages = ref({}) // { [itemId]: File[] }

function onImagesStaged({ id, files }) {
  // salva i File per il bulk
  stagedImages.value[id] = Array.isArray(files) ? files.filter(f => f instanceof File) : []

  // IMPORTANTISSIMO: mettili anche dentro modifiedItems
  // così saveAll li serializza dentro FormData
  if (stagedImages.value[id].length) {
    modifiedItems.value[id] = {
      ...(modifiedItems.value[id] || { id }),
      images: stagedImages.value[id],
    }
  } else {
    // se l’utente ha svuotato la dropzone: non inviare 'images' vuote
    if (modifiedItems.value[id]) {
      const { images, ...rest } = modifiedItems.value[id]
      modifiedItems.value[id] = { ...rest }
      if (!Object.keys(modifiedItems.value[id]).length) delete modifiedItems.value[id]
    }
  }
}


function onImagesUpdated({ id, images }) {
  // 1) aggiorna la riga in order.items
  const idx = order.items.findIndex(i => i.id === id)
  if (idx !== -1) {
    order.items[idx] = {
      ...order.items[idx],
      images: images || []
    }
  }

  // 2) se l’item è presente tra i “dirty” (modifiedItems) e stai facendo
  //    un salvataggio massivo che deve includere immagini nuove (upload),
  //    qui di solito NON serve aggiornare perché la cancellazione è già
  //    stata persistita lato server.
  //    Tuttavia, se vuoi che saveAll spedisca comunque lo stato corrente,
  //    puoi allineare anche il buffer:
  if (modifiedItems.value[id]) {
    modifiedItems.value[id] = {
      ...modifiedItems.value[id],
      images: images || []
    }
  }
}





/*
function handleItemUpdate(updatedItem) {
  // 1) mark it dirty for saveAll
  modifiedItems.value[updatedItem.id] = updatedItem

  // 2) sync into the live order.items so children re-render
  const idx = order.items.findIndex(i => i.id === updatedItem.id)
  if (idx !== -1) {
    // either overwrite the whole object:
    // order.items[idx] = { ...order.items[idx], ...updatedItem }
    const { images, ...rest } = updatedItem
    order.items[idx] = { ...order.items[idx], ...rest } // 👈 non tocchiamo .images
    // —or mutate in place if you prefer:
    // Object.assign(order.items[idx], updatedItem)
  }
}
*/
function handleItemUpdate(updatedItem) {
  //const id = updatedItem.id
  // Fallback id: se manca in updatedItem, prendi quello della riga nel dataset
  let id = updatedItem.id
  if (!id) {
    const fromOrder = Array.isArray(order.items) ? order.items.find(i => i?.id === updatedItem?.id) : null
    id = fromOrder?.id ?? updatedItem?._id ?? updatedItem?.item_id
  }
  if (!id) {
    console.warn('handleItemUpdate: missing id in updatedItem, skipping', updatedItem)
    return
  }
  updatedItem.id = id

  // Accumula ESCLUSIVAMENTE i File reali per il bulk (se presenti)
  if (Array.isArray(updatedItem.images)) {
    const newFiles = updatedItem.images.filter(f => f instanceof File)
    if (newFiles.length) {
      stagedImages.value[id] = newFiles
      modifiedItems.value[id] = {
        ...(modifiedItems.value[id] || { id }),
        ...updatedItem,
        images: newFiles, // solo i File
      }
    } else {
      // niente file nuovi nell’update “logico”
      modifiedItems.value[id] = { ...(modifiedItems.value[id] || { id }), ...updatedItem }
      // ma NON iniettiamo images vuote
      delete modifiedItems.value[id].images
    }
  } else {
    modifiedItems.value[id] = { ...(modifiedItems.value[id] || { id }), ...updatedItem }
  }

  // Aggiorna la riga visuale SENZA toccare le immagini persistite
  const idx = order.items.findIndex(i => i.id === id)
  if (idx !== -1) {
    const { images, ...rest } = updatedItem
    order.items[idx] = { ...order.items[idx], ...rest }
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
 * helper per salvare i valori non array
 * serializza oggetti/array non-file se ne hai
 * SCALARI: booleans -> '1'/'0', null/undefined -> ''
 */

function appendScalar(fd, key, val) {
  if (typeof val === 'boolean') {
    fd.append(key, val ? '1' : '0')
  } else if (val == null) {
    fd.append(key, '')
  } else {
    fd.append(key, String(val))
  }
}

// --- FE: normalizzatore esplosi ---
// prende un nodo "grezzo" (con _selected, catalog_item, ecc.) e torna il formato pulito
function normalizeExplosionNode(n) {
  if (!n || typeof n !== 'object') return null

  // ID catalogo (supporta tante varianti)
  const catalogId =
    n.catalog_item_id ??
    n.catalogItemId ??
    n.catalog_item?.id ??
    n.catalogItem?.id ??
    n._selected?.id ??
    n.selected?.id ??
    n.catalog_id ??
    n.catalogId ?? null

  if (!catalogId) return null

  // Tipo (material | component | service ...)
  const type =
    n.type ??
    n.catalog_item?.type ??
    n.catalogItem?.type ??
    n._selected?.type ??
    n.selected?.type ?? null

  // Peso applicato solo se è materiale
  const weight =
    type === 'material'
      ? Number(n.weight_net ?? n.weightNet ?? n.net_weight ?? 0) || 0
      : null

  // Recipe linkage (id + version) in varie forme
  const recipeId =
    n.recipe_id ??
    n.recipeId ??
    n._selectedRecipeId ??
    n.selectedRecipeId ??
    n.recipe?.id ?? null

  const recipeVersion =
    n.recipe_version ??
    n.recipeVersion ??
    n.recipe?.version ?? null

  // Origine esplosione
  const explosionSource =
    n.explosion_source ??
    n.explosionSource ??
    (n.isFromRecipe ? 'recipe' : 'ad_hoc')

  // Note
  const notes = n.notes ?? null

  // Figli
  const childrenArr = Array.isArray(n.children) ? n.children
                    : Array.isArray(n.items)    ? n.items
                    : []
  const children = childrenArr.map(normalizeExplosionNode).filter(Boolean)

  return {
    catalog_item_id: catalogId,
    recipe_id: recipeId,
    recipe_version: recipeVersion,
    explosion_source: explosionSource,
    weight_net: weight,
    notes,
    children,
  }
}

/*
function normalizeExplosionsArray(nodes) {
  return (Array.isArray(nodes) ? nodes : [])
    .map(normalizeExplosionNode)
    .filter(Boolean)
}
*/
/*
function normalizeExplosionsArray(nodes) {
  return (Array.isArray(nodes) ? nodes : [])
    .map(normalizeExplosionNode)
    .filter(Boolean)
}
*/

// --- APPEND ITEM: usato sia dal singolo sia dal bulk ---
function appendItemToFormData(fd, item, index = null) {
  const prefix = index == null ? '' : `items[${index}]`

  // id sempre
  appendScalar(fd, prefix ? `${prefix}[id]` : 'id', item.id)

  // SCALARI (no images/explosions)
  /*
  Object.entries(item).forEach(([key, val]) => {
    if (key === 'images' || key === 'explosions') return
    appendScalar(fd, prefix ? `${prefix}[${key}]` : key, val)
  })
  */
  // 2) SCALARI ammessi (ESCLUDI 'id')
  const scalarKeys = [
    'holder_quantity','cer_code_id',
    'weight_gross','weight_tare','weight_net',
    'is_ragnabile','machinery_time_fraction',
    'machinery_time_fraction_hh','machinery_time_fraction_mm',
    'is_holder_dirty','total_dirty_holders',
    'is_holder_broken','total_broken_holders',
    'has_selection','selection_time','selection_time_hh','selection_time_mm',
    'warehouse_downaload_worker_id','warehouse_downaload_dt',
    'warehouse_weighing_worker_id','warehouse_weighing_dt',
    'warehouse_selection_worker_id','warehouse_selection_dt',
    'warehouse_notes','warehouse_non_conformity',
    'has_exploded_children','updated_at',
  ]
  scalarKeys.forEach((key) => {
    if (key in item) {
      appendScalar(fd, prefix ? `${prefix}[${key}]` : key, item[key])
    }
  })  

  // ESPLOSIONE (normalizzata) -> JSON string
  const clean = normalizeExplosionsArray(item.explosions || [])
  fd.append(prefix ? `${prefix}[explosions]` : 'explosions', JSON.stringify(clean))

  // IMMAGINI -> SOLO file nuovi
  const files = Array.isArray(item.images) ? item.images : []
  files.forEach((file, j) => {
    if (file instanceof File) {
      fd.append(prefix ? `${prefix}[images][${j}]` : `images[${j}]`, file)
    }
  })

  // (opzionale) optimistic lock soft
  if (item.updated_at) {
    appendScalar(fd, prefix ? `${prefix}[updated_at]` : 'updated_at', item.updated_at)
  }
}


/**
 * Salva un singolo orderItem:
 * - lo invia al backend
 * - aggiorna order.items con la risposta
 * - toglie quell’id da modifiedItems
 */
async function handleSaveOne(itemData) {
  const id = itemData.id
  if (savingItems.value.includes(id)) return
  savingItems.value.push(id)

  try {
    const fd = new FormData()
    appendItemToFormData(fd, itemData, null) // 👈 singolo: nessun prefix "items[i]"
    fd.append('_method', 'PUT')

    const res = await axios.post(`/api/warehouse-order-items/${id}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    // 👇 UNWRAP ROBUSTO
    const payload = res.data?.item ?? res.data?.orderItem ?? res.data
    const saved   = Array.isArray(payload) ? payload[0] : payload

    // ricalcolo hh/mm (se serve)
    const total = Number(saved.machinery_time_fraction) || 0
    saved.machinery_time_fraction_hh = Math.floor(total / 60)
    saved.machinery_time_fraction_mm = total % 60

    // sostituisci l’item e CAMBIA REFERENCE all’array
    const idx = order.items.findIndex(i => i.id === saved.id)
    if (idx !== -1) {
      order.items.splice(idx, 1, {
        ...order.items[idx],
        ...saved,
        // difese se il backend non include sempre tutte le relazioni
        holder:        saved.holder        ?? order.items[idx].holder,
        cer_code:      saved.cer_code      ?? order.items[idx].cer_code,
        images:        saved.images        ?? order.items[idx].images,
        warehouse:     saved.warehouse     ?? order.items[idx].warehouse,
        journey_cargo: saved.journey_cargo ?? order.items[idx].journey_cargo,
      })
      order.items = [...order.items] // 👈 forza reattività in catena
    }

    delete modifiedItems.value[saved.id]
    store.dispatch('flash/queueMessage', { type:'success', text:`Item ${saved.id} salvato con successo.` })

    // fa pulire i previewFiles nel child (hai watcher con immediate su resetKey)
    resetKey.value++
  } catch (e) {
    if (e.response?.status === 409) {
      const c = e.response.data.conflicts?.[0]
      if (c) {
        const i = order.items.findIndex(x => x.id === c.id)
        if (i !== -1) order.items.splice(i, 1, c.updated_item)
        delete modifiedItems.value[c.id]
      }
      store.dispatch('flash/queueMessage', { type:'error', text:`Item ${id} in conflitto. Dati ricaricati.` })
    } else {
      store.dispatch('flash/queueMessage', { type:'error', text:`Errore nel salvataggio di item ${id}.` })
    }
  } finally {
    savingItems.value = savingItems.value.filter(x => x !== id)
  }
}


async function saveAll() {
  if (!canSaveAll.value) return

  const fd = new FormData()

  // Debug FormData
  const dbg = []
  fd.forEach((v, k) => {
    dbg.push([k, typeof v === 'object' && v?.name ? `File(${v.name})` : v])
  })
  console.table(dbg)
  // Rimuovi in produzione

  // ✅ campi ordine (se il backend li usa nel bulk)
  fd.append('order_id', String(order.id))
  fd.append('has_ragno', form.has_ragno ? '1' : '0')
  fd.append('ragnista_id', form.ragnista_id ?? '')
  fd.append('machinery_time', String(form.machinery_time_hh * 60 + form.machinery_time_mm))

  // ✅ items modificati
  const items = Object.values(modifiedItems.value)

  items.forEach((it, i) => appendItemToFormData(fd, it, i))

  try {
    const res = await axios.post(
      route('warehouse-order-items.save-items-bulk'), // ⬅️ usa la route che hai già
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    const savedItems = res.data?.savedItems || []
    // 🔁 merge reattivo sugli items dell’ordine
    savedItems.forEach(saved => {
      const total = Number(saved.machinery_time_fraction) || 0
      saved.machinery_time_fraction_hh = Math.floor(total / 60)
      saved.machinery_time_fraction_mm = total % 60

      const idx = order.items.findIndex(i => i.id === saved.id)
      if (idx !== -1) {
        order.items.splice(idx, 1, {
          ...order.items[idx],
          ...saved,
          // difese se il backend non rimanda sempre tutto
          holder:        saved.holder        ?? order.items[idx].holder,
          cer_code:      saved.cer_code      ?? order.items[idx].cer_code,
          images:        saved.images        ?? order.items[idx].images,
          warehouse:     saved.warehouse     ?? order.items[idx].warehouse,
          journey_cargo: saved.journey_cargo ?? order.items[idx].journey_cargo,
          explosions:    saved.explosions    ?? order.items[idx].explosions,
        })
      }
    })

    // 🧹 pulizia buffer + refresh figli (pulisce preview)
    modifiedItems.value = {}
    resetKey.value++

    store.dispatch('flash/queueMessage', {
      type: 'success',
      text: 'Tutti gli elementi modificati sono stati aggiornati con successo',
    })
  } catch (e) {
    if (e.response?.status === 409) {
      const conflicts = e.response.data?.conflicts || []
      conflicts.forEach(c => {
        if (c?.updatedItem) {
          const idx = order.items.findIndex(i => i.id === c.id)
          if (idx !== -1) order.items.splice(idx, 1, c.updatedItem)
        }
        store.dispatch('flash/queueMessage', {
          type: 'error',
          text: `Item ${c?.id ?? 'N/D'} in conflitto`,
        })
      })
    } else {
      store.dispatch('flash/queueMessage', {
        type: 'error',
        text: 'Errore nel salvataggio massivo',
      })
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
