// OrderItemRow.vue
<template>
  <div class="collapse collapse-arrow border border-base-300 bg-base-100">
    <!-- HEADER -->
    <input type="checkbox" />
    <div class="collapse-title flex justify-between items-center">
      <!-- ID -->
      <div class="flex flex-col items-center justify-center">
        <div class="badge badge-outline badge-info">{{ index + 1 }}</div>
        <div class="badge badge-outline badge-info">{{ localItem.id }}</div>
      </div>
      <!-- Quantità e holder -->
      <div class="flex flex-row items-center gap-2">
        <div class="text-xl font-bold">
          {{ localItem.holder_quantity }} x
        </div>
        <div class="badge badge-primary badge-lg">
          {{ localItem.holder.name }}
        </div>
      </div>

      <!-- Codice CER -->
      <div class="tooltip tooltip-top" :class="localItem.cer_code.is_dangerous ? 'tooltip-error' : 'tooltip-info'"
        :data-tip="localItem.cer_code.description">
        <div class="badge badge-lg" :class="localItem.cer_code.is_dangerous ? 'badge-error' : 'badge-primary'">
          {{ localItem.cer_code.code }}
        </div>
      </div>
      <!-- Peso dichiarato -->
      <div class="flex items-center gap-1">
        <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl text-primary" />
        <div v-if="localItem.weight_declared">
          dichiarato
          <div class="badge badge-primary badge-lg">{{ localItem.weight_declared }} Kg</div>
        </div>
        <div v-else>
          <div v-if="localItem.is_warehouse_added" class="badge badge-info badge-lg">
            + Magazzino
          </div>
          <div v-else class="badge badge-secondary badge-lg">Nessuno</div>
        </div>
      </div>
      <!-- Descrizione -->
      <div class="w-1/2">{{ localItem.description || 'nessuna descrizione' }}</div>
      <div class="mr-6 flex flex-row gap-1">

        <div class="w-8 h-8 rounded-full flex items-center justify-center"
            :class="localItem.warehouse_downaload_dt
          ? 'bg-success text-success-content'
          : 'bg-error text-error-content'"
        >
          <font-awesome-icon :icon="['fas','truck-ramp-box']" />
        </div>
        <div class="w-8 h-8 rounded-full flex items-center justify-center"
            :class="localItem.warehouse_weighing_dt
          ? 'bg-success text-success-content'
          : 'bg-error text-error-content'"
        >
          <font-awesome-icon :icon="['fas','weight-scale']" />
        </div>
                <div class="w-8 h-8 rounded-full flex items-center justify-center"
            :class="localItem.warehouse_selection_dt
          ? 'bg-success text-success-content'
          : 'bg-error text-error-content'"
        >
          <font-awesome-icon :icon="['fas','magnifying-glass-plus']" />
        </div>

      </div>
    </div>

    <!-- START CONTENT -->
    <div v-if="localItem.is_not_found" class="flex flex-row justify-between items-center mb-2 px-4">
      <div>
        Elemento dichiarato come "non trovato".<br/>Se trovato nel frattempo: premere il pulsante a destra per ri-attivare.
      </div>
      <div>
        <button class="btn btn-primary btn-circle btn-outline btn-success" @click="itemFound()">
          <font-awesome-icon :icon="['fas', 'eye']" class="text-2xl" />
        </button>
      </div>
    </div>
    <div v-else class="collapse-content flex flex-col gap-2">
      <div class="flex flex-row justify-between items-center mb-2">
        <div class="flex flex-row items-center gap-8">
          <div class="flex flex-row items-center gap-2">
            <input type="checkbox" v-model="localItem.is_ragnabile" :true-value="1" :false-value="0" class="toggle"
              :disabled="!parentHasRagno" @change="toggleIsRagnabileInput" />
            Ragnabile
            <div v-if="localItem.is_ragnabile" class="flex flex-row items-center gap-2">
              <select id="machinery_time_fraction_hh" v-model="localItem.machinery_time_fraction_hh"
                class="select select-bordered" @change="onManualMachineryTimeInput">
                <option value="0" selected>0</option>
                <option v-for="hour in 8" :key="hour" :value="hour">{{ String(hour) }}</option>
              </select>
              <div>hh</div>
              <select id="machinery_time_fraction_mm" v-model="localItem.machinery_time_fraction_mm"
                class="select select-bordered" @change="onManualMachineryTimeInput">
                <option value="0" selected>00</option>
                <option v-for="min in 11" :key="min * 5" :value="min * 5">{{ String(min * 5).padStart(2, '0') }}</option>
              </select>
              <div>mm</div>

              <div v-if="localItem.is_machinery_time_manual" class="flex flex-row items-center gap-2">

                <font-awesome-icon :icon="['fas', 'hand']" class="text-2xl text-primary" />
                <button class="btn btn-primary btn-circle btn-outline  btn-error"
                  @click="resetManualMachineryTimeInput">

                  <font-awesome-icon :icon="['fas', 'rotate-left']" class="text-2xl" />
                </button>
              </div>
            </div>
          </div>

          <div v-if="canBeDamagedOrDirty" class="flex flex-row items-center gap-2">
            <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="text-2xl" />
            Stato {{ localItem.holder.name }} :
            <div class="flex flex-row items-center gap-2">
              <input type="checkbox" v-model="localItem.is_holder_dirty" :true-value="1" :false-value="0" class="toggle" @change="toggleIsRagnabileInput" />
              Sporco
            </div>
            <div v-if="localItem.is_holder_dirty">
              <select id="total_dirty_holders" v-model="localItem.total_dirty_holders"
                class="select select-bordered">
                <option v-for="i in localItem.holder_quantity" :key="i" :value="i">{{ i }}</option>
              </select>
            </div>
            <div class="flex flex-row items-center gap-2">
              <input type="checkbox" v-model="localItem.is_holder_broken" :true-value="1" :false-value="0" class="toggle" @change="toggleIsRagnabileInput" />
              Rotto
            </div>
            <div v-if="localItem.is_holder_broken">
              <select id="total_broken_holders" v-model="localItem.total_broken_holders"
                class="select select-bordered">
                <option v-for="i in localItem.holder_quantity" :key="i" :value="i">{{ i }}</option>
              </select>
            </div>
            
          </div>
        </div>

        <!-- Save button -->
        <div class="flex flex-row items-center gap-2">

          <div v-if="localItem.is_warehouse_added" class="tooltip" data-tip="Elimina elemento">
            <button class="btn btn-primary btn-circle btn-outline btn-error">
              <font-awesome-icon :icon="['fas', 'trash']" class="text-2xl" />
            </button>
          </div>

          <div v-else class="tooltip" data-tip="Non trovato">
            <button v-if="!localItem.warehouse_downaload_dt" class="btn btn-primary btn-circle btn-outline btn-error" @click="itemNotFound()">
              <font-awesome-icon :icon="['fas', 'eye-slash']" class="text-2xl" />
            </button>
          </div>

          <div class="tooltip" data-tip="Salva">
            <button
              class="btn btn-primary btn-circle btn-outline btn-success"
              :disabled="isSaving || !(props.hasDirtyScalars || props.hasStagedImages || props.hasDirtyExplosions)"
              @click="saveItem"
            >
              <span v-if="isSaving" class="loading loading-spinner loading-md"></span>
              <font-awesome-icon v-else :icon="['fas', 'save']" class="text-2xl" />
            </button>
          </div>

        </div>

      </div>

      <div class="flex flex-row gap-2">
        <!-- SCARICO -->
        <Box class="w-1/3 flex flex-col items-center gap-2">
          <div class="w-full flex justify-center"><span>SCARICO</span></div>
          <div class="w-full flex justify-between items-center gap-2">
            <div class="w-full flex justify-between items-center gap-0">
              <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl text-primary" />
              <span class="badge badge-primary badge-md">previsto</span>
              <span>{{ localItem.warehouse.denominazione }}</span>
            </div>
            <div class="w-full flex justify-between items-center gap-0">
              <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl text-primary" />
              <span class="badge badge-primary badge-md">effettivo</span>
              <span>{{ localItem.warehouse_download.denominazione }}</span>
            </div>
          </div>
          <div>
            <span class="font-medium">Operatore:</span>
            <select v-model="localItem.warehouse_downaload_worker_id" class="select select-bordered">
              <option value="" disabled>Seleziona Operatore</option>
              <option v-for="w in downloadWorkers" :key="w.id" :value="w.id">
                {{ w.name }} {{ w.surname }} - {{ w.role }}
              </option>
            </select>
          </div>
          <div>
            <span class="font-medium">Data operazione:</span>
            <VueDatePicker v-model="localItem.warehouse_downaload_dt" format="dd/MM/yyyy, HH:mm" :teleport="true" />
          </div>
        </Box>

        <!-- PESATURA -->
        <Box class="w-1/3 flex flex-col items-center gap-2">
          <div class="w-full flex justify-center"><span>PESATURA</span></div>
          <div class="flex items-center gap-1">
            Lordo:
            <input   
              v-model.number="localItem.weight_gross"
              type="number" step="1" class="input input-bordered flex"
              placeholder="LORDO" @input="lastEdited = 'gross'" 
            />
            Tara:
            <input 
              v-model.number="localItem.weight_tare"
              type="number" step="0.01" class="input input-bordered flex"
              placeholder="TARA" @input="lastEdited = 'tare'"
            />
            Netto:
            <input 
              v-model.number="localItem.weight_net"
              type="number" step="0.01" class="input input-bordered flex"
              placeholder="NETTO" @input="lastEdited = 'net'" 
            />
          </div>
          <div>
            <span class="font-medium">Operatore:</span>
            <select v-model="localItem.warehouse_weighing_worker_id" class="select select-bordered">
              <option value="" disabled>Seleziona Operatore</option>
              <option v-for="w in weighingWorkers" :key="w.id" :value="w.id">
                {{ w.name }} {{ w.surname }} - {{ w.role }}
              </option>
            </select>
          </div>
          <div>
            <span class="font-medium">Data operazione:</span>
            <VueDatePicker v-model="localItem.warehouse_weighing_dt" format="dd/MM/yyyy, HH:mm" :teleport="true" />
          </div>
        </Box>

        <!-- SELEZIONE -->
        <Box class="w-1/3 flex flex-col items-center gap-2">
          <div class="w-full flex justify-center"><span>SELEZIONE/CLASSIFICA</span></div>
          <div class="flex flex-row items-center gap-2">
            <input type="checkbox" v-model="localItem.has_selection" :true-value="1" :false-value="0"
              class="toggle my-3" /> Selezione
            <div v-if="localItem.has_selection" class="flex items-center gap-2">
              <select v-model="localItem.selection_time_hh" class="select select-bordered">
                <option v-for="h in 8" :key="h" :value="h">{{ h }}</option>
              </select> hh
              <select v-model="localItem.selection_time_mm" class="select select-bordered">
                <option v-for="m in 11" :key="m" :value="m * 5">
                  {{ (m * 5).toString().padStart(2, '0') }}
                </option>
              </select> mm
            </div>
          </div>
          <div>
            <span class="font-medium">Operatore:</span>
            <select v-model="localItem.warehouse_selection_worker_id" class="select select-bordered">
              <option value="" disabled>Seleziona Operatore</option>
              <option v-for="w in selectionWorkers" :key="w.id" :value="w.id">
                {{ w.name }} {{ w.surname }} - {{ w.role }}
              </option>
            </select>
          </div>
          <div>
            <span class="font-medium">Data operazione:</span>
            <VueDatePicker v-model="localItem.warehouse_selection_dt" format="dd/MM/yyyy, HH:mm" :teleport="true" />
          </div>
        </Box>
      </div>

       <!-- ESPLOSO -->
