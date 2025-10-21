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
      <template v-for="(group, warehouseId) in itemsByWarehouse" :key="warehouseId">
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
        <div role="tabpanel" class="tab-content bg-base-100 pt-4">
          <!-- OPZIONI ORDINE -->
          <div class="flex flex-row justify-between items-center sticky top-16 bg-base-100 z-10 py-4">
            <!-- + Aggiungi -->
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
              <button v-else class="btn btn-disabled" tabindex="-1" role="button" aria-disabled="true">
                <font-awesome-icon :icon="['fas','plus']" class="text-2xl"/>
                Aggiungi Elemento
              </button>
            </div>

            <!-- RAGNO -->
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

            <!-- Salva Tutto -->
            <div>
              <button v-if="Number(warehouseId) === currentWarehouse"
                @click="saveAll"
                class="btn btn-outline btn-success"
                :disabled="!canSaveAll"
              >
                <font-awesome-icon :icon="['fas','floppy-disk']" class="text-2xl"/>
                Salva INTERO Ordine
              </button>
              <button v-else class="btn btn-disabled" tabindex="-1" role="button" aria-disabled="true">
                <font-awesome-icon :icon="['fas','floppy-disk']" class="text-2xl"/>
                Salva INTERO Ordine
              </button>
            </div>
          </div>
          <!-- /OPZIONI ORDINE -->

          <!-- ITEMS DELLA WAREHOUSE CORRENTE -->
          <div v-if="Number(warehouseId) === currentWarehouse" class="flex flex-col gap-2">
            <template v-if="group.items.length">
              <OrderItemRow
                v-for="(item, idx) in group.items"
                :key="item.id"
                :item="item"
                :index="idx"
                :staged-images="stagedImages[item.id] || []" 
                :warehouseChiefs="filteredChiefs"
                :warehouseManagers="filteredManagers"
                :warehouseWorkers="filteredWorkers"
                :parentHasRagno="form.has_ragno"
                :parentMachineryTime="form.machinery_time"
                :saving="savingItems.includes(item.id)"
                :recipes="props.recipes"
                :catalog="props.catalog"

                :has-dirty-explosions="hasDirtyExplosions(item.id)"
                :has-staged-images="hasStagedImages(item.id)"
                :has-dirty-scalars="hasDirtyScalars(item.id)"

                @update="handleItemUpdate"
                @save-one="handleSaveOne"

                @update-is-ragnabile-toggle="onRowToggle"
                @update-manual-machinery-time="onRowManual"
                @reset-manual-machinery-time="onRowReset"

                @item-not-found="onItemNotFound"
                @item-found="onItemFound"

                @images:delete-existing="onDeleteExisting"
                @images:add="onImagesAdd"
                @images:remove="onImagesRemove"

                @explosion:add-root="onExpAddRoot"
                @explosion:add-child="onExpAddChild"
                @explosion:remove="onExpRemove"
                @explosion:update-node="onExpUpdateNode"
                @explosion:toggle-collapse="onExpToggle"
                @explosion:set-recipe="onExpSetRecipe"
                @explosion:apply-recipe="onExpApplyRecipe"
              />
            </template>
            <div v-else class="alert alert-info">
              Nessun elemento presente in questo magazzino.
            </div>
          </div>

          <!-- ITEMS DI ALTRE WAREHOUSE -->
          <div v-else class="flex flex-col gap-2">
            <template v-if="group.items.length">
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
import dayjs from 'dayjs'
import { useStore } from 'vuex'
import OrderItemRow from './Components/OrderItemRow.vue'
import OrderSpace from '@/Components/OrderSpace.vue'
import OrderItemRowNotMyWarehouse from './Components/OrderItemRowNotMyWarehouse.vue'

// Reducer/utility pure per explosion
import {
  makeTempIdFactory,
  addRoot, addChild, removeNode, updateNode, toggleCollapse,
  applyRecipeAt, hasExplodedChildren, normalizeForApi,
} from '@/utils/orderItemExplosions.js'

