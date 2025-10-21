// ImageUploader.vue
<template>
  <div class="space-y-4 relative">
    <!-- Overlay drag -->
    <div
      v-if="isDragging"
      class="fixed inset-0 bg-gray-800 bg-opacity-75 z-50 flex items-center justify-center pointer-events-none"
    >
      <span class="text-white text-3xl font-semibold">Rilascia le foto</span>
    </div>

    <!-- (Opzionale) immagini già esistenti sul server -->
    <div v-if="existing && existing.length" class="rounded-xl border p-4">
      <div class="mb-2 font-medium">Immagini caricate</div>
      <section class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <div
          v-for="img in existing"
          :key="img.id"
          class="relative group"
        >
          <img :src="img.url" class="rounded-xl w-full h-32 object-cover border" />
          <button
            class="btn btn-xs btn-circle btn-error absolute top-1 right-1 opacity-80 group-hover:opacity-100"
            :disabled="disabled"
            @click.stop="requestDeleteExisting(img)"
            title="Elimina immagine esistente"
          >
            <font-awesome-icon :icon="['fas','trash']" class="" />
          </button>
                    <button
            class="btn btn-xs btn-circle btn-primary absolute top-1 left-1 opacity-80 group-hover:opacity-100"
            :disabled="disabled"
            @click.stop="viewImageInPopUp(img)"
            title="Visuazlizza immagine"
          >
            <font-awesome-icon :icon="['fas','eye']" class="" />
          </button>
        </div>
      </section>
    </div>
    <EmptyState v-else class="w-3/4 h-full">
      Non sono presenti immagini
    </EmptyState>

    <!-- Dropzone (aggiunge nuove staged) -->
    <div
      class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-primary transition duration-200"
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @click="triggerFileInput"
      :class="disabled ? 'pointer-events-none opacity-60' : ''"
    >
      <p class="text-gray-600">
        Trascina qui le foto oppure clicca per selezionarle/scattarle
      </p>
      <p class="text-xs text-gray-400 mt-1">
        Supporta anche scatto diretto da fotocamera mobile
      </p>
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        capture="environment"
        class="hidden"
        multiple
        :disabled="disabled"
        @change="onFileChange"
      />
      <div v-if="hardLimitReached" class="mt-2 text-xs text-error">
        Hai raggiunto il limite massimo di {{ maxFiles }} immagini (staged + esistenti).
      </div>
    </div>

    <!-- Anteprime delle STAGED (nuove) -->
    <div v-if="stagedPreviews.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
      <div
        v-for="(p, index) in stagedPreviews"
        :key="p.key"
        class="relative group"
      >
        <img :src="p.url" class="rounded-xl w-full h-32 object-cover border" />
        <button
          class="btn btn-xs btn-circle btn-error absolute top-1 right-1 opacity-80 group-hover:opacity-100"
          :disabled="disabled"
          @click.stop="removeStaged(index)"
          title="Rimuovi (non verrà caricata)"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Messaggi di errore validazione aggiunta -->
    <div v-if="error" class="text-error text-sm mt-2">{{ error }}</div>
  </div>
</template>

<script setup>
/**
 * ImageUploader “dummy-child”
 * - Non conserva copia applicativa di staged/existing: sono SSOT del parent.
 * - Espone solo UI + validazione + anteprime ObjectURL per staged (cleanup sicuro).
 * - Comunica con emits ‘images:*’.
 */

import EmptyState from '@/Components/UI/EmptyState.vue'
import { ref, watch, computed, onBeforeUnmount } from 'vue'

/* =========================
 * Props (contratto nuovo)
 * ========================= */
const props = defineProps({
  /** Nuove immagini non ancora salvate (controllate dal parent) */
  staged:   { type: Array,  default: () => [] },         // File[]
  /** Immagini già persistite (opzionale) */
  existing: { type: Array,  default: () => [] },         // [{id,url}]
  /** Limiti/validazioni (override facoltativi) */
  maxFiles:      { type: Number, default: 10 },          // totale: existing + staged
  maxFileSizeMb: { type: Number, default: 5  },
  acceptedTypes: {
    type: Array,
    default: () => ['image/jpeg','image/png','image/webp','image/heic','image/heif']
  },
  /** Disabilita interazioni */
  disabled: { type: Boolean, default: false },
})