<div class="mt-4">
  <div class="flex items-center justify-between mb-2">
    <div class="font-semibold">Esplosione (materiali/componenti)</div>
    <button class="btn btn-sm" @click="showExplosion = !showExplosion">
      {{ showExplosion ? 'Nascondi' : 'Gestisci esplosione' }}
      <span class="ml-2 badge badge-ghost" title="Nodi root">{{ itemExplosions.length }}</span>
      <!-- material leaves count -->
      <span class="ml-1 badge badge-outline" title="Materiali (foglie)">{{ materialLeafCount }}</span>
    </button>
  </div>

  <!-- 
  Usare ...p (spread): appiattisce le chiavi di p nel nuovo oggetto che stai emettendo verso il parent.
  emit('explosion:remove', { itemId: localItem.id, ...p })
  // => payload = { itemId: 42, id: 123 }
  Questo è quello che si aspetta il parent se i reducer vogliono le chiavi a livello top (payload.id, payload.parentId, ecc).
  
  Usare solo p (senza spread) dentro a un oggetto: annida il payload sotto la chiave p.
  emit('explosion:remove', { itemId: localItem.id, p })
  // => payload = { itemId: 42, p: { id: 123 } }
  -->
  <ExplosionEditor
    v-if="showExplosion"
    :tree="itemExplosions || []"
    :catalog="catalog"
    :recipes="recipes"
    :parentNet="Number(localItem.weight_net) || 0"
    @explosion:add-root="()       => emit('explosion:add-root', { itemId: localItem.id })"
    @explosion:add-child="p       => emit('explosion:add-child', { itemId: localItem.id, ...(p || {}) })"
    @explosion:remove="p          => emit('explosion:remove', { itemId: localItem.id, ...(p || {}) })"
    @explosion:update-node="p     => emit('explosion:update-node', { itemId: localItem.id, ...(p || {}) })"
    @explosion:toggle-collapse="p => emit('explosion:toggle-collapse', { itemId: localItem.id, ...(p || {}) })"
    @explosion:set-recipe="p      => emit('explosion:set-recipe', { itemId: localItem.id, ...(p || {}) })"
    @explosion:apply-recipe="p    => emit('explosion:apply-recipe', { itemId: localItem.id, ...(p || {}) })"
  />
