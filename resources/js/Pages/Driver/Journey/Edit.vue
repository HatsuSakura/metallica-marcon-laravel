<template>
    <section>
        <HeaderForDashboard
            :backLinkRoute="'driver.journey.index'"
            :backLinkText="'Viaggi'"
        >
            Gestione Viaggio
        </HeaderForDashboard>

        <div class="flex flex-row items-center justify-between gap-3 mb-4">
            <JourneyMainData :journey="journey" />
            <div class="flex items-center gap-2">
                <span class="badge badge-outline">
                    Stato: {{ journey.status }}
                </span>
                <button
                    v-if="journey.status === 'creato'"
                    type="button"
                    class="btn btn-success btn-sm"
                    :disabled="isLoading"
                    @click="startJourney"
                >
                    <font-awesome-icon :icon="['fas', 'play']" class="text-lg"/>
                    Inizia viaggio
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Box class="lg:col-span-1">
                <template #header>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="font-semibold">Tappe viaggio</div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="reorderableStops.length > 1"
                                type="button"
                                class="btn btn-outline btn-xs"
                                @click="toggleReorder"
                            >
                                <font-awesome-icon
                                    :icon="['fas', reorderEnabled ? 'xmark' : 'arrows-up-down-left-right']"
                                    class="text-sm"
                                />
                                {{ reorderEnabled ? 'Annulla' : 'Riordina' }}
                            </button>
                            <button
                                v-if="reorderEnabled"
                                type="button"
                                class="btn btn-primary btn-xs"
                                :disabled="isLoading"
                                @click="saveReorder"
                            >
                                <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-sm"/>
                                Salva ordine
                            </button>
                        </div>
                    </div>
                </template>

                <div v-if="reorderEnabled" class="space-y-2">
                    <div class="text-xs opacity-70">
                        Solo le tappe pianificate o in corso sono riordinabili.
                    </div>
                    <draggable
                        v-model="reorderDraft"
                        :item-key="stop => stop.id"
                        handle=".stop-handle"
                        :animation="150"
                        tag="div"
                        class="space-y-2"
                    >
                        <template #item="{ element: stop, index }">
                            <Box padding="p-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-start gap-2 min-w-0">
                                        <font-awesome-icon
                                            :icon="['fas', 'grip-vertical']"
                                            class="stop-handle mt-1 cursor-grab select-none opacity-60"
                                        />
                                        <div class="min-w-0">
                                            <div class="font-semibold truncate">
                                                {{ index + 1 }}. {{ stopTitle(stop) }}
                                            </div>
                                            <div v-if="stopSubtitle(stop)" class="text-xs opacity-70 truncate">
                                                Sede: {{ stopSubtitle(stop) }}
                                            </div>
                                        </div>
                                    </div>
                                    <span v-if="stopOrdersCount(stop) > 0" class="badge badge-neutral">
                                        {{ stopOrdersCount(stop) }} ordini
                                    </span>
                                </div>
                            </Box>
                        </template>
                    </draggable>
                </div>

                <div v-else class="space-y-2">
                    <button
                        v-for="stop in stops"
                        :key="stop.id"
                        type="button"
                        class="btn btn-ghost w-full justify-start"
                        :class="selectedStopId === stop.id ? 'btn-active' : ''"
                        @click="selectedStopId = stop.id"
                    >
                        <div class="flex items-start gap-2 w-full">
                            <span class="badge badge-outline">
                                {{ stopStatusLabel(stop.status) }}
                            </span>
                            <div class="min-w-0 text-left">
                                <div class="font-semibold truncate">{{ stopTitle(stop) }}</div>
                                <div v-if="stopSubtitle(stop)" class="text-xs opacity-70 truncate">
                                    {{ stopSubtitle(stop) }}
                                </div>
                            </div>
                        </div>
                    </button>
                </div>





