<script setup>
import { computed, ref, watchEffect } from 'vue'
import draggable from 'vuedraggable'
import { GoogleMap, AdvancedMarker } from 'vue3-google-map'
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
  'update-technical-stop-coords',
  'update-stop-fields',
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

const isTechnical = (s) => s?.kind === 'technical'
const canRemove = (s) => s?.kind === 'technical'

const selectedActionId = ref('')
const addTechnical = () => {
  if (!selectedActionId.value) return
  emit('add-technical-stop', selectedActionId.value)
  selectedActionId.value = ''
}

const mapApiKey = import.meta.env.VITE_MAPS_API_KEY
const defaultLat = Number(import.meta.env.VITE_MAPS_CENTER_DEFAULT_LAT ?? 0)
const defaultLng = Number(import.meta.env.VITE_MAPS_CENTER_DEFAULT_LNG ?? 0)
const zoomLevel = 14

const stopCoords = (s) => {
  if (!s) return { lat: defaultLat, lng: defaultLng }
  if (s.kind === 'technical') {
    const lat = Number(s.location_lat ?? defaultLat)
    const lng = Number(s.location_lng ?? defaultLng)
    return { lat, lng }
  }
  const lat = Number(s.site?.lat ?? defaultLat)
  const lng = Number(s.site?.lng ?? defaultLng)
  return { lat, lng }
}

const pinSvg = ref(null)
watchEffect(() => {
  const pinSvgContent =
    '<svg xmlns="http://www.w3.org/2000/svg" height="42" viewBox="0 0 24 24">' +
    '<path fill="#ef4444" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>' +
    '</svg>'
  pinSvg.value = new DOMParser().parseFromString(pinSvgContent, 'image/svg+xml').documentElement
})

const markerOptions = (s) => {
  const { lat, lng } = stopCoords(s)
  return {
    id: s?.local_id ?? s?.id ?? 'technical',
    position: { lat, lng },
    content: pinSvg.value,
    gmpClickable: true,
    gmpDraggable: true,
  }
}

const onMarkerDragEnd = (evt, s, key) => {
  if (!evt?.latLng || !s || !key) return
  const lat = evt.latLng.lat()
  const lng = evt.latLng.lng()
  s.location_lat = lat
  s.location_lng = lng
  emit('update-technical-stop-coords', { key, lat, lng })
}

const onMapClick = (evt, s, key) => {
  onMarkerDragEnd(evt, s, key)
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
          <Box :key="`stop-${key}`" padding="p-2">
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 w-3/4">
                <div class="font-semibold truncate">
                  <font-awesome-icon :icon="['fas', 'grip-vertical']" class="stop-handle cursor-grab select-none opacity-60 mr-2" />
                  {{ index + 1 }}. {{ stopTitle(resolveStop(key)) }}
                </div>
                <div v-if="stopSubtitle(resolveStop(key))" class="text-xs opacity-70 truncate">
                  {{ stopSubtitle(resolveStop(key)) }}
                </div>
                <div v-if="isTechnical(resolveStop(key))" class="text-xs opacity-70">
                  Sosta tecnica - scegli posizione sulla mappa
                  
                  <div class="mt-2 flex flex-row gap-2">
                    <input
                      type="text"
                      class="input input-bordered input-sm w-1/2"
                      placeholder="Descrizione (es. parcheggio camion)"
                      :value="resolveStop(key)?.description ?? ''"
                      @input="(e) => emit('update-stop-fields', { key, description: e.target.value })"
                    />
                    <input
                      type="text"
                      class="input input-bordered input-sm w-1/2"
                      placeholder="Indirizzo"
                      :value="resolveStop(key)?.address_text ?? ''"
                      @input="(e) => emit('update-stop-fields', { key, address_text: e.target.value })"
                    />
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <div class="badge badge-neutral">
                  {{ resolveStop(key)?.orders_count ?? (resolveStop(key)?.orders?.length ?? 0) }} ordini
                </div>
                <div v-if="isTechnical(resolveStop(key))" class="badge badge-neutral">
                  sosta tecnica
                </div>
                <div v-if="canRemove(resolveStop(key))" class="flex">
                  <button
                    type="button"
                    class="btn btn-ghost btn-xs"
                    @click.stop="emit('remove-technical-stop', key)"
                  >
                    <font-awesome-icon :icon="['fas', 'trash']" class="text-sm"/>
                  </button>
                </div>
              </div>
            </div>

            <div v-if="isTechnical(resolveStop(key))" class="collapse collapse-arrow border border-base-300 bg-base-100 mt-3">
              <input type="checkbox" v-model="resolveStop(key).open" />
              <div class="collapse-title text-sm font-medium">
                Mappa posizione
              </div>
              <div class="collapse-content">
                <div class="w-full min-h-[300px] h-[40vh]">
                  <GoogleMap
                    mapId="DEMO_MAP_ID"
                    :api-key="mapApiKey"
                    style="width: 100%; height: 100%"
                    :center="stopCoords(resolveStop(key))"
                    :zoom="zoomLevel"
                    :fullscreenControl="false"
                    @click="(evt) => onMapClick(evt, resolveStop(key), key)"
                  >
                    <AdvancedMarker
                      :key="resolveStop(key)?.local_id ?? key"
                      :id="resolveStop(key)?.local_id ?? key"
                      :options="markerOptions(resolveStop(key))"
                      @dragend="(evt) => onMarkerDragEnd(evt, resolveStop(key), key)"
                    />
                  </GoogleMap>
                </div>
                <div class="text-xs opacity-70 mt-2">
                  Lat: {{ stopCoords(resolveStop(key)).lat }} | Lng: {{ stopCoords(resolveStop(key)).lng }}
                </div>
              </div>
            </div>
          </Box>
        </template>
      </draggable>
    </div>
  </div>
</template>