</div>


      <!-- NOTE -->
      <div class="flex flex-col gap-2 w-full">
        <textarea v-model="localItem.warehouse_notes" class="textarea textarea-success w-full"
          placeholder="Note di Magazzino" />
        <textarea v-model="localItem.warehouse_non_conformity" class="textarea textarea-error w-full"
          placeholder="Non conformità" />
      </div>

      <!-- IMMAGINI -->
      <div class="flex gap-2">
        <Box class="h-full">
          <ImageUploader
            :staged="stagedImages"
            :existing="localItem.images || []"
            :max-files="10"
            :max-file-size-mb="5"
            @images:add="files => emit('images:add', { itemId: localItem.id, files })"
            @images:remove="p => emit('images:remove', { itemId: localItem.id, ...(p || {}) })"
            @images:delete-existing="p => emit('images:delete-existing', { itemId: localItem.id, ...(p || {}) })"
          />
        </Box>
      </div>
    </div>
    <!-- END CONTENT -->

  </div>
</template>

<script setup>
import { useStore } from 'vuex'
import Box from '@/Components/UI/Box.vue'
import EmptyState from '@/Components/UI/EmptyState.vue'
import { ref, reactive, computed, watch, nextTick } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import dayjs from 'dayjs'
import ImageUploader   from '@/Pages/Warehouse/Order/Components/ImageUploader.vue'
import ExplosionEditor from '@/Pages/Warehouse/Order/Components/ExplosionEditor.vue'