const props = defineProps({
  order: Object,
  catalog: { type: Array, required: true },   // [{id,name,type}]
  recipes: { type: Array, default: () => []}, // [{id,name,version, items/tree?}]
  warehouses: Array,
  warehouseChiefs: Array,
  warehouseManagers: Array,
  warehouseWorkers: Array,
})

const order = reactive({ ...props.order })               // SSOT: gli items vivono qui
order.items = (order.items || []).map(it => {
  const tree_flat = rehydrateExplosionsIfFlat(it.explosions || [])
  const tree_hyer = hydrateCatalogOnTree(tree_flat, props.catalog)
  return { ...it, explosions: tree_hyer }
})
const page  = usePage()
const store = useStore()
const user  = computed(() => page.props.user)

// Stato di salvataggio
function hasDirtyExplosions(id) {
  return !!modifiedItems.value[id]?.explosions
}
function hasStagedImages(id) {
  return (stagedImages.value[id]?.length || 0) > 0
}
function hasDirtyScalars(id) {
  const cur = modifiedItems.value[id]
  if (!cur) return false
  // considera dirty “scalare” tutto ciò che non è immagini/esplosioni/meta
  return Object.keys(cur).some(k =>
    k !== 'id' &&
    k !== 'images' &&
    k !== 'explosions' &&
    k !== 'has_exploded_children'
  )
}




/* ========= ORDER form (ragno) ========= */
const form = useForm({
  has_ragno: props.order.has_ragno === 1,
  ragnista_id: props.order.ragnista_id || '',
  machinery_time: props.order.machinery_time || 0,
  machinery_time_hh: Math.floor((props.order.machinery_time || 0) / 60),
  machinery_time_mm: (props.order.machinery_time || 0) % 60,
})

const currentWarehouse = computed(() =>
  user.value.warehouses?.[0]?.id ?? 1
)
const filteredChiefs   = computed(() =>
  props.warehouseChiefs.filter(w => w.warehouses.some(x => x.id === currentWarehouse.value))
)
const filteredManagers = computed(() =>
  props.warehouseManagers.filter(w => w.warehouses.some(x => x.id === currentWarehouse.value))
)
const filteredWorkers = computed(() =>
  props.warehouseWorkers.filter(w => w.warehouses.some(x => x.id === currentWarehouse.value))
)

/* ========= Helpers ========= */
const formatDate = v => (v ? dayjs(v).format('YYYY-MM-DD HH:mm:ss') : null)

// Normalizza un item (scalari + date) per l’API
function serializeItemForApi(it) {
  return {
    id: it.id,
    holder_quantity: it.holder_quantity,
    cer_code_id: it.cer_code_id,
    weight_gross: it.weight_gross,
    weight_tare: it.weight_tare,
    weight_net: it.weight_net,
    is_ragnabile: it.is_ragnabile ? 1 : 0,
    machinery_time_fraction: it.is_ragnabile
      ? ((Number(it.machinery_time_fraction_hh) || 0) * 60) + (Number(it.machinery_time_fraction_mm) || 0)
      : 0,
    is_holder_dirty: it.is_holder_dirty ? 1 : 0,
    total_dirty_holders: it.total_dirty_holders,
    is_holder_broken: it.is_holder_broken ? 1 : 0,
    total_broken_holders: it.total_broken_holders,
    has_selection: it.has_selection ? 1 : 0,
    selection_time: it.has_selection
      ? ((Number(it.selection_time_hh) || 0) * 60) + (Number(it.selection_time_mm) || 0)
      : 0,
    warehouse_downaload_worker_id: it.warehouse_downaload_worker_id,
    warehouse_downaload_dt: formatDate(it.warehouse_downaload_dt),
    warehouse_weighing_worker_id: it.warehouse_weighing_worker_id,
    warehouse_weighing_dt: formatDate(it.warehouse_weighing_dt),
    warehouse_selection_worker_id: it.warehouse_selection_worker_id,
    warehouse_selection_dt: formatDate(it.warehouse_selection_dt),
    warehouse_notes: it.warehouse_notes,
    warehouse_non_conformity: it.warehouse_non_conformity,
    has_exploded_children: it.has_exploded_children ?? (hasExplodedChildren(it.explosions || []) ? 1 : 0),
    updated_at: it.updated_at,
    //explosions, // <- SEMPRE presente
  }
}

