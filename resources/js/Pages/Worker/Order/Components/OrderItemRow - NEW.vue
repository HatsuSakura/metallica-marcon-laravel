<script setup>
import { ref } from 'vue'
import ImageUploader from '@/Pages/Worker/Order/Components/ImageUploader.vue'
import ExplosionEditor from './ExplosionEditor.vue'

const props = defineProps({
  item: { type: Object, required: true },      // oggetto “vero” dal parent
  catalog: { type: Array, required: true },
  recipes: { type: Array, default: () => [] },
  stagedImages: { type: Array, default: () => [] }, // solo File[], per preview
})

const emit = defineEmits(['patch', 'stage-images', 'save'])

// helper rapido per patch
function patch(fields) {
  emit('patch', { id: props.item.id, patch: fields })
}

/* ------- campi semplici ------- */
function onGrossInput(v) { patch({ weight_gross: v }) }
function onTareInput(v)  { patch({ weight_tare: v }) }
function onNetInput(v)   { patch({ weight_net: v }) }

/* ------- immagini (solo File[]) ------- */
function onImages(files) {
  emit('stage-images', { id: props.item.id, files })
}

/* ------- esplosioni ------- */
function onExplosionUpdate(nodes) {
  patch({
    explosions: nodes,
    has_exploded_children: Array.isArray(nodes) && nodes.length > 0
  })
}

function saveOne() {
  emit('save', props.item.id)
}
</script>

<template>
  <div class="card border mb-3">
    <div class="card-body">
      <div class="flex justify-between items-center">
        <div class="font-semibold">
          Item #{{ item.id }} — {{ item.holder?.name }}
        </div>
        <button class="btn btn-sm btn-success" @click="saveOne">Salva</button>
      </div>

      <!-- PESI -->
      <div class="flex gap-2 items-center mt-2">
        <label>Lordo</label>
        <input class="input input-bordered input-sm w-24" :value="item.weight_gross" @input="onGrossInput($event.target.value)" />
        <label>Tara</label>
        <input class="input input-bordered input-sm w-24" :value="item.weight_tare" @input="onTareInput($event.target.value)" />
        <label>Netto</label>
        <input class="input input-bordered input-sm w-24" :value="item.weight_net" @input="onNetInput($event.target.value)" />
      </div>

      <!-- ESPLOSIONE -->
      <div class="mt-4">
        <div class="mb-1 font-medium">Esplosione (componenti/materiali)</div>
        <ExplosionEditor
          :explosions="item.explosions"
          :catalog="catalog"
          :recipes="recipes"
          :parentNet="Number(item.weight_net) || 0"
          @update="onExplosionUpdate"
        />
      </div>

      <!-- IMMAGINI -->
      <div class="mt-4">
        <ImageUploader :images="stagedImages" @update:images="onImages" />
      </div>
    </div>
  </div>
</template>