const props = defineProps({
  item: Object,
  index: Number,
  warehouseChiefs: Array,
  warehouseManagers: Array,
  warehouseWorkers: Array,
  parentHasRagno: Boolean,
  parentMachineryTime: Number,
  saving: Boolean,

  hasDirtyExplosions: { type: Boolean, default: false },
  hasStagedImages:    { type: Boolean, default: false  },
  hasDirtyScalars:    { type: Boolean, default: false },

  stagedImages: { type: Array, default: () => [] }, // immagini selezionate per questo item
  catalog:      { type: Array, required: true },    // [{id,name,type}]
  recipes:      { type: Array, default: () => []},  // [{id,name,version}]
})

const emit = defineEmits([
  // patch “classiche”
  'update-is-ragnabile-toggle',
  'update-manual-machinery-time',
  'reset-manual-machinery-time',
  // update / save single item
  'update',
  'save-one',
  // flag found/not found
  'item-not-found',
  'item-found',
  // immagini
  'images:add',
  'images:remove',
  'images:delete-existing',
  // nuovi per gestione dummy explosion tree
  'explosion:add-root',
  'explosion:add-child',
  'explosion:remove',
  'explosion:update-node',
  'explosion:toggle-collapse',
  'explosion:set-recipe',
  'explosion:apply-recipe',
])