/* ========= Items per warehouse (SSOT: order.items) ========= */
const itemsByWarehouse = computed(() => {
  const map = (props.warehouses || []).reduce((acc, w) => {
    acc[w.id] = { id: w.id, denominazione: w.denominazione, items: [], count: 0 }
    return acc
  }, {})

  for (const it of (order.items || [])) {
    const wid   = it?.warehouse_download?.id ?? 0
    const denom = it?.warehouse_download?.denominazione ?? 'none'
    if (!map[wid]) map[wid] = { id: wid, denominazione: denom, items: [], count: 0 }
    map[wid].items.push(it)
  }

  Object.values(map).forEach(g => { g.count = g.items.length })
  return map
})

/* ========= Explosion: temp-id factory ========= */
const tempIdFactories = new Map()
function idFactoryFor(itemId) {
  if (!tempIdFactories.has(itemId)) {
    tempIdFactories.set(itemId, makeTempIdFactory(-1))
  }
  return tempIdFactories.get(itemId)
}

/* ========= Helper per UI Explosions ========= */
function isFlatExplosionRow(n) {
  return n && !('children' in n) && (
    'parent_explosion_id' in n ||
    'parentId' in n ||
    'parent_node_id' in n ||
    'parent_id' in n
  )
}

function rehydrateExplosionsIfFlat(maybeFlat) {
  if (!Array.isArray(maybeFlat)) return []
  if (!maybeFlat.some(isFlatExplosionRow)) return maybeFlat // già ad albero

  const byId = new Map()
  maybeFlat.forEach(r => byId.set(r.id, { ...r, children: [] }))

  const roots = []
  maybeFlat.forEach(r => {
    const node = byId.get(r.id)
    const pid = r.parent_explosion_id ?? r.parentId ?? r.parent_node_id ?? r.parent_id ?? null
    if (pid && byId.has(pid)) byId.get(pid).children.push(node)
    else roots.push(node)
  })

  const sortRec = (arr) => {
    arr.sort((a,b) => (a.sort ?? 0) - (b.sort ?? 0))
    arr.forEach(n => n.children?.length && sortRec(n.children))
  }
  sortRec(roots)

  return roots
}

function hydrateCatalogOnTree(tree, catalog) {
  const map = new Map((catalog || []).map(c => [c.id, c]))
  const clone = JSON.parse(JSON.stringify(tree || []))
  const walk = (arr=[]) => {
    for (const n of arr) {
      if (!n.catalog_item && n.catalog_item_id && map.has(n.catalog_item_id)) {
        const ci = map.get(n.catalog_item_id)
        n.catalog_item = { id: ci.id, name: ci.name, type: ci.type }
      }
      if (n.children?.length) walk(n.children)
    }
  }
  walk(clone)
  return clone
}

function pickBetterTree(serverTree, localTree) {
  const count = (arr=[]) => arr.reduce((a,n)=>a+1+count(n.children||[]),0)
  return count(serverTree) >= count(localTree) ? serverTree : localTree
}


/* ========= Helpers SSOT per tree ========= */
function getItemIndex(itemId) {
  return Array.isArray(order.items) ? order.items.findIndex(i => i.id === itemId) : -1
}
function getTree(itemId) {
  const idx = getItemIndex(itemId)
  return idx === -1 ? [] : (order.items[idx].explosions ?? [])
}
function setTree(itemId, newTree) {
  const idx = getItemIndex(itemId)
  if (idx === -1) return
  const it = order.items[idx]
  order.items.splice(idx, 1, {
    ...it,
    explosions: newTree,
    has_exploded_children: hasExplodedChildren(newTree) ? 1 : 0,
  })
  modifiedItems.value[itemId] = {
    ...(modifiedItems.value[itemId] || { id: itemId }),
    explosions: newTree,
    has_exploded_children: hasExplodedChildren(newTree) ? 1 : 0,
  }
}

