// ImageUploader.vue
<template>
  <div class="space-y-4 relative">
    <!-- Overlay visivo durante drag attivo -->
    <div
      v-if="isDragging"
      class="fixed inset-0 bg-gray-800 bg-opacity-75 z-50 flex items-center justify-center pointer-events-none"
    >
      <span class="text-white text-3xl font-semibold">Rilascia le foto</span>
    </div>

    <!-- Dropzone -->
    <div
      class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-primary transition duration-200"
      @dragover.prevent="onDragOver"
      @dragleave.prevent="onDragLeave"
      @drop.prevent="onDrop"
      @click="triggerFileInput"
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
        @change="onFileChange"
      />
    </div>

    <!-- Previews -->
    <div v-if="previews.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
      <div
        v-for="(img, index) in previews"
        :key="index"
        class="relative group"
      >
        <img
          :src="img.url"
          class="rounded-xl w-full h-32 object-cover border"
        />
        <button
          class="btn btn-xs btn-circle btn-error absolute top-1 right-1 opacity-80 group-hover:opacity-100"
          @click.stop="removeImage(index)"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Messaggi di errore -->
    <div v-if="error" class="text-error text-sm mt-2">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

/**
 * props.images è quello che il parent passa come `previewFiles`.
 * Quando diventa [] (dopo saveAll), puliamo l'interno.
 */
const props = defineProps({
  images: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:images'])

const fileInput = ref(null)
const files     = ref([])
const previews  = ref([])
const error     = ref(null)
const isDragging = ref(false)

// Configurazioni
const MAX_FILE_SIZE_MB = 5
const MAX_FILES = 10
const ACCEPTED_TYPES = ['image/jpeg', 'image/png', 'image/webp']

// Se il parent azzera props.images, pulisci internal state
watch(() => props.images, (newVal) => {
  if (Array.isArray(newVal) && newVal.length === 0) {
    // revoke object URLs
    previews.value.forEach(p => URL.revokeObjectURL(p.url))
    files.value = []
    previews.value = []
    error.value = null
  }
})

function triggerFileInput() {
  fileInput.value?.click()
}

function onFileChange(e) {
  handleFiles([...e.target.files])
}

function onDragOver(e) {
  isDragging.value = true
  e.dataTransfer.dropEffect = 'copy'
}

function onDragLeave() {
  isDragging.value = false
}

function onDrop(e) {
  isDragging.value = false
  handleFiles([...e.dataTransfer.files])
}

function handleFiles(selectedFiles) {
  error.value = null

  for (const file of selectedFiles) {
    if (files.value.length >= MAX_FILES) {
      error.value = `Puoi caricare al massimo ${MAX_FILES} immagini`
      break
    }

    if (!ACCEPTED_TYPES.includes(file.type)) {
      error.value = `Tipo file non supportato: ${file.name}`
      continue
    }

    if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
      error.value = `Il file ${file.name} supera i ${MAX_FILE_SIZE_MB} MB`
      continue
    }

    const url = URL.createObjectURL(file)
    previews.value.push({ file, url })
    files.value.push(file)
  }

  emit('update:images', files.value)
}

function removeImage(index) {
  URL.revokeObjectURL(previews.value[index].url)
  previews.value.splice(index, 1)
  files.value.splice(index, 1)
  emit('update:images', files.value)
}
</script>
