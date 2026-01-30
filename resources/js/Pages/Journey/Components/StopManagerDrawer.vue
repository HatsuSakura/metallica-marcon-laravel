<script setup>
import { computed, ref } from 'vue'
import draggable from 'vuedraggable'
import Box from '@/Components/UI/Box.vue'

const props = defineProps({
  stopOrder: { type: Array, default: () => [] },
  stopsByKey: { type: Object, required: true },
  technicalActions: { type: Array, default: () => [] },
})

const emit = defineEmits([
  'update:stopOrder',
  'add-technical-stop',
  'remove-technical-stop',
  'dragging',
  'close',
])

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

const canRemove = (s) => s?.kind === 'technical'

const selectedActionId = ref('')

const addTechnical = () => {
  if (!selectedActionId.value) return
  emit('add-technical-stop', selectedActionId.value)
  selectedActionId.value = ''
}
</script>

<template>
  <div id="stop-manager-drawer" class="absolute inset-0 z-20 p-3 bg-base-100 backdrop-blur-sm scroll-mt-16 h-full overflow-y-auto">
    <div class="p-4 mt-4 flex items-center justify-between">
      <h3 class="font-semibold flex items-center gap-2">
        Gestione Tappe
        <font-awesome-icon :icon="['fas', 'route']" class="text-xl"/>
      </h3>
      <button type="button" class="btn btn-sm btn-outline" @click="emit('close')">
        <font-awesome-icon :icon="['fas', 'xmark']" class="text-sm"/>
        Riduci
      </button>
    </div>

    <div class="p-4">
      <div class="flex items-center gap-2 mb-4">
        <select
          v-model="selectedActionId"
          class="select select-bordered w-full"
        >
          <option value="">Aggiungi sosta tecnica...</option>
          <option
            v-for="action in technicalActions"
            :key="action.id"
            :value="action.id"
          >
            {{ action.label }}
          </option>
        </select>
        <button type="button" class="btn btn-primary btn-sm" @click="addTechnical">
          Aggiungi
        </button>
      </div>

      <Box v-if="localOrder.length === 0" padding="p-2">
        <div class="text-sm opacity-70">
          Nessuna tappa. Trascina ordini o aggiungi una sosta tecnica.
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
                <div v-if="stopSubtitle(resolveStop(key))" class="text-xs opacity-70 truncate">
                  {{ stopSubtitle(resolveStop(key)) }}
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <div class="badge badge-neutral">
                  {{ resolveStop(key)?.orders_count ?? (resolveStop(key)?.orders?.length ?? 0) }} ordini
                </div>
                <div v-if="canRemove(resolveStop(key))" class="flex">
                  <button
                    type="button"
                    class="btn btn-ghost btn-xs"
                    @click="emit('remove-technical-stop', key)"
                  >
                    <font-awesome-icon :icon="['fas', 'trash']" class="text-sm"/>
                  </button>
                </div>
              </div>
            </div>
          </Box>
        </template>
      </draggable>
    </div>
  </div>
</template>
