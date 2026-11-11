<template>
            <aside class="min-w-0 space-y-3" aria-labelledby="journey-stops-heading">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="journey-stops-heading" class="text-lg font-semibold">Tappe</h2>
                    <div v-if="!readOnly || canStart" class="flex items-center gap-2">
                        <button v-if="!readOnly" type="button" class="btn btn-outline min-h-12 px-3" :disabled="isLoading" :aria-expanded="addStopOpen" aria-controls="journey-add-stop-form" @click="addStopOpen = !addStopOpen">{{ addStopOpen ? 'Chiudi' : '+ Aggiungi' }}</button>
                        <button v-if="!reorderEnabled && reorderableStops.length > 1" type="button" class="btn btn-outline min-h-12 px-3" :disabled="isLoading" @click="$emit('toggle-reorder')">Riordina</button>
                    </div>
                </div>
                <slot name="before-stops" />
                <JourneyAddStopForm v-if="!readOnly && addStopOpen" id="journey-add-stop-form" :lookups="lookups" :busy="isLoading" @command="(operation, input, onSaved) => $emit('command', operation, input, onSaved)" @saved="addStopOpen = false" />
                <div v-if="reorderEnabled" class="space-y-3">
                    <p class="text-sm opacity-70">Sposta le tappe con le frecce oppure trascina la maniglia.</p>
                    <JourneyReorderActions :busy="isLoading" @cancel="$emit('toggle-reorder')" @save="$emit('save-reorder')" />
                    <draggable v-model="reorderDraft" :item-key="stop => stop.id" handle=".stop-handle" :animation="150" :disabled="isLoading" tag="div" class="space-y-2">
                        <template #item="{ element: stop, index }">
                            <div class="min-w-0 rounded-xl border border-base-300 p-3 space-y-2">
                                <div class="flex items-start gap-2">
                                    <span class="stop-handle flex min-h-12 w-8 shrink-0 cursor-grab items-center justify-center" aria-hidden="true">⠿</span>
                                    <div class="min-w-0"><p class="font-semibold break-words">{{ index + 1 }}. {{ stopTitle(stop) }}</p><p class="text-sm opacity-70 break-words">{{ stopSubtitle(stop) }}</p></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" class="btn btn-outline min-h-12" :aria-label="`Sposta su ${stopTitle(stop)}`" :disabled="isLoading || index === 0" @click="$emit('move-stop', index, -1)">↑ Sposta su</button>
                                    <button type="button" class="btn btn-outline min-h-12" :aria-label="`Sposta giù ${stopTitle(stop)}`" :disabled="isLoading || index === reorderDraft.length - 1" @click="$emit('move-stop', index, 1)">↓ Sposta giù</button>
                                </div>
                            </div>
                        </template>
                    </draggable>
                    <JourneyReorderActions :busy="isLoading" @cancel="$emit('toggle-reorder')" @save="$emit('save-reorder')" />
                </div>
                <div v-else class="space-y-2">
                    <button v-if="finishedStops.length" type="button" class="btn btn-ghost min-h-12 w-full whitespace-normal" :aria-expanded="showFinished" :disabled="isLoading" @click="showFinished = !showFinished">{{ showFinished ? 'Nascondi' : 'Mostra' }} tappe concluse ({{ finishedStops.length }})</button>
                    <p v-if="!stops.length" class="text-sm opacity-70">Nessuna tappa disponibile.</p>
                    <p v-else-if="!remainingStops.length" class="text-sm opacity-70">Non ci sono altre tappe da svolgere.</p>
                    <article v-for="stop in visibleStops" :key="stop.id" class="min-w-0 rounded-xl bg-base-100 transition-colors" :class="[stop.id === currentStopId ? 'border-2 border-success bg-success/10' : 'border border-base-300', { 'ring-2 ring-primary': selectedStopId === stop.id && stop.id !== currentStopId }]">
                        <button type="button" class="block min-h-12 w-full rounded-xl p-3 text-left hover:bg-base-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary" :aria-pressed="selectedStopId === stop.id" :aria-current="stop.id === currentStopId ? 'step' : undefined" :disabled="isLoading" @click="$emit('select-stop', stop.id)">
                        <span class="flex flex-wrap items-center gap-2 text-xs"><JourneyStatusBadge v-if="stop.id === currentStopId" :status="stop.status" kind="stop">Tappa corrente</JourneyStatusBadge><JourneyStatusBadge v-else :status="stop.status" kind="stop" /></span>
                        <span class="mt-2 block break-words font-semibold" :class="{ 'text-lg': stop.id === currentStopId }">{{ stopTitle(stop) }}</span>
                        <span v-if="stopSubtitle(stop)" class="mt-1 block break-words text-sm opacity-70">{{ stopSubtitle(stop) }}</span>
                        </button>
                        <JourneyStopContacts v-if="isPending(stop)" :stop="stop" class="px-3 pb-3" />
                        <JourneyContainerMovements v-if="stop.kind !== 'technical' && !['skipped', 'cancelled'].includes(stop.status)" :orders="stopContainerOrders(stop)" :census="census" :completed="normalizeJourneyStopStatus(stop.status) === JOURNEY_STOP_STATUS.DONE" class="px-3 pb-2" />
                    </article>
                </div>
            </aside>
</template>

<script setup>
import { computed, ref } from 'vue'
import JourneyAddStopForm from '@/Components/Journey/JourneyAddStopForm.vue'
import JourneyReorderActions from './JourneyReorderActions.vue'
import draggable from 'vuedraggable'
import { JOURNEY_STOP_STATUS, normalizeJourneyStopStatus } from '@/Constants/journeyStopStatus'
import { stopTitle, stopSubtitle } from './driverStopPresentation'
import JourneyStatusBadge from '@/Components/UI/JourneyStatusBadge.vue'
import JourneyStopContacts from './JourneyStopContacts.vue'
import JourneyContainerMovements from '@/Components/Journey/JourneyContainerMovements.vue'
import { stopContainerOrders } from '@/utils/journeyContainers'

const props = defineProps({
    stops: { type: Array, default: () => [] },
    selectedStopId: { type: Number, default: null },
    currentStopId: { type: Number, default: null },
    reorderEnabled: Boolean,
    isLoading: Boolean,
    readOnly: Boolean,
    canStart: Boolean,
    lookups: { type: Object, default: () => ({}) },
    census: { type: Array, default: () => [] },
})
const reorderDraft = defineModel('reorderDraft', { type: Array, default: () => [] })
const showFinished = defineModel('showFinished', { type: Boolean, default: false })
const addStopOpen = ref(false)
defineEmits(['command', 'toggle-reorder', 'save-reorder', 'move-stop', 'select-stop'])
const isPending = stop => [JOURNEY_STOP_STATUS.PLANNED, JOURNEY_STOP_STATUS.IN_PROGRESS].includes(normalizeJourneyStopStatus(stop.status))
const remainingStops = computed(() => props.stops.filter(isPending))
const reorderableStops = remainingStops
const finishedStops = computed(() => props.stops.filter(stop => !remainingStops.value.some(pending => pending.id === stop.id)))
// Il filtro comprime le concluse senza alterare la sequenza operativa ricevuta.
const visibleStops = computed(() => showFinished.value ? props.stops : remainingStops.value)
</script>
