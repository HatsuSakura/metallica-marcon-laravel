<script setup>
import { computed } from 'vue'
import draggable from 'vuedraggable'
import Box from '@/Components/UI/Box.vue'

const props = defineProps({
  // array di chiavi (string) ordinabili via drag
  stopOrder: { type: Array, default: () => [] },

  // Map(key -> stop). Deve essere una Map vera (computed Map ok)
  stopsByKey: { type: Object, required: true },

  // opzionale: se vuoi bloccare watch nel parent
  isDragging: { type: Boolean, default: false },
})

const emit = defineEmits(['update:stopOrder', 'dragging', 'open-manager'])

const localOrder = computed({
  get: () => props.stopOrder,
  set: (v) => emit('update:stopOrder', v),
})

const resolveStop = (key) => props.stopsByKey?.get?.(key) ?? null

const stopTitle = (s) => {
  if (!s) return '-'
  if (s.kind === 'technical') return String(s.action_label ?? s.action_name ?? 'Sosta tecnica').trim()
  const name = s.customer?.ragione_sociale
  return name ? String(name).trim() : `Cliente #${s.customer_id}`
}

const stopSubtitle = (s) => {
  const addr = s?.site?.indirizzo
  return addr ? String(addr).trim() : null
}

const hasCoords = (s) => {
  const lat = s?.site?.lat
  const lng = s?.site?.lng
  return typeof lat === 'number' && typeof lng === 'number'
}
</script>

<template>
  <div class="mt-4">
    <div class="flex items-center justify-between gap-2">
      <h3 class="font-semibold flex flex-row justify-start items-center gap-2">
        Tappe Viaggio
        <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-2xl" />
      </h3>
      <button
        type="button"
        class="btn btn-sm btn-outline"
        @click="emit('open-manager')"
      >
        <font-awesome-icon :icon="['fas', 'up-right-and-down-left-from-center']" class="text-sm"/>
        Gestisci
      </button>
    </div>

    <div class="mt-2">
      <Box v-if="localOrder.length === 0" padding="p-2">
        <div class="text-sm opacity-70">
          Nessuna tappa: trascina almeno un ordine su motrice/rimorchio/riempimento.
        </div>
      </Box>

      <draggable
        v-else
        v-model="localOrder"
        :item-key="k => k"
        handle=".stop-handle"
        :animation="150"
        tag="div"
        class="space-y-2"
        @start="emit('dragging', true)"
        @end="emit('dragging', false)"
      >
        <template #item="{ element: key, index }">
          <Box :key="key" padding="p-2">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0">
                <div class="font-semibold truncate">
                  <span class="stop-handle cursor-grab select-none opacity-60 mr-2">⋮⋮</span>
                  {{ index + 1 }}. {{ stopTitle(resolveStop(key)) }}
                </div>

                <div v-if="stopSubtitle(resolveStop(key))" class="mt-0.5 min-w-0">
                  <div
                    class="tooltip tooltip-top tooltip-primary w-full"
                    :data-tip="stopSubtitle(resolveStop(key))"
                  >
                    <div class="text-xs opacity-70 truncate w-full max-w-full">
                      {{ stopSubtitle(resolveStop(key)) }}
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <div class="badge badge-neutral">
                  {{ resolveStop(key)?.orders_count ?? (resolveStop(key)?.orders?.length ?? 0) }} ordini
                </div>
                <div v-if="hasCoords(resolveStop(key))" class="badge badge-outline">
                  GPS
                </div>
              </div>
            </div>
          </Box>
        </template>
      </draggable>
    </div>
  </div>
</template>