<!--
                    <div class="border-t pt-4 space-y-3">
                        <div class="font-semibold">Aggiungi sosta tecnica</div>
                        <select v-model="techForm.technical_action_id" class="select select-bordered w-full">
                            <option value="" disabled>Tipo di sosta tecnica</option>
                            <option v-for="action in journeyStopActions" :key="action.id" :value="action.id">
                                {{ action.label }}
                            </option>
                        </select>
                        <input
                            v-model="techForm.description"
                            type="text"
                            class="input input-bordered w-full"
                            placeholder="Descrizione breve (opzionale)"
                        />
                        <input
                            v-model="techForm.address_text"
                            type="text"
                            class="input input-bordered w-full"
                            placeholder="address (opzionale)"
                        />
                        <textarea
                            v-model="techForm.driver_notes"
                            class="textarea textarea-bordered w-full"
                            rows="2"
                            placeholder="Note driver (opzionali)"
                        ></textarea>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :disabled="isLoading"
                            @click="createTechnicalStop"
                        >
                            Aggiungi
                        </button>
                    </div>
-->


            </Box>

            <Box class="lg:col-span-2">
                <template #header>
                    <div class="flex items-center justify-between">
                        <div class="font-semibold">Dettaglio tappa</div>
                        <div v-if="currentStop" class="badge badge-primary">
                            Tappa corrente
                        </div>
                    </div>
                </template>

                <div v-if="!selectedStop" class="text-sm opacity-70">
                    Seleziona una tappa per vedere i dettagli.
                </div>

                <div v-else class="space-y-4">
                    <div>
                        <div class="text-lg font-semibold">{{ stopTitle(selectedStop) }}</div>
                        <div v-if="stopSubtitle(selectedStop)" class="text-sm opacity-80">
                            {{ stopSubtitle(selectedStop) }}
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="badge badge-outline">
                                Stato: {{ stopStatusLabel(selectedStop.status) }}
                            </span>
                            <span v-if="selectedStop.kind === 'technical'" class="badge badge-neutral">
                                Tecnica
                            </span>
                        </div>
                    </div>

                    <div v-if="stopOrdersCount(selectedStop) > 0" class="space-y-1">
                        <div class="font-semibold">Ordini collegati</div>
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="order in stopOrders(selectedStop)"
                                :key="order.id"
                                class="rounded-box border border-base-300 bg-base-100 p-3 text-sm"
                            >
                                <div class="font-semibold">
                                    {{ String(order.legacy_code)}} 
                                </div>
                                <div class="mt-1 whitespace-pre-line">
                                    <span class="font-semibold">Note Cliente: </span>
                                    <span v-if="orderCustomerNotes(order)" >{{ orderCustomerNotes(order) }}</span>
                                    <span v-else class="italic opacity-70">Nessuna nota cliente</span>
                                </div>
                                <div class="mt-1 whitespace-pre-line">
                                    <span class="font-semibold">Note Sede: </span>
                                    <span v-if="orderSiteNotes(order)" >{{ orderSiteNotes(order) }}</span>
                                    <span v-else class="italic opacity-70">Nessuna nota sede</span>
                                </div>
                                <div class="mt-1 whitespace-pre-line">
                                    <span class="font-semibold">Note Ordine: </span>
                                    <span v-if="order.notes" >{{ order.notes }}</span>
                                    <span v-else class="italic opacity-70">Nessuna nota ordine</span>
                                </div>
                                <div v-if="orderItems(order).length > 0" class="mt-2">
                                    <div class="font-semibold">Dettaglio elementi:</div>
                                    <ul class="list-disc ml-5">
                                        <li v-for="item in orderItems(order)" :key="item.id ?? item.uuid ?? item.temp_id">
                                            {{ itemSummary(item) }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="isCurrentSelected" class="flex flex-col gap-3">
                        <div class="font-semibold">Azioni</div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="btn btn-success"
                                :disabled="isLoading"
                                @click="completeCurrentStop"
                            >
                                <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                                Completa
                            </button>
                            <button
                                type="button"
                                class="btn btn-warning"
                                :disabled="isLoading"
                                @click="skipPanelOpen = !skipPanelOpen"
                            >
                                <font-awesome-icon :icon="['fas', 'forward']" class="text-lg"/>
                                Salta
                            </button>
                        </div>

                        <div v-if="skipPanelOpen" class="space-y-2">
                            <select v-model="skipForm.reason_code" class="select select-bordered w-full">
                                <option value="" disabled>Motivazione</option>
                                <option v-for="reason in skipReasons" :key="reason.value" :value="reason.value">
                                    {{ reason.label }}
                                </option>
                            </select>
                            <textarea
                                v-model="skipForm.driver_notes"
                                class="textarea textarea-bordered w-full"
                                rows="3"
                                placeholder="Note del driver (obbligatorie)"
                            ></textarea>
                            <button
                                type="button"
                                class="btn btn-warning"
                                :disabled="isLoading"
                                @click="confirmSkip"
                            >
                                Conferma salto
                            </button>
                        </div>
                    </div>
                </div>
            </Box>
        </div>

        <Box class="my-4">
            <template #header>Scarico a magazzino</template>
            <div class="text-sm opacity-70">
                Terminate le tappe di consegna, chiamare in sede la Logistica per concordare le modalità di scarico.
            </div>
        </Box>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import draggable from 'vuedraggable'
import Box from '@/Components/UI/Box.vue'
import HeaderForDashboard from '@/Components/UI/HeaderForDashboard.vue'
import JourneyMainData from './Components/JourneyMainData.vue'

const props = defineProps({
    journey: Object,
    journeyStopActions: Object,
})

const journey = ref(props.journey)
const journeyStopActions = computed(() => props.journeyStopActions || [])
const stops = ref([])
const selectedStopId = ref(null)
const reorderEnabled = ref(false)
const reorderDraft = ref([])
const isLoading = ref(false)
const skipPanelOpen = ref(false)

const skipReasons = [
    { value: 'traffic', label: 'Traffico intenso' },
    { value: 'over_capacity', label: 'Mezzo sovraccarico' },
    { value: 'customer_closed', label: 'Cliente chiuso' },
    { value: 'customer_refused', label: 'Cliente rifiuta' },
    { value: 'vehicle_issue', label: 'Problema al mezzo' },
    { value: 'other', label: 'Altro' },
]

const skipForm = ref({
    reason_code: '',
    driver_notes: '',
})

const techForm = ref({
    technical_action_id: '',
    description: '',
    address_text: '',
    driver_notes: '',
})

const normalizeStops = (list) => {
    const items = Array.isArray(list) ? [...list] : []
    return items.sort((a, b) => {
        const aSeq = a.sequence ?? a.planned_sequence ?? 0
        const bSeq = b.sequence ?? b.planned_sequence ?? 0
        return aSeq - bSeq
    })
}

const selectedStop = computed(() => stops.value.find((stop) => stop.id === selectedStopId.value) || null)

const currentStop = computed(() => stops.value.find((stop) => stop.status === 'in_progress') || null)

const isCurrentSelected = computed(() => currentStop.value && selectedStop.value?.id === currentStop.value.id)

const reorderableStops = computed(() =>
    stops.value.filter((stop) => ['planned', 'in_progress'].includes(stop.status))
)

const refreshFromJourney = (data) => {
    journey.value = data
    stops.value = normalizeStops(data?.stops || [])
    if (!selectedStopId.value) {
        const current = stops.value.find((stop) => stop.status === 'in_progress')
        selectedStopId.value = current?.id ?? stops.value[0]?.id ?? null
    }
    if (reorderEnabled.value) {
        reorderDraft.value = reorderableStops.value.map((stop) => stop)
    }
}

watch(
    () => props.journey,
    (val) => {
        refreshFromJourney(val)
    },
    { immediate: true }
)

const stopOrders = (stop) => {
    if (!stop) return []
    if (Array.isArray(stop.orders)) return stop.orders
    const stopOrdersList = stop.stop_orders ?? stop.stopOrders
    if (!Array.isArray(stopOrdersList)) return []
    return stopOrdersList.map((so) => so?.order ?? so).filter(Boolean)
}

const stopOrdersCount = (stop) => stopOrders(stop).length

const stopTitle = (stop) => {
    if (!stop) return '-'
    if (stop.kind === 'technical') {
        return stop.technical_action?.label ?? stop.technical_action?.name ?? stop.description ?? 'Sosta tecnica'
    }
    return stop.customer?.company_name ?? `Cliente #${stop.customer_id ?? '-'}`
}

const stopSubtitle = (stop) => {
    if (!stop) return null
    if (stop.address_text) return stop.address_text
    const firstOrder = stopOrders(stop)[0]
    return firstOrder?.site?.address ?? null
}

const stopStatusLabel = (status) => {
    switch (status) {
        case 'in_progress':
            return 'In corso'
        case 'done':
            return 'Completata'
        case 'skipped':
            return 'Saltata'
        case 'cancelled':
            return 'Annullata'
        default:
            return 'Pianificata'
    }
}

const orderItems = (order) => {
    if (!order || !Array.isArray(order.items)) return []
    return order.items
}

const itemSummary = (item) => {
    const cer = item?.cer_code?.code ?? item?.cerCode?.code ?? '-'
    const description = item?.description ? ` - ${item.description}` : ''
    const weight = item?.weight_declared ? ` (${item.weight_declared} kg)` : ''
    return `CER ${cer}${description}${weight}`
}

const orderSiteAddress = (order) => order?.site?.address ?? null

const orderCustomerNotes = (order) => order?.customer?.notes ?? null

const orderSiteNotes = (order) => order?.site?.notes ?? null

const toggleReorder = () => {
    reorderEnabled.value = !reorderEnabled.value
    if (reorderEnabled.value) {
        reorderDraft.value = reorderableStops.value.map((stop) => stop)
    }
}

const saveReorder = async () => {
    if (reorderDraft.value.length === 0) return
    isLoading.value = true
    try {
        const response = await axios.put(`/api/driver/journeys/${journey.value.id}/stops/reorder`, {
            stop_ids: reorderDraft.value.map((stop) => stop.id),
        })
        reorderEnabled.value = false
        reorderDraft.value = []
        refreshFromJourney(response.data.journey)
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false
    }
}

const startJourney = async () => {
    isLoading.value = true
    try {
        const response = await axios.post(`/api/driver/journeys/${journey.value.id}/start`)
        refreshFromJourney(response.data.journey)
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false
    }
}

const completeCurrentStop = async () => {
    if (!currentStop.value) return
    isLoading.value = true
    try {
        const response = await axios.put(
            `/api/driver/journeys/${journey.value.id}/stops/${currentStop.value.id}/complete`
        )
        refreshFromJourney(response.data.journey)
        skipPanelOpen.value = false
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false
    }
}

const confirmSkip = async () => {
    if (!currentStop.value) return
    if (!skipForm.value.reason_code || !skipForm.value.driver_notes) return
    isLoading.value = true
    try {
        const response = await axios.put(
            `/api/driver/journeys/${journey.value.id}/stops/${currentStop.value.id}/skip`,
            skipForm.value
        )
        refreshFromJourney(response.data.journey)
        skipForm.value.reason_code = ''
        skipForm.value.driver_notes = ''
        skipPanelOpen.value = false
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false
    }
}

const createTechnicalStop = async () => {
    if (!techForm.value.technical_action_id) return
    isLoading.value = true
    try {
        const response = await axios.post(
            `/api/driver/journeys/${journey.value.id}/stops/technical`,
            techForm.value
        )
        refreshFromJourney(response.data.journey)
        techForm.value = {
            technical_action_id: '',
            description: '',
            address_text: '',
            driver_notes: '',
        }
    } catch (error) {
        console.error(error)
    } finally {
        isLoading.value = false
    }
}
</script>