const store = useStore()

const initialTotal = Number(props.item.machinery_time_fraction) || 0
const localItem = reactive({ 
  ...props.item,
  machinery_time_fraction_hh: Math.floor(initialTotal / 60),
  machinery_time_fraction_mm: initialTotal % 60
})

const isSaving = computed(() => !!props.saving)

/**
 * HELPER FUNCTIONS
 */
const EMPTY = Object.freeze([]);

// --- Helpers formato DATE ---
function formatDate(d) {
  return d ? dayjs(d).format('YYYY-MM-DD HH:mm:ss') : null
}

function toDayjs(v) {
  if (v == null) return null
  const d = dayjs(v)        // accetta Date, string, number, Dayjs
  return d.isValid() ? d : null
}

// --- Helpers che inibisce operazioni finchè applico un patch da PARENT ---
const applyingFromParent = ref(false)

function runAsParentPatch(fn) {
  applyingFromParent.value = true
  try { fn?.() } finally {
    nextTick(() => { applyingFromParent.value = false })
  }
}


/**
 * MAIN FUNCTIONS and WATCHERS
 */

/*
 *  PATCH verso parent (campi semplici)
 */
// 0) Campi scalari che il row gestisce (senza images/explosions)
const SCALAR_KEYS = [
  'holder_quantity','cer_code_id',
  'weight_gross','weight_tare','weight_net',
  'is_ragnabile','machinery_time_fraction_hh','machinery_time_fraction_mm',
  'is_holder_dirty','total_dirty_holders',
  'is_holder_broken','total_broken_holders',
  'has_selection','selection_time_hh','selection_time_mm',
  'warehouse_downaload_worker_id','warehouse_downaload_dt',
  'warehouse_weighing_worker_id','warehouse_weighing_dt',
  'warehouse_selection_worker_id','warehouse_selection_dt',
  'warehouse_notes','warehouse_non_conformity',
]

// helper per prendere solo le chiavi whitelisted
function pick(obj, keys) {
  const out = {}
  for (const k of keys) out[k] = obj[k]
  return out
}

// diff shallow: ritorna solo le chiavi con valore diverso
function diffShallow(prev, next) {
  const patch = {}
  for (const k in next) {
    // opzionale: normalizza numeri/stringhe se serve
    if (prev[k] !== next[k]) patch[k] = next[k]
  }
  return patch
}

// snapshot iniziale (dopo aver popolato localItem da props.item)
let prevSnapshot = pick(localItem, SCALAR_KEYS)

