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
      <label class="label" for="empty_holders_count"><span class="label-text">Pieni</span></label>
      <input
        :value="Number(holder.filled_holders_count ?? 0)"
        id="filled_holders_count"
        type="number"
        class="input input-bordered w-32 opacity-70"
        disabled
      />
    </div>

    <!-- Vuoti richiesti (Â±) -->
    <div class="form-control flex flex-row items-center gap-2">
      <label class="label" for="empty_holders_count"><span class="label-text">Vuoti richiesti (Â±)</span></label>
      <input
        v-model.number="holder.empty_holders_count"
        id="empty_holders_count"
        type="number"
        step="1"
        class="input input-bordered w-32"
        placeholder="+/- quantitÃ "
      />
    </div>

    <!-- Totale (read-only) -->
    <div class="form-control flex flex-row items-center gap-2">
      <label class="label" for="total_holders_count"><span class="label-text font-medium">Totale</span></label>
      <input
        :value="Number(holder.total_holders_count ?? 0)"
        id="total_holders_count"
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
  holder: { type: Object, required: true }, // { holder_id, filled_holders_count, empty_holders_count, total_holders_count, _highlight? }
  holders: { type: Array, default: () => [] },
})

const emit = defineEmits(['remove', 'change-holder-id'])

// v-model locale per intercettare il cambio id e delegarlo al parent
const localId = ref(props.holder.holder_id)

// se cambiano da UI: chiedi al parent se va bene; il parent puÃ² revertire
watch(localId, (newId, oldId) => {
  emit('change-holder-id', { index: props.index, newId, oldId })
})

// se il parent modifica holder_id (es. dopo revert), sync il locale
watch(() => props.holder.holder_id, (id) => {
  if (id !== localId.value) localId.value = id
})

// ricalcola totale quando cambiano auto o vuoti
watch(
  () => [props.holder.filled_holders_count, props.holder.empty_holders_count],
  () => {
    const auto  = Number(props.holder.filled_holders_count ?? 0)
    const vuote = Number(props.holder.empty_holders_count ?? 0)
    props.holder.total_holders_count = auto + vuote
  },
  { immediate: true }
)
</script>