/* ========= Buffers & state ========= */
const modifiedItems = ref({}) // patch per bulk/single
const stagedImages  = ref({}) // { [itemId]: File[] }
const savingItems   = ref([])

/* ========= Handlers EXPLOSION (dal child) ========= */
function onExpAddRoot({ itemId }) {
  const nextId = idFactoryFor(itemId)
  setTree(itemId, addRoot(getTree(itemId), nextId))
}
function onExpAddChild({ itemId, parentId }) {
  const nextId = idFactoryFor(itemId)
  setTree(itemId, addChild(getTree(itemId), parentId, nextId))
}
function onExpRemove({ itemId, id }) {
  setTree(itemId, removeNode(getTree(itemId), id))
}
function onExpUpdateNode({ itemId, id, patch, hints }) {
  setTree(itemId, updateNode(getTree(itemId), id, patch, hints))
}
function onExpToggle({ itemId, id, collapsed }) {
  setTree(itemId, toggleCollapse(getTree(itemId), id, collapsed))
}
function onExpSetRecipe({ itemId, id, recipeId }) {
  setTree(itemId, updateNode(getTree(itemId), id, { recipe_id: recipeId, _selectedRecipeId: recipeId }))
}

function normalizeRecipeTemplate(arr) {
  const norm = (n) => {
    const ci   = n?.catalog_item ?? n?.catalogItem ?? null
    const ciId = n?.catalog_item_id ?? n?.catalogItemId ?? ci?.id ?? null
    const kids = n?.children ?? n?.children_recursive ?? n?.childrenRecursive ?? n?.nodes ?? n?.items ?? []

    return {
      // l’editor/reducer si aspetta queste chiavi
      catalog_item_id: ciId,
      catalog_item: ci ? { id: ci.id, name: ci.name, type: ci.type } : null,
      children: (Array.isArray(kids) ? kids : []).map(norm),
    }
  }
  return (Array.isArray(arr) ? arr : []).map(norm)
}

// Sostituisci la tua getRecipeTreeById attuale con questa
function getRecipeTreeById(recipeId) {
  const r = (props.recipes || []).find(x => Number(x.id) === Number(recipeId))
  if (!r) return []

  // supporta tutte le shape possibili dal BE
  const raw =
    r?.nodes ??
    r?.items ??
    r?.tree ??
    r?.components ??
    r?.root_nodes ??
    r?.rootNodes ??
    []

  return normalizeRecipeTemplate(raw)
}
function onExpApplyRecipe({ itemId, id: nodeId, recipeId }) {
  const tree = getRecipeTreeById(recipeId)
  if (!Array.isArray(tree) || !tree.length) {
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Ricetta non trovata o senza elementi.' })
    return
  }
  const nextId = idFactoryFor(itemId)
  setTree(itemId, applyRecipeAt(getTree(itemId), nodeId, tree, nextId))
}