// 1) quando il parent cambia item, si modifica porps.item. Ricarica l'item e aggiorna anche lo snapshot
watch(() => props.item, (newItem) => {
  runAsParentPatch(() => {
    // 1) aggiorno tutti i campi
    Object.assign(localItem, newItem)
    // 2) splitto subito la frazione in hh/mm
    const total = Number(newItem.machinery_time_fraction) || 0
    localItem.machinery_time_fraction_hh = Math.floor(total / 60)
    localItem.machinery_time_fraction_mm = total % 60
    prevSnapshot = pick(localItem, SCALAR_KEYS) // 🔴 reset snapshot
  })
}, { immediate: true })

/*
// quando prop.item cambia (parent ha aggiornato), ricarica l'item
watch(
  () => props.item, (newItem) => 
  runAsParentPatch(() => {
    // 1) aggiorno tutti i campi
    Object.assign(localItem, newItem)
    // 2) splitto subito la frazione in hh/mm
    const total = Number(newItem.machinery_time_fraction) || 0
    localItem.machinery_time_fraction_hh = Math.floor(total / 60)
    localItem.machinery_time_fraction_mm = total % 60
    nextTick(() => { applyingFromParent.value = false })
  }),
  //{ deep: true, immediate: true }
  { immediate: true }
)
*/

// 2) Watch SOLO i campi whitelisted, non tutto localItem
watch(
  () => SCALAR_KEYS.map(k => localItem[k]),
  () => {
    if (applyingFromParent.value) return
    const nextSnapshot = pick(localItem, SCALAR_KEYS)
    const patch = diffShallow(prevSnapshot, nextSnapshot)
    if (Object.keys(patch).length) {
      emit('update', { id: localItem.id, ...patch }) // invia a parent solo le differenze
      prevSnapshot = nextSnapshot
    }
  },
  { deep: false } // importantissimo: niente deep
)


// quando viene modificato il selection time, aggiorna i campi in ore e minuti
watch(
  () => props.item.selection_time,
  (totalMinutes) => {
    localItem.selection_time_hh = Math.floor(totalMinutes / 60)
    localItem.selection_time_mm = totalMinutes % 60
  },
  { immediate: true }
)

// quando attivo lo stato "sporco", inizializza il numero a 1 se era vuoto
watch(
  () => localItem.is_holder_dirty,
  isDirty => {
    if (isDirty === 1 && !localItem.total_dirty_holders) {
      localItem.total_dirty_holders = 1
    }
  }
)

// quando attivo lo stato "rotto", inizializza il numero a 1 se era vuoto
watch(
  () => localItem.is_holder_broken,
  isBroken => {
    if (isBroken === 1 && !localItem.total_broken_holders) {
      localItem.total_broken_holders = 1
    }
  }
)

const canBeDamagedOrDirty = computed(() => {
  return [4, 5, 6, 7, 8].includes(localItem.holder_id) // holders that can be damaged or dirty
})


/**
 * IMMAGINI
 */
// TUTTO LO STATO DELLE IMMAGINI VIVE NEL PARENT

/*
* ESPLOSIONE
*/ 
const itemExplosions = computed(() =>
  Array.isArray(props.item?.explosions) ? props.item.explosions : EMPTY
);

// per il TOGGLE
const showExplosion = ref(false)

function nodeType(n) {
  return n?.catalog_item?.type
      || n?.catalogItem?.type
      || n?._selected?.type
      || n?.type
      || null
}

function nodeChildren(n) {
  if (Array.isArray(n?.children)) return n.children
  if (Array.isArray(n?.children_recursive)) return n.children_recursive
  if (Array.isArray(n?.childrenRecursive)) return n.childrenRecursive
  return []
}

function countMaterialLeaves(list) {
  let count = 0
  for (const n of Array.isArray(list) ? list : []) {
    if (nodeType(n) === 'material') count++
    const kids = nodeChildren(n)
    if (kids.length) count += countMaterialLeaves(kids)
  }
  return count
}

