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
            <button class="btn btn-primary btn-circle btn-outline btn-success" :disabled="!canModify || saving" @click="saveItem" >
              <span v-if="saving" class="loading loading-spinner loading-md"></span>
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
            <input v-model="localItem.weight_gross" type="text" class="input input-bordered flex" placeholder="LORDO" />
            Tara:
            <input v-model="localItem.weight_tare" type="text" class="input input-bordered flex" placeholder="TARA" />
            Netto:
            <input v-model="localItem.weight_net" type="text" class="input input-bordered flex" placeholder="NETTO" />
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

      <!-- NOTE -->
      <div class="flex flex-col gap-2">
        <textarea v-model="localItem.warehouse_notes" class="textarea textarea-success w-full"
          placeholder="Note di Magazzino" />
        <textarea v-model="localItem.warehouse_non_conformity" class="textarea textarea-error w-full"
          placeholder="Non conformità" />
      </div>

      <!-- IMMAGINI -->
      <div class="flex items-stretch gap-2 w-full">
        <Box v-if="existingImages.length" class="w-3/4 h-full">
          <template #header>Immagini caricate</template>
          <section class="mt-4 grid grid-cols-3 gap-4">
            <div v-for="img in existingImages" :key="img.id" class="flex flex-col justify-between">
              <img :src="img.url" class="rounded-md" />
              <Link :href="route('relator.item.image.destroy', { item: localItem.id, image: img.id })" method="delete"
                as="button" class="btn btn-outline btn-error mt-2">
              <font-awesome-icon :icon="['fas', 'trash']" class="text-2xl" /> Elimina
              </Link>
            </div>
          </section>
        </Box>
        <EmptyState v-else class="w-3/4 h-full">
          Non sono presenti immagini
        </EmptyState>
        <Box class="w-1/4 h-full">
          <ImageUploader :images="previewFiles" @update:images="handleImageFiles" />
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
import ImageUploader from '@/Pages/Worker/Order/Components/ImageUploader.vue'
import { ref, reactive, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import dayjs from 'dayjs'

const props = defineProps({
  item: Object,
  index: Number,
  warehouseChiefs: Array,
  warehouseManagers: Array,
  warehouseWorkers: Array,
  parentHasRagno: Boolean,
  parentMachineryTime: Number,
  resetKey: Number,
  saving: Boolean,
})

const emit = defineEmits([
  'update-is-ragnabile-toggle',
  'update-manual-machinery-time',
  'reset-manual-machinery-time',
  'update',
  'save-one'
])

const store     = useStore()

const initialTotal = Number(props.item.machinery_time_fraction) || 0
const localItem = reactive({ 
  ...props.item,
  machinery_time_fraction_hh: Math.floor(initialTotal / 60),
  machinery_time_fraction_mm: initialTotal % 60
})
const previewFiles = ref([])
const canModify = ref(false)

const existingImages = computed(() =>
  (localItem.images ?? []).filter(img => img.id)
)


/**
 * LORODO + TARA = NETTO
 */

// --- Helpers peso ---
function toNum(v) {
  // '' o null -> 0 ; numeri/stringhe -> Number
  const n = Number(v)
  return Number.isFinite(n) ? n : 0
}

// Lordo + Tara = Netto
watch(
  () => [localItem.weight_gross, localItem.weight_tare],
  ([gross, tare]) => {
    const G = toNum(gross)
    const T = toNum(tare)
    // se LORDO è vuoto, netto = 0
    if (gross === '' || gross == null) {
      localItem.weight_net = 0
      return
    }
    // se TARA non è valorizzata -> conta come 0
    localItem.weight_net = G + T
  },
  { immediate: true }
)


/**
 * DATE SCARICO, PESATURA, SELEZIONE
 */
// --- Helpers date ---
function toDayjs(d) {
  return d ? dayjs(d) : null
}

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



// quando prop.item cambia (parent ha aggiornato), ricarica
watch(
  () => props.item,
  newItem => {
    // 1) aggiorno tutti i campi
    Object.assign(localItem, newItem)

    // 2) splitto subito la frazione in hh/mm
    const total = Number(newItem.machinery_time_fraction) || 0
    localItem.machinery_time_fraction_hh = Math.floor(total / 60)
    localItem.machinery_time_fraction_mm = total % 60
  },
  { deep: true, immediate: true }
)

watch(
  () => props.item.selection_time,
  (totalMinutes) => {
    localItem.selection_time_hh = Math.floor(totalMinutes / 60)
    localItem.selection_time_mm = totalMinutes % 60
  },
  { immediate: true }
)

// quando resetKey cambia, resetta le preview
watch(() => props.resetKey, () => {
  previewFiles.value = []
  const total = Number(props.item.machinery_time_fraction) || 0
  localItem.machinery_time_fraction_hh = Math.floor(total / 60)
  localItem.machinery_time_fraction_mm = total % 60
  canModify.value = false
})


// quando attivo lo stato "sporco", inizializza il numero a 1 se era vuoto
watch(
  () => localItem.is_holder_dirty,
  isDirty => {
    if (isDirty === 1 && !localItem.total_dirty_holders) {
      localItem.total_dirty_holders = 1
    }
  }
)

// idem per "rotto"
watch(
  () => localItem.is_holder_broken,
  isBroken => {
    if (isBroken === 1 && !localItem.total_broken_holders) {
      localItem.total_broken_holders = 1
    }
  }
)

const canBeDamagedOrDirty = computed(() => {
  return localItem.holder_id in [4, 5, 6, 7, 8] // holders that can be damaged or dirty
})

function handleImageFiles(files) {
  previewFiles.value = files
  canModify.value = true
  emitUpdate()
}

function saveItem() {
  emit('save-one', serializeLocalItem())
}

function itemNotFound(){
  console.log('local item = ',localItem);
  emit('itemNotFound', serializeLocalItem())
}

function itemFound(){
  console.log('local item = ',localItem);
  emit('itemFound', serializeLocalItem())
}

function emitUpdate() {
  emit('update', serializeLocalItem())
}

function formatDate(dt) {
  return dt ? dayjs(dt).format('YYYY-MM-DD HH:mm:ss') : null
}

function serializeLocalItem() {
  return {
    id: localItem.id,
    holder_quantity: localItem.holder_quantity,
    cer_code_id: localItem.cer_code_id,
    weight_gross: localItem.weight_gross,
    weight_tare: localItem.weight_tare,
    weight_net: localItem.weight_net,
    is_ragnabile: localItem.is_ragnabile ? 1 : 0,
    machinery_time_fraction_hh: localItem.machinery_time_fraction_hh,
    machinery_time_fraction_mm: localItem.machinery_time_fraction_mm,
    machinery_time_fraction: localItem.is_ragnabile
      ? ((Number(localItem.machinery_time_fraction_hh) || 0) * 60) + (Number(localItem.machinery_time_fraction_mm) || 0)
      : 0,
    is_holder_dirty: localItem.is_holder_dirty ? 1 : 0,
    total_dirty_holders: localItem.total_dirty_holders,
    is_holder_broken: localItem.is_holder_broken ? 1 : 0,
    total_broken_holders: localItem.total_broken_holders,
    has_selection: localItem.has_selection ? 1 : 0,
    selection_time_hh: localItem.selection_time_hh,
    selection_time_mm: localItem.selection_time_mm,
    selection_time: localItem.has_selection
      ? ((Number(localItem.selection_time_hh) || 0) * 60) + (Number(localItem.selection_time_mm) || 0)
      : 0,
    warehouse_downaload_worker_id: localItem.warehouse_downaload_worker_id,
    warehouse_downaload_dt: formatDate(localItem.warehouse_downaload_dt),
    warehouse_weighing_worker_id: localItem.warehouse_weighing_worker_id,
    warehouse_weighing_dt: formatDate(localItem.warehouse_weighing_dt),
    warehouse_selection_worker_id: localItem.warehouse_selection_worker_id,
    warehouse_selection_dt: formatDate(localItem.warehouse_selection_dt),
    warehouse_notes: localItem.warehouse_notes,
    warehouse_non_conformity: localItem.warehouse_non_conformity,
    images: previewFiles.value,
  }
}

watch(localItem, () => {
  canModify.value = true
  emitUpdate()
}, { deep: true })

const downloadWorkers = computed(() =>
  [...props.warehouseChiefs, ...props.warehouseManagers, ...props.warehouseWorkers]
)
const weighingWorkers = downloadWorkers
const selectionWorkers = computed(() => [...props.warehouseWorkers])

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