/* ========= Import item da altre warehouse (immutate) ========= */
const importingItems = ref([])
async function onImportItem({ id }) {
  if (importingItems.value.includes(id)) return
  importingItems.value.push(id)

  const currentGroup = itemsByWarehouse.value?.[currentWarehouse.value]
  const currentJourneyCargo = (() => {
    if (currentGroup?.items?.length) {
      for (const it of currentGroup.items) {
        const jc = it?.journey_cargo
        const found = jc?.id ?? jc?.cargo_id ?? null
        if (found != null) return found
      }
    } else {
      const list = order?.journey?.journey_cargos
      if (!Array.isArray(list)) return null
      const hit = list.find(jc => Number(jc?.warehouse_id) === Number(currentWarehouse.value))
      return hit?.id ?? null
    }
    return null
  })()

  if (currentJourneyCargo == null) {
    importingItems.value = importingItems.value.filter(x => x !== id)
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Viaggio non passato da questo magazzino.' })
    return
  }

  try {
    const res = await axios.post(`/api/warehouse-order-items/move-journey-cargo/${id}`, {
      journey_cargo_id: currentJourneyCargo, warehouse_id: currentWarehouse.value
    })
    const updated = res.data?.orderItem ?? res.data
    const wh = (props.warehouses || []).find(w => Number(w.id) === Number(currentWarehouse.value))
    updated.warehouse_download = wh
      ? { id: wh.id, denominazione: wh.denominazione }
      : { id: Number(currentWarehouse.value), denominazione: '—' }

    const idx = order.items.findIndex(i => i.id === updated.id)
    if (idx !== -1) {
      const prev = order.items[idx]
      const merged = {
        ...prev,
        ...updated,
        holder:        updated.holder        ?? prev.holder,
        cer_code:      updated.cer_code      ?? prev.cer_code,
        images:        updated.images        ?? prev.images,
        warehouse:     updated.warehouse     ?? prev.warehouse,
        journey_cargo: updated.journey_cargo ?? prev.journey_cargo,
      }
      order.items.splice(idx, 1, merged)
    } else {
      order.items.push(updated)
    }
    order.items = [...order.items]

    store.dispatch('flash/queueMessage', { type: 'success', text: `Materiale importato (item ${updated.id}).` })
  } catch (e) {
    console.error(e)
    store.dispatch('flash/queueMessage', { type: 'error', text: `Errore di import (item ${id}).` })
  } finally {
    importingItems.value = importingItems.value.filter(x => x !== id)
  }
}

/* ========= NOT FOUND / FOUND ========= */
function optimisticUpdate(id, patch) {
  const idx = order.items.findIndex(i => i.id === id)
  if (idx === -1) return { revert: () => {} }
  const prev = { ...order.items[idx] }
  order.items[idx] = { ...order.items[idx], ...patch }
  return { revert: () => { order.items[idx] = prev } }
}

async function onItemNotFound({ id, updated_at }) {
  console.log('Not found item', id)
  const { revert } = optimisticUpdate(id, { is_not_found: 1 })
  try {
    await axios.patch(route('api.warehouse-order-items.flag-not-found', { orderItem: id }), {
      is_not_found: true, Accept: 'application/json'
    })
    

    store.dispatch('flash/queueMessage', { type: 'success', text: `Item ${id} dichiarato NON TROVATO.` })
  } catch (e) {
    revert()
    store.dispatch('flash/queueMessage', { type: 'error', text: `Errore nel flag NOT FOUND per item ${id}.` })
  }
}
async function onItemFound({ id, updated_at }) {
  const { revert } = optimisticUpdate(id, { is_not_found: 0 })
  try {
    await axios.patch(route('api.warehouse-order-items.flag-not-found', { orderItem: id }), {
      is_not_found: false, Accept: 'application/json'
    })
    store.dispatch('flash/queueMessage', { type: 'success', text: `Item ${id} ripristinato come TROVATO.` })
  } catch (e) {
    revert()
    store.dispatch('flash/queueMessage', { type: 'error', text: `Errore nel flag TROVATO per item ${id}.` })
  }
}

/* ========= IMMAGINI: eventi dal child (SSOT nel parent) ========= */
// Aggiunge file allo staging e marca l’item come modificato (solo images: File[])
function onImagesAdd({ itemId, files }) {
  const prev = stagedImages.value[itemId] || []
  const next = [...prev, ...(Array.isArray(files) ? files : [])].filter(f => f instanceof File)
  stagedImages.value[itemId] = next
  modifiedItems.value[itemId] = { ...(modifiedItems.value[itemId] || { id: itemId }), images: next }
}
// Rimuove dallo staging per indice o riferimento file
function onImagesRemove({ itemId, index, file }) {
  const arr = stagedImages.value[itemId] || []
  let next = arr
  if (typeof index === 'number' && index >= 0 && index < arr.length && (!file || arr[index] === file)) {
    next = [...arr.slice(0, index), ...arr.slice(index + 1)]
  } else if (file) {
    next = arr.filter(f => f !== file)
  }
  stagedImages.value[itemId] = next
  if (next.length) {
    modifiedItems.value[itemId] = { ...(modifiedItems.value[itemId] || { id: itemId }), images: next }
  } else {
    // rimuovi 'images' dal dirty se vuoto
    const cur = modifiedItems.value[itemId]
    if (cur) {
      const { images, ...rest } = cur
      modifiedItems.value[itemId] = { ...rest }
      if (!Object.keys(modifiedItems.value[itemId]).length) delete modifiedItems.value[itemId]
    }
  }
}
// Cancella un'immagine PERSISTITA (immediata, non dirty)
async function onDeleteExisting({ itemId, image }) {
  // optimistic UI
  const idx = order.items.findIndex(i => i.id === itemId)
  if (idx === -1) return
  const prev = order.items[idx].images || []
  order.items[idx] = { ...order.items[idx], images: prev.filter(x => x.id !== image.id) }

  try {
    await axios.delete(route('warehouse-manager.order-item.image.destroy', { orderItem: itemId, image: image.id }), {
      headers: { Accept: 'application/json' }
    })
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Immagine eliminata.' })
  } catch (e) {
    // revert su errore
    order.items[idx] = { ...order.items[idx], images: prev }
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore eliminazione immagine.' })
  }
}