/* =========================
 * Emits (contratto nuovo)
 * ========================= */
const emit = defineEmits([
  'images:add',             // (files: File[]) -> parent aggiunge a staged
  'images:remove',          // ({ index, file }) -> parent rimuove da staged
  'images:delete-existing', // ({ image }) -> parent elimina dal server e aggiorna existing
])

/* =========================
 * Stato SOLO UI
 * ========================= */
const fileInput   = ref(null)
const isDragging  = ref(false)
const error       = ref(null)

/**
 * Mappa File -> ObjectURL per anteprime.
 * Nessuna lista “files” locale: render basato su props.staged.
 */
const urlMap = new Map() // Map<File, string>

/** Previews calcolate da props.staged + urlMap */
const stagedPreviews = ref([])

/** Totale conteggiato su staged + existing (per limitare aggiunte) */
const totalCount = computed(() => (props.staged?.length || 0) + (props.existing?.length || 0))
const hardLimitReached = computed(() => totalCount.value >= props.maxFiles)

/* =========================
 * Watcher: staged -> (ri)crea preview
 * ========================= */
watch(
  () => props.staged,
  (next) => {
    const list = Array.isArray(next) ? next : []

    // Revoke URL che non servono più
    for (const [file, url] of urlMap.entries()) {
      if (!list.includes(file)) {
        URL.revokeObjectURL(url)
        urlMap.delete(file)
      }
    }

    // Assicura URL per ogni File in staged
    list.forEach(file => {
      if (!(file instanceof File)) return
      if (!urlMap.has(file)) urlMap.set(file, URL.createObjectURL(file))
    })

    // Aggiorna previews
    stagedPreviews.value = list.map((file, i) => ({
      key: `${i}-${file.name || 'f'}-${file.size}-${file.lastModified || '0'}`,
      file,
      url: urlMap.get(file),
    }))

    // reset error se lista vuota
    if (!list.length) error.value = null
  },
  { immediate: true }
)

/* =========================
 * Handlers UI
 * ========================= */
function triggerFileInput() {
  if (props.disabled) return
  fileInput.value?.click()
}

function onDragOver(e) {
  if (props.disabled) return
  isDragging.value = true
  e.dataTransfer.dropEffect = 'copy'
}
function onDragLeave() {
  isDragging.value = false
}
function onDrop(e) {
  if (props.disabled) return
  isDragging.value = false
  addFiles([...e.dataTransfer.files])
}

function onFileChange(e) {
  if (props.disabled) return
  addFiles([...e.target.files])
  // reset per poter ri-selezionare gli stessi file
  e.target.value = ''
}

/**
 * Validazione + emit ‘images:add’.
 * Il parent decide come aggiornare staged (dedup/limiti business ulteriori).
 */
function addFiles(selected) {
  error.value = null
  const accepted = []

  // limite totale = existing + staged + nuovi
  let remaining = props.maxFiles - totalCount.value
  if (remaining <= 0) {
    error.value = `Hai raggiunto il limite massimo di ${props.maxFiles} immagini`
    return
  }

  for (const file of selected) {
    if (remaining <= 0) break

    if (!(file instanceof File)) continue

    if (!props.acceptedTypes.includes(file.type)) {
      error.value = `Tipo file non supportato: ${file.name}`
      continue
    }
    if (file.size > props.maxFileSizeMb * 1024 * 1024) {
      error.value = `Il file ${file.name} supera i ${props.maxFileSizeMb} MB`
      continue
    }

    accepted.push(file)
    remaining--
  }

  if (accepted.length) emit('images:add', accepted)
}

/** Richiede rimozione dallo staged (indice corrente + file) */
function removeStaged(index) {
  const p = stagedPreviews.value[index]
  if (!p) return
  emit('images:remove', { index, file: p.file })
}

/** Richiede eliminazione di una “existing” persistita (no API qui!) */
function requestDeleteExisting(image) {
  if (!image) return
  emit('images:delete-existing', { image })
}

/** Richiede l'apertura del pop-upo di zoom */
function viewImageInPopUp(image) {
  if (!image) return
  window.open(image.url, '_blank', 'noopener,noreferrer')
}

/* =========================
 * Cleanup ObjectURL
 * ========================= */
onBeforeUnmount(() => {
  for (const url of urlMap.values()) URL.revokeObjectURL(url)
  urlMap.clear()
})
</script>
