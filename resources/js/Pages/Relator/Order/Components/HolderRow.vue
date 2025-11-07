<template>
  <div
    class="flex flex-row items-start gap-2 mb-2 p-2 rounded-md"
    :class="holder._highlight ? 'ring-2 ring-warning/60' : ''"
  >
    <!-- Selettore holder -->
    <div class="form-control">
      <select v-model="localId" id="holder" class="select select-bordered">
        <option disabled value="">Seleziona un contenitore</option>
        <option v-for="h in holders" :key="h.id" :value="h.id">
          {{ h.name }}
        </option>
      </select>
    </div>

    <!-- Pieni AUTOMATICI (read-only) -->
    <div class="form-control flex flex-row items-center gap-2">
      <label class="label" for="holder_vuote"><span class="label-text">Pieni</span></label>
      <input
        :value="Number(holder.holder_piene ?? 0)"
        id="holder_piene"
        type="number"
        class="input input-bordered w-32 opacity-70"
        disabled
      />
    </div>

    <!-- Vuoti richiesti (±) -->
    <div class="form-control flex flex-row items-center gap-2">
      <label class="label" for="holder_vuote"><span class="label-text">Vuoti richiesti (±)</span></label>
      <input
        v-model.number="holder.holder_vuote"
        id="holder_vuote"
        type="number"
        step="1"
        class="input input-bordered w-32"
        placeholder="+/- quantità"
      />
    </div>

    <!-- Totale (read-only) -->
    <div class="form-control flex flex-row items-center gap-2">
      <label class="label" for="holder_totale"><span class="label-text font-medium">Totale</span></label>
      <input
        :value="Number(holder.holder_totale ?? 0)"
        id="holder_totale"
        type="number"
        class="input input-bordered w-32 font-medium"
        placeholder="Totale"
        disabled
      />
    </div>

    <button @click="$emit('remove')" class="btn btn-error btn-circle self-end">
      <font-awesome-icon :icon="['fas', 'xmark']" />
    </button>
  </div>
</template>

<script setup>
import { watch, ref } from 'vue'

const props = defineProps({ 
  index: { type: Number, required: true },
  holder: { type: Object, required: true }, // { holder_id, holder_piene, holder_vuote, holder_totale, _highlight? }
  holders: { type: Array, default: () => [] },
})

const emit = defineEmits(['remove', 'change-holder-id'])

// v-model locale per intercettare il cambio id e delegarlo al parent
const localId = ref(props.holder.holder_id)

// se cambiano da UI: chiedi al parent se va bene; il parent può revertire
watch(localId, (newId, oldId) => {
  emit('change-holder-id', { index: props.index, newId, oldId })
})

// se il parent modifica holder_id (es. dopo revert), sync il locale
watch(() => props.holder.holder_id, (id) => {
  if (id !== localId.value) localId.value = id
})

// ricalcola totale quando cambiano auto o vuoti
watch(
  () => [props.holder.holder_piene, props.holder.holder_vuote],
  () => {
    const auto  = Number(props.holder.holder_piene ?? 0)
    const vuote = Number(props.holder.holder_vuote ?? 0)
    props.holder.holder_totale = auto + vuote
  },
  { immediate: true }
)
</script>