/* ========= PATCH generiche dagli item ========= */
function handleItemUpdate(updatedItem) {
  const id = updatedItem?.id
  if (!id) return

  // merge nel buffer dirty
  const prevDirty = modifiedItems.value[id] || { id }
  // NON trascinare files persistiti qui
  const { images, ...rest } = updatedItem
  modifiedItems.value[id] = { ...prevDirty, ...rest }

  // merge sull’SSOT visualizzato
  const idx = order.items.findIndex(i => i.id === id)
  if (idx !== -1) {
    order.items[idx] = { ...order.items[idx], ...rest }
  }
}

/* ========= Ragno: ridistribuzione ========= */
const initialOrder = {
  has_ragno: props.order.has_ragno,
  ragnista_id: props.order.ragnista_id,
  machinery_time: props.order.machinery_time,
}
const orderDirty = computed(() => {
  const currentMachinery = form.machinery_time_hh * 60 + form.machinery_time_mm
  return (
    form.has_ragno !== initialOrder.has_ragno ||
    String(form.ragnista_id) !== String(initialOrder.ragnista_id) ||
    currentMachinery !== initialOrder.machinery_time
  )
})
const manualRows = ref({})
function recalcMachineryFractions() {
  const active = (order.items || []).filter(i => i.is_ragnabile)
  if (!active.length) return
  const manualSum = Object.values(manualRows.value).reduce((a, b) => a + b, 0)
  const leftover  = Math.max(0, (form.machinery_time_hh * 60 + form.machinery_time_mm) - manualSum)
  const auto = active.filter(i => manualRows.value[i.id] == null)
  const perAuto = auto.length ? Math.floor(leftover / auto.length) : 0
  active.forEach(item => {
    const mins = manualRows.value[item.id] != null ? manualRows.value[item.id] : perAuto
    handleItemUpdate({
      id: item.id,
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

/* ========= Save flags ========= */
const itemsDirty  = computed(() => Object.keys(modifiedItems.value).length > 0)
const canSaveAll  = computed(() => orderDirty.value || itemsDirty.value)
const canCloseOrder = computed(() => {
  const group = itemsByWarehouse.value?.[currentWarehouse.value]
  if (!group) return false
  const items = group.items || []
  if (!items.length) return false
  return items.every(it => !!it.warehouse_downaload_dt && !!it.warehouse_weighing_dt && !!it.warehouse_selection_dt)
})

/* ========= Helpers FormData ========= */
function appendScalar(fd, key, val) {
//  if (typeof val === 'boolean') fd.append(key, val ? '1' : '0')
//  else if (val == null)         fd.append(key, '')
//  else                          fd.append(key, String(val))
  if (val === undefined) return;                    // ✅ non toccare il campo
  if (typeof val === 'boolean') fd.append(key, val ? '1' : '0')
  else if (val === null)        fd.append(key, '')  // ✅ null esplicito = svuota
  else                          fd.append(key, String(val))
}

/** Appende un item (scalari + images + explosions) */
function appendItemToFormData(fd, item, index = null) {
  const clean = serializeItemForApi(item) // formatta date/booleans e calcola time
  const prefix = index == null ? '' : `items[${index}]`

  // id
  appendScalar(fd, prefix ? `${prefix}[id]` : 'id', clean.id)

  // scalari selezionati (esclusi images/explosions)
  const scalarKeys = [
    'holder_quantity','cer_code_id',
    'weight_gross','weight_tare','weight_net',
    'is_ragnabile','machinery_time_fraction',
    'is_holder_dirty','total_dirty_holders',
    'is_holder_broken','total_broken_holders',
    'has_selection','selection_time',
    'warehouse_downaload_worker_id','warehouse_downaload_dt',
    'warehouse_weighing_worker_id','warehouse_weighing_dt',
    'warehouse_selection_worker_id','warehouse_selection_dt',
    'warehouse_notes','warehouse_non_conformity',
    'has_exploded_children','updated_at',
  ]
  scalarKeys.forEach(k => {
    if (k in clean) appendScalar(fd, prefix ? `${prefix}[${k}]` : k, clean[k])
  })

  // explosions normalizzate -> JSON
  const norm = normalizeForApi(item.explosions || [])
  fd.append(prefix ? `${prefix}[explosions]` : 'explosions', JSON.stringify(norm))

  // images -> SOLO File nuovi
  const files = Array.isArray(item.images) ? item.images : []
  files.forEach((file, j) => {
    if (file instanceof File) {
      fd.append(prefix ? `${prefix}[images][${j}]` : `images[${j}]`, file)
    }
  })
}

/* ========= Save One ========= */
async function handleSaveOne({ id }) {
  if (!id || savingItems.value.includes(id)) return
  savingItems.value.push(id)
  console.log('Saving item', id)

  // ricompone l’item corrente partendo dall’SSOT + patch dirty
  const srcIdx = order.items.findIndex(i => i.id === id)
  if (srcIdx === -1) return
  const base = order.items[srcIdx]
  const patch = modifiedItems.value[id] || {}
  const item = { ...base, ...patch }

  try {
    const fd = new FormData()
    appendItemToFormData(fd, item, null) // singolo: nessun prefix items[i]
    fd.append('_method', 'PUT')

    const res = await axios.post(`/api/warehouse-order-items/${id}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    const payload = res.data?.item ?? res.data?.orderItem ?? res.data
    const saved   = Array.isArray(payload) ? payload[0] : payload

    // hh/mm ricalcolati
    const total = Number(saved.machinery_time_fraction) || 0
    saved.machinery_time_fraction_hh = Math.floor(total / 60)
    saved.machinery_time_fraction_mm = total % 60

    // merge reattivo
    const idx = order.items.findIndex(i => i.id === saved.id)
    if (idx !== -1) {

      const prev = order.items[idx]
      const prevTree          = prev.explosions || []
      const savingTree        = Array.isArray(saved.explosions) ? saved.explosions : []
      const savingTree_reHyd  = rehydrateExplosionsIfFlat(savingTree)
      const savingTree_reHyd2 = hydrateCatalogOnTree(savingTree_reHyd, props.catalog)
      const mergedTree  = pickBetterTree(savingTree_reHyd2, prevTree)

      order.items.splice(idx, 1, {
        ...order.items[idx],
        ...saved,
        holder:        saved.holder        ?? order.items[idx].holder,
        cer_code:      saved.cer_code      ?? order.items[idx].cer_code,
        images:        saved.images        ?? order.items[idx].images,
        warehouse:     saved.warehouse     ?? order.items[idx].warehouse,
        journey_cargo: saved.journey_cargo ?? order.items[idx].journey_cargo,
        explosions:    mergedTree //saved.explosions    ?? order.items[idx].explosions,
      })
      order.items = [...order.items]
    }

    delete modifiedItems.value[saved.id]
    stagedImages.value[saved.id] = [] // <- pulizia anteprime locali
    store.dispatch('flash/queueMessage', { type:'success', text:`Item ${saved.id} salvato con successo.` })
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

/* ========= Save All ========= */
async function saveAll() {
  if (!canSaveAll.value) return

  const fd = new FormData()
  // campi ordine
  fd.append('order_id', String(order.id))
  fd.append('has_ragno', form.has_ragno ? '1' : '0')
  fd.append('ragnista_id', form.ragnista_id ?? '')
  fd.append('machinery_time', String(form.machinery_time_hh * 60 + form.machinery_time_mm))

  // items modificati (restituiti già normalizzati lato explosions)
  const items = Object.values(modifiedItems.value).map(patch => {
    const idx  = order.items.findIndex(i => i.id === patch.id)
    const base = idx !== -1 ? order.items[idx] : {}
    return { ...base, ...patch }                 // dopo il map => item completo
  })
  const sentIds = items.map(it => it.id) // 👈 tracciamo chi stiamo salvando

  items.forEach((it, i) => appendItemToFormData(fd, it, i))

  try {
    const res = await axios.post(
      route('warehouse-order-items.save-items-bulk'),
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    const savedItems = res.data?.savedItems || []
    savedItems.forEach(saved => {
      const total = Number(saved.machinery_time_fraction) || 0
      saved.machinery_time_fraction_hh = Math.floor(total / 60)
      saved.machinery_time_fraction_mm = total % 60

      const idx = order.items.findIndex(i => i.id === saved.id)
      if (idx !== -1) {
        order.items.splice(idx, 1, {
          ...order.items[idx],
          ...saved,
          holder:        saved.holder        ?? order.items[idx].holder,
          cer_code:      saved.cer_code      ?? order.items[idx].cer_code,
          images:        saved.images        ?? order.items[idx].images,
          warehouse:     saved.warehouse     ?? order.items[idx].warehouse,
          journey_cargo: saved.journey_cargo ?? order.items[idx].journey_cargo,
          explosions:    saved.explosions    ?? order.items[idx].explosions,
        })
      }
    })

    // SE ho salvato anche dati relativi all'ORDINE, aggiorna l’SSOT anche per l'Order
    if (res.data?.order) {
      order.has_ragno      = !!res.data.order.has_ragno
      order.ragnista_id    = res.data.order.ragnista_id
      order.machinery_time = res.data.order.machinery_time
    }


    modifiedItems.value = {}
    // pulisci solo gli item spediti; preserva eventuali nuovi drop concorrenti rispetto a stagedImages.value = {}
    sentIds.forEach(id => { stagedImages.value[id] = [] })
    stagedImages.value = { ...stagedImages.value } // bump reattività

    store.dispatch('flash/queueMessage', { type: 'success', text: 'Tutti gli elementi modificati sono stati aggiornati con successo' })
  } catch (e) {
    if (e.response?.status === 409) {
      const conflicts = e.response.data?.conflicts || []
      conflicts.forEach(c => {
        if (c?.updatedItem) {
          const idx = order.items.findIndex(i => i.id === c.id)
          if (idx !== -1) order.items.splice(idx, 1, c.updatedItem)
        }
        store.dispatch('flash/queueMessage', { type: 'error', text: `Item ${c?.id ?? 'N/D'} in conflitto` })
      })
    } else {
      store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore nel salvataggio massivo' })
    }
  }
}

/* ========= Util vari ========= */
function getRange(start, stop, step = 1) {
  const length = Math.floor((stop - start) / step) + 1
  return Array.from({ length }, (_, i) => start + i * step)
}
</script>

<style>
.collapse-title { padding: 0.25rem 1rem !important; min-height: 2.5rem !important; }
</style>

<style scoped>
.tab.current-warehouse { display: inline-flex; align-items: center; flex-wrap: nowrap; white-space: nowrap; }
.tab.current-warehouse::before { content: attr(aria-label); display: inline-block; margin-right: 0.25rem; }
.tab.current-warehouse::after  {
  content: "\f521"; display: inline-block; font-family: "Font Awesome 5 Free"; font-weight: 900;
  margin-left: 0.25rem; vertical-align: middle;
}
</style>