const materialLeafCount = computed(() => countMaterialLeaves(itemExplosions.value))


/**
 * LORODO + TARA = NETTO
 */

// --- Helpers peso ---
function toNum(v) {
  // '' o null -> 0 ; numeri/stringhe -> Number
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

const weightSyncing = ref(false)
const lastEdited    = ref(null) // 'gross' | 'tare' | 'net' | null

function n(v) {
  const x = Number(v)
  return Number.isFinite(x) ? x : 0
}

// Sync a tre vie con protezione dal loop
watch(
  () => [localItem.weight_gross, localItem.weight_tare, localItem.weight_net],
  ([gross, tare, net]) => {
    if (weightSyncing.value) return
    weightSyncing.value = true
    try {
      const G = n(gross)
      const T = n(tare)
      const N = n(net)

      // Se non hai ancora editato nulla in questa sessione,
      // comportati come prima: calcola NETTO da LORDO e TARA.
      if (!lastEdited.value) {
        if (gross === '' || gross == null) {
          localItem.weight_net = 0
        } else {
          localItem.weight_net = +(G - T).toFixed(2)
        }
        return
      }

      switch (lastEdited.value) {
        case 'gross':
          // netto = lordo - tara
          localItem.weight_net = +(G - T).toFixed(2)
          break
        case 'tare':
          // netto = lordo - tara
          localItem.weight_net = +(G - T).toFixed(2)
          break
        case 'net':
          // tara = lordo - netto
          // (se preferisci ricalcolare il lordo: lordo = netto + tara)
          localItem.weight_tare = +(G - N).toFixed(2)
          break
      }
    } finally {
      // lascio lastEdited come è, così le prossime modifiche
      // continuano a rispettare l’intento dell’utente
      weightSyncing.value = false
    }
  },
  { immediate: true }
)
// quando cambio item, resetto lastEdited


/**
 * DATE SCARICO, PESATURA, SELEZIONE
 */
let lastToast = ''
function dateInfo(type, text){
  if (text === lastToast) return          // evita duplicati identici ravvicinati
  lastToast = text
  store.dispatch('flash/queueMessage', { type, text })
  setTimeout(() => { lastToast = '' }, 400)
}


// 2.1) Se cambia la DATA DI SCARICO:
// - non ha prerequisiti
// - se PESATURA esiste ed è < SCARICO -> allinea a SCARICO
// - se SELEZIONE esiste ed è < PESATURA -> riallineo dopo che ho sistemato PESATURA
let lockingDL = false
watch(
  () => localItem.warehouse_downaload_dt,
  (newDl) => {
    if (lockingDL) { lockingDL = false; return }
    const dDL = toDayjs(newDl)

    // riallinea PESATURA
    if (localItem.warehouse_weighing_dt) {
      const dWG = toDayjs(localItem.warehouse_weighing_dt)
      if (dDL && dWG.isBefore(dDL)) {
        lockingDL = true
        localItem.warehouse_weighing_dt = dDL.toDate()
        dateInfo('success', 'Data di pesatura riallineata a quella di scarico')
      }
    }

    // riallinea SELEZIONE
    if (localItem.warehouse_selection_dt) {
      const dWG2 = toDayjs(localItem.warehouse_weighing_dt)
      const dSL  = toDayjs(localItem.warehouse_selection_dt)

      if (!dWG2) {
        lockingDL = true
        localItem.warehouse_selection_dt = null
        dateInfo('error', 'Imposta prima una data di pesatura')
      } else if (dSL.isBefore(dWG2)) {
        lockingDL = true
        localItem.warehouse_selection_dt = dWG2.toDate()
        dateInfo('success', 'Data di selezione riallineata a quella di pesatura')
      }
    }
  }
)

// 2.2) Se cambia la DATA DI PESATURA:
// - NON può essere impostata se SCARICO è null -> annulla
// - deve essere ≥ SCARICO
// - SELEZIONE (se esiste) deve essere ≥ PESATURA
let lockingWG = false
watch(
  () => localItem.warehouse_weighing_dt,
  (newWg) => {
    if (lockingWG) { lockingWG = false; return }

    const dDL = toDayjs(localItem.warehouse_downaload_dt)
    if (!dDL) {
      lockingWG = true
      localItem.warehouse_weighing_dt = null
      dateInfo('error', 'Imposta prima una data di scarico')
      return
    }

    const dWG = toDayjs(newWg)
    if (dWG && dWG.isBefore(dDL)) {
      lockingWG = true
      localItem.warehouse_weighing_dt = dDL.toDate()
      dateInfo('success', 'Data di pesatura riallineata a quella di scarico')
      return
    }

    // SELEZIONE ≥ PESATURA
    const dWG2 = toDayjs(localItem.warehouse_weighing_dt)
    const dSL  = toDayjs(localItem.warehouse_selection_dt)
    if (dSL && dWG2 && dSL.isBefore(dWG2)) {
      lockingWG = true
      //localItem.warehouse_selection_dt = dWG2.toDate()   
      //dateInfo('success', 'Data di selezione riallineata a quella di pesatura')
      dateInfo('error', 'Data di pesatura successiva a quella di selezione.')
      localItem.warehouse_weighing_dt = null;
    }
  }
)

// 2.3) Se cambia la DATA DI SELEZIONE:
// - NON può essere impostata se PESATURA è null -> annulla
// - deve essere ≥ PESATURA
let lockingSL = false
watch(
  () => localItem.warehouse_selection_dt,
  (newSl) => {
    if (lockingSL) { lockingSL = false; return }

    const dWG = toDayjs(localItem.warehouse_weighing_dt)
    if (!dWG) {
      lockingSL = true
      localItem.warehouse_selection_dt = null
      dateInfo('error', 'Imposta prima una data di pesatura') // ← testo corretto
      return
    }

    const dSL = toDayjs(newSl)
    if (dSL && dSL.isBefore(dWG)) {
      lockingSL = true
      localItem.warehouse_selection_dt = dWG.toDate()
      dateInfo('success', 'Data di selezione riallineata a quella di pesatura')
    }
  }
)

function saveItem() {
  console.log('local item = ',localItem);
  emit('save-one', { id: localItem.id })
}

function itemNotFound(){
  console.log('local item = ',localItem);
  emit('item-not-found', { id: localItem.id, updated_at: dayjs().toISOString() })
}

function itemFound(){
  const li = localItem?.value ?? localItem
  console.log('local item = ',li);
  emit('item-found', { id: li.id, updated_at: dayjs().toISOString() })
}


const downloadWorkers = computed(() =>  [
    ...(props.warehouseChiefs ||  []),
    ...(props.warehouseManagers ||  []),
    ...(props.warehouseWorkers ||  []),
  ]
)
const weighingWorkers = downloadWorkers
const selectionWorkers = computed(() => [
    ...(props.warehouseWorkers ||  []),
  ])

// when user manually tweaks this row’s fraction:
function onManualMachineryTimeInput() {
  // old: Number(props.item.machinery_time_fraction)
  const totalMinutes = localItem.machinery_time_fraction
  // clamp to parent total minutes * 60
  const maxMinutes = props.parentMachineryTime
  const safe = Math.min(totalMinutes, maxMinutes)
  emit('update-manual-machinery-time', {
    id: localItem.id,
    minutes: safe,
    isManual: true
  })
}

// 3) reset this row back to auto-split
function resetManualMachineryTimeInput() {
  emit('reset-manual-machinery-time', { id: localItem.id })
}

// 4) reset this row back to auto-split
function toggleIsRagnabileInput() {
  emit('update-is-ragnabile-toggle', { id: localItem.id, isRagnabile: localItem.is_ragnabile ? 1 : 0 })
}


</script>
