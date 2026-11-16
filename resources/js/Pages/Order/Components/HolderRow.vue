<template>
    <div class="mb-3 grid min-w-0 grid-cols-1 items-end gap-3 rounded-xl bg-base-200 p-3 sm:grid-cols-2 xl:grid-cols-5" :class="holder._highlight ? 'ring-2 ring-warning/60' : ''">
        <label class="form-control min-w-0"><span class="label">Contenitore</span><select v-model="localId" :id="`order-holder-${index}`" :name="`holders.${index}.holder_id`" class="select select-bordered"><option disabled :value="null">Seleziona contenitore</option><option v-for="h in holders" :key="h.id" :value="h.id">{{ h.name }}</option></select></label>
        <label class="form-control min-w-0"><span class="label">Pieni da ritirare</span><input :value="Number(holder.filled_holders_count ?? 0)" type="number" class="input input-bordered" disabled /></label>
        <label class="form-control min-w-0"><span class="label">Vuoti richiesti (±)</span><input v-model.number="holder.empty_holders_count" :id="`order-holder-${index}-empty`" :name="`holders.${index}.empty_holders_count`" type="number" step="1" class="input input-bordered" /></label>
        <label class="form-control min-w-0"><span class="label">Totale da consegnare</span><input :value="Number(holder.total_holders_count ?? 0)" type="number" class="input input-bordered" disabled /></label>
        <AppAction variant="remove" shape="circle" :aria-label="`Rimuovi contenitore ${holders.find(h=>h.id === holder.holder_id)?.name || index+1}`" @click="$emit('remove')"><font-awesome-icon :icon="['fas','trash-can']" aria-hidden="true" /></AppAction>
    </div>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
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
