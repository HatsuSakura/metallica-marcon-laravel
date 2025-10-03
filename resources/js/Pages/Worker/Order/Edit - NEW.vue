<script setup>
import { reactive, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import OrderItemRow from './Components/OrderItemRow.vue'
import { normalizeExplosionsArray } from '@/utils/orderItemExplosions - OLD.js'

/* -------- props -------- */
const props = defineProps({
  order: Object,
  catalog:    { type: Array, required: true },
  recipes:    { type: Array, default: () => [] },
  warehouses: Array,
  warehouseChiefs: Array,
  warehouseManagers: Array,
  warehouseWorkers: Array,
})

/* -------- utils -------- */
const deepClone = (x) => (x == null ? x : JSON.parse(JSON.stringify(x)))
function appendScalar(fd, key, val) {
  if (val === undefined) return
  if (typeof val === 'boolean') fd.append(key, val ? '1' : '0')
  else if (val == null)         fd.append(key, '')
  else                          fd.append(key, String(val))
}
function hasTreeChildren(list) {
  return Array.isArray(list) && list.some(n =>
    Array.isArray(n?.children) || Array.isArray(n?.children_recursive) || Array.isArray(n?.childrenRecursive)
  )
}
function buildTreeFromFlat(list) {
  const arr = Array.isArray(list) ? list : []
  const byId = new Map()
  const roots = []
  arr.forEach(n => byId.set(n.id, { ...n, children: [] }))
  byId.forEach(n => {
    const pid = n.parent_explosion_id
    if (pid) {
      const p = byId.get(pid)
      p ? p.children.push(n) : roots.push(n)
    } else roots.push(n)
  })
  return roots
}
function pickExplosionTree(item) {
  const candidates = [
    item?.explosions,
    item?.explosions_root,
    item?.explosionsRoot,
    item?.explosionsTree,
    item?.explosionsRecursive,
  ].filter(Array.isArray)
  if (!candidates.length) return []
  const arr = candidates[0]
  return hasTreeChildren(arr) ? deepClone(arr) : buildTreeFromFlat(arr)
}

/* -------- stato centrale -------- */
const itemsById = reactive({})       // id -> item "vero" per la UI
const dirtyById = reactive({})       // id -> patch accumulate (solo chiavi modificate)
const stagedImagesById = reactive({})// id -> File[]

;(props.order?.items || []).forEach((raw) => {
  const item = { ...raw, explosions: pickExplosionTree(raw) }
  itemsById[item.id] = item
})

/* -------- grouping per warehouse (solo lettura) -------- */
const itemsByWarehouse = computed(() => {
  const map = (props.warehouses || []).reduce((acc, w) => {
    acc[w.id] = { id: w.id, denominazione: w.denominazione, items: [] }
    return acc
  }, {})
  Object.values(itemsById).forEach(it => {
    const wid   = it?.warehouse_download?.id ?? 0
    const denom = it?.warehouse_download?.denominazione ?? '—'
    if (!map[wid]) map[wid] = { id: wid, denominazione: denom, items: [] }
    map[wid].items.push(it)
  })
  return map
})

/* -------- API locale -------- */
function applyPatch(id, patch) {
  const it = itemsById[id]
  if (!it) return
  // merge nello stato visibile
  Object.assign(it, patch)
  // accumula differenze (per save)
  dirtyById[id] = { ...(dirtyById[id] || { id }), ...patch }
}

function setStagedImages(id, files) {
  stagedImagesById[id] = (files || []).filter(f => f instanceof File)
  // NB: nel bulk spingeremo SOLO i file,
  //     ma non tocchiamo it.images persistite
  if (stagedImagesById[id].length) {
    dirtyById[id] = { ...(dirtyById[id] || { id }), /* nessuna key images "persistita" qui */ }
  }
}

/* -------- SAVE (bulk e single passano di qui) -------- */
function appendItemToFormData(fd, itemPatch, i) {
  const prefix = `items[${i}]`
  // id sempre
  appendScalar(fd, `${prefix}[id]`, itemPatch.id)

  // scalari espliciti che vuoi salvare (evita di pushare oggetti interi)
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
  scalarKeys.forEach(k => {
    if (k in itemPatch) appendScalar(fd, `${prefix}[${k}]`, itemPatch[k])
  })

  // esplosioni: prendi l’albero CORRENTE dello stato (non la patch)
  const stateItem = itemsById[itemPatch.id]
  const clean = normalizeExplosionsArray(stateItem?.explosions || [])
  fd.append(`${prefix}[explosions]`, JSON.stringify(clean))

  // immagini: SOLO i file (staged)
  const files = stagedImagesById[itemPatch.id] || []
  files.forEach((file, j) => fd.append(`${prefix}[images][${j}]`, file))
}

async function saveBulk(ids = null) {
  const toSave = ids
    ? ids.map(id => dirtyById[id]).filter(Boolean)
    : Object.values(dirtyById)

  if (!toSave.length) return

  const fd = new FormData()
  toSave.forEach((patch, i) => appendItemToFormData(fd, patch, i))

  try {
    const res = await axios.post(
      route('warehouse-order-items.save-items-bulk'),
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    const savedItems = res.data?.savedItems || []
    savedItems.forEach(saved => {
      // merge nello stato “vero” + pulizia dirty
      itemsById[saved.id] = {
        ...itemsById[saved.id],
        ...saved,
        explosions: pickExplosionTree(saved), // assicura albero
      }
      delete dirtyById[saved.id]
      // staged images: azzera la coda dello staging (preview restano nel child)
      stagedImagesById[saved.id] = []
    })
  } catch (e) {
    console.error('Bulk save error', e)
  }
}

/* -------- handlers dagli OrderItem -------- */
function onPatch({ id, patch }) {
  applyPatch(id, patch)
}
function onStageImages({ id, files }) {
  setStagedImages(id, files)
}
function onSaveOne(id) {
  saveBulk([id])
}

/* -------- form ordine minimale (se serve) -------- */
const form = useForm({
  has_ragno: props.order.has_ragno === 1,
  ragnista_id: props.order.ragnista_id || '',
  machinery_time: props.order.machinery_time || 0,
  machinery_time_hh: Math.floor(props.order.machinery_time / 60) || 0,
  machinery_time_mm: props.order.machinery_time % 60 || 0,
})

/* -------- computed utili -------- */
const canSaveAll = computed(() => Object.keys(dirtyById).length > 0)
</script>

<template>
  <div>
    <!-- header / tabs come preferisci ... -->

    <!-- Gruppi per magazzino -->
    <div v-for="group in Object.values(itemsByWarehouse)" :key="group.id" class="mb-8">
      <h2 class="text-lg font-semibold mb-2">
        {{ group.denominazione }} ({{ group.items.length }})
      </h2>

      <OrderItemRow
        v-for="it in group.items"
        :key="it.id"
        :item="it"
        :catalog="props.catalog"
        :recipes="props.recipes"
        :staged-images="stagedImagesById[it.id] || []"
        @patch="onPatch"
        @stage-images="onStageImages"
        @save="onSaveOne"
      />
    </div>

    <div class="flex justify-end gap-2 mt-6">
      <button class="btn btn-success" :disabled="!canSaveAll" @click="saveBulk()">
        Salva TUTTO
      </button>
    </div>
  </div>
</template>
