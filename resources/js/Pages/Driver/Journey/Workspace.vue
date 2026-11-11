<template>
    <Head :title="`Viaggio #${journey.id}`" />
    <section class="mx-auto w-full max-w-6xl min-w-0 space-y-4" :aria-busy="isLoading">
        <header class="space-y-2">
            <div class="flex items-center gap-2">
                <Link :href="returnTo" class="btn btn-square btn-outline min-h-12 shrink-0" aria-label="Torna ai viaggi"><font-awesome-icon :icon="['fas', 'arrow-left']" aria-hidden="true" /></Link>
                <h1 class="min-w-0 flex-1 text-lg font-bold break-words">Viaggio #{{ journey.id }}</h1>
                <JourneyStatusBadge :status="journey.status" class="shrink-0" />
            </div>
            <DisclosureCard>
                <template #summary>
                    <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1 text-xs">
                        <span>{{ journeyDateLabel }}</span>
                        <span>{{ completedStops.length }} di {{ stops.length }} tappe completate</span>
                    </div>
                </template>
                <JourneyVehicleSummary :vehicle="journey.vehicle" :trailer="journey.trailer" />
                <JourneyContainerMovements :orders="journeyOrders.filter(order => order.execution_status !== 'released')" :census="journey.load_census_items ?? []" :completed="['eseguito', 'chiuso'].includes(journey.status)" :balance="journey.container_balance" aria-label="Totali contenitori del viaggio" />
                <section class="space-y-1 text-sm">
                    <h5 class="font-medium">Note per il viaggio</h5>
                    <p class="whitespace-pre-line break-words">{{ journey.notes?.trim() || 'nessuna nota' }}</p>
                </section>
                <button v-if="canStart" type="button" class="btn btn-success mt-3 min-h-12 w-full sm:w-auto" :disabled="isLoading || !stops.length" @click="startJourney()">Inizia viaggio</button>
            </DisclosureCard>
        </header>
        <p v-if="isJourneyCreated(journey.status) && !stops.length" class="alert alert-warning text-sm">Il piano non contiene tappe strutturate. La Logistica deve completarlo e aggiornare i documenti prima della partenza.</p>
        <p v-if="journey.legacy_execution" class="alert alert-warning text-sm">{{ canViewExecutionHistory ? 'Viaggio avviato prima del nuovo processo: consultazione disponibile, piano originale non ricostruibile automaticamente.' : 'Viaggio disponibile in sola consultazione.' }}</p>
        <JourneyExecutionTools v-if="journey.has_baseline" :journey="journey" :read-only="!canManage" :can-view-history="canViewExecutionHistory" :busy="isLoading" @command="executeCommand" />
        <p v-if="isLoading" role="status" class="text-sm">Salvataggio in corso…</p>

        <div class="grid min-w-0 grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <JourneyStopsPanel
                :stops="stops"
                :lookups="executionCatalogs"
                @command="executeCommand"
                :selected-stop-id="selectedStopId"
                :current-stop-id="currentStop?.id ?? null"
                :reorder-enabled="reorderEnabled"
                :is-loading="isLoading"
                :read-only="!canManage || journey.status !== 'attivo'"
                :can-start="canStart"
                :census="journey.load_census_items ?? []"
                v-model:reorder-draft="reorderDraft"
                v-model:show-finished="showFinished"
                @toggle-reorder="toggleReorder"
                @save-reorder="saveReorder"
                @move-stop="moveStop"
                @select-stop="selectStop"
            >
                <template #before-stops><JourneyStartReorderPrompt v-if="startReorderPrompt" :busy="isLoading" :error="startReorderError" @cancel="startReorderPrompt = false" @confirm="startJourney(true)" /></template>
            </JourneyStopsPanel>

            <section ref="detailPanel" class="min-w-0 scroll-mt-20 space-y-4 lg:border-l lg:border-base-300 lg:pl-4" aria-labelledby="stop-detail-heading" tabindex="-1">
                <hr class="border-base-300 lg:hidden" />
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 id="stop-detail-heading" class="text-lg font-semibold">Dettaglio tappa</h2><JourneyStatusBadge v-if="isCurrentSelected" :status="selectedStop.status" kind="stop">Tappa corrente</JourneyStatusBadge></div>
                <p v-if="!selectedStop" class="text-sm opacity-70">Seleziona una tappa per vedere i dettagli.</p>
                <template v-else>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold break-words">{{ stopTitle(selectedStop) }}</h3>
                        <p v-if="stopSubtitle(selectedStop)" class="text-sm break-words opacity-80">{{ stopSubtitle(selectedStop) }}</p>
                        <div v-if="!isCurrentSelected || selectedStop.kind === 'technical'" class="flex flex-wrap gap-2"><JourneyStatusBadge v-if="!isCurrentSelected" :status="selectedStop.status" kind="stop" /><span v-if="selectedStop.kind === 'technical'" class="badge badge-neutral">Sosta tecnica</span></div>
                    </div>
                    <JourneyStopContacts :stop="selectedStop" />
                    <JourneyStopSiteNotes :stop="selectedStop" :editable="canManage && journey.status === 'attivo'" :busy="isLoading" @command="executeCommand" />
                    <div v-if="canManage && ['done','skipped','cancelled'].includes(selectedStop.status)" class="space-y-2">
                        <button type="button" class="btn btn-outline min-h-12" :disabled="isLoading || !selectedStop.reopen_available" @click="reopenStop(selectedStop.id)">Riapri tappa</button>
                        <p class="text-sm" :class="selectedStop.reopen_available ? 'opacity-70' : 'text-warning'">{{ selectedStop.reopen_available ? 'La tappa torna corrente. Quantità, pesi e consegne già registrati vengono conservati.' : selectedStop.reopen_unavailable_reason }}</p>
                    </div>
                    <details v-if="selectedStop.kind !== 'technical' && stopPrimarySite(selectedStop)" class="rounded-xl border border-base-300 p-3">
                        <summary class="min-h-12 cursor-pointer py-3 font-semibold">Orari e attrezzature della sede</summary>
                        <p class="mt-2 text-sm break-words"><span class="font-medium">Attrezzature: </span>{{ stopMachinerySummary(selectedStop) }}</p>
                        <p v-if="!stopTimetableSummary(selectedStop).length" class="mt-2 text-sm opacity-70">Nessun orario configurato.</p>
                        <dl v-else class="mt-3 space-y-2"><div v-for="slot in stopTimetableSummary(selectedStop)" :key="slot.dayNumber" class="flex flex-wrap justify-between gap-x-3 gap-y-1 text-sm" :class="{ 'font-bold': slot.isToday }"><dt>{{ slot.label }}</dt><dd>{{ slot.output }}</dd></div></dl>
                    </details>
                    <div v-if="canManage && journey.status === 'attivo' && isCurrentSelected" class="rounded-xl bg-base-200 p-3 space-y-3">
                        <p class="text-sm font-medium">Esito della tappa</p>
                        <ul class="list-disc pl-4 text-xs space-y-1">
                            <li><strong>Completa:</strong> conferma le quantità correnti.</li>
                            <li><strong>Salta:</strong> esclude la tappa dal viaggio corrente.</li>
                        </ul>
                        <div class="grid grid-cols-2 items-stretch gap-3">
                            <button type="button" class="btn btn-outline h-auto min-h-12 min-w-0 whitespace-normal px-2 py-3" :disabled="isLoading" :aria-expanded="skipPanelOpen" @click="skipPanelOpen = !skipPanelOpen">{{ skipPanelOpen ? 'Annulla salto' : 'Salta' }}</button>
                            <button type="button" class="btn btn-success h-auto min-h-12 min-w-0 whitespace-normal px-2 py-3" :disabled="isLoading" @click="completeCurrentStop">Completa</button>
                        </div>
                        <div v-if="skipPanelOpen" class="space-y-3">
                            <label for="skip-reason" class="block text-sm font-medium">Motivo del salto</label>
                            <select id="skip-reason" v-model="skipForm.reason_code" class="select select-bordered min-h-12 w-full" :disabled="isLoading"><option value="" disabled>Seleziona un motivo</option><option v-for="reason in skipReasons" :key="reason.value" :value="reason.value">{{ reason.label }}</option></select>
                            <label for="skip-notes" class="block text-sm font-medium">Note {{ skipForm.reason_code === 'other' ? '(obbligatorie)' : '(facoltative)' }}</label>
                            <textarea id="skip-notes" v-model="skipForm.driver_notes" class="textarea textarea-bordered w-full" rows="3" :disabled="isLoading"></textarea>
                            <button type="button" class="btn btn-warning min-h-12 w-full" :disabled="isLoading || !skipForm.reason_code || (skipForm.reason_code === 'other' && !skipForm.driver_notes.trim())" @click="confirmSkip">Conferma salto</button>
                        </div>
                    </div>
                    <section v-if="stopOrdersCount(selectedStop)" class="space-y-3" aria-label="Ordini collegati">
                        <article v-for="order in stopOrders(selectedStop)" :key="order.id" class="min-w-0 rounded-xl border border-base-300 p-3 space-y-3 text-sm">
                            <h4 class="font-semibold break-words">Ordine {{ order.legacy_code || `#${order.id}` }}</h4>
                            <p v-if="orderCustomerNotes(order)" class="whitespace-pre-line break-words"><span class="font-semibold">Note cliente: </span>{{ orderCustomerNotes(order) }}</p>
                            <p v-if="order.notes" class="whitespace-pre-line break-words"><span class="font-semibold">Note ordine: </span>{{ order.notes }}</p>
                            <JourneyExecutionOrderEditor v-if="canManage && journey.status === 'attivo' && order.execution_status === 'pending'" :order="order" :has-trailer="!!journey.trailer_id" :show-original-plan="canViewExecutionHistory" :lookups="executionCatalogs" :census="journey.load_census_items ?? []" :busy="isLoading" @command="executeCommand" />
                            <JourneyOrderMaterials v-else :show-original-plan="canViewExecutionHistory" :order="order" :census="journey.load_census_items ?? []" :completed="isSelectedCompleted" />
                            <p v-if="order.execution_status === 'released'" class="text-warning">Ordine rilasciato alla Logistica: nessuna movimentazione eseguita.</p>
                        </article>
                    </section>
                </template>
            </section>
        </div>
        <JourneyWarehouseExecution v-if="warehouseAccess && journey.has_baseline" :journey="journey" :orders="journeyOrders" :warehouses="executionCatalogs.warehouses || []" :busy="isLoading" @command="executeCommand" />
        <aside class="rounded-2xl border border-base-300 p-4 space-y-2"><h2 class="font-semibold">Scarico a magazzino</h2><p class="text-sm opacity-70">Terminate le tappe, chiama la Logistica per concordare le modalità di scarico.</p></aside>
    </section>
</template>

<script setup>
import {useStore} from 'vuex'
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { materialCommandMessage } from '@/utils/journeyCommandFeedback'
import { Head, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import DisclosureCard from '@/Components/UI/DisclosureCard.vue'
import JourneyWarehouseExecution from '@/Components/Journey/JourneyWarehouseExecution.vue'
import JourneyExecutionOrderEditor from '@/Components/Journey/JourneyExecutionOrderEditor.vue'
import JourneyExecutionTools from '@/Components/Journey/JourneyExecutionTools.vue'
import JourneyStopsPanel from './Components/JourneyStopsPanel.vue'
import JourneyVehicleSummary from '@/Components/Journey/JourneyVehicleSummary.vue'
import JourneyStopContacts from './Components/JourneyStopContacts.vue'
import JourneyStopSiteNotes from './Components/JourneyStopSiteNotes.vue'
import JourneyStartReorderPrompt from './Components/JourneyStartReorderPrompt.vue'
import JourneyStatusBadge from '@/Components/UI/JourneyStatusBadge.vue'
import JourneyOrderMaterials from '@/Components/Journey/JourneyOrderMaterials.vue'
import JourneyContainerMovements from '@/Components/Journey/JourneyContainerMovements.vue'
import { stopContainerOrders, uniqueContainerOrders } from '@/utils/journeyContainers'
import { stopOrders, stopOrdersCount, stopTitle, stopSubtitle } from './Components/driverStopPresentation'
import { isJourneyCreated } from '@/Constants/journeyStatus'
import { JOURNEY_STOP_STATUS, normalizeJourneyStopStatus } from '@/Constants/journeyStopStatus'

const props = defineProps({
    journey: Object,
    warehouseAccess: { type: Boolean, default: false },
    canViewExecutionHistory: { type: Boolean, default: false },
    executionLookups: { type: Object, default: () => ({}) },
    readOnly: { type: Boolean, default: true },
})

const journey = ref(props.journey)
const executionCatalogs = ref(props.executionLookups)
watch(() => props.executionLookups, value => { executionCatalogs.value = value })
const startReorderPrompt = ref(false), startReorderError = ref('')
const canManage = computed(() => !props.readOnly && journey.value.has_baseline && ['attivo', 'eseguito'].includes(journey.value.status))
const canStart = computed(() => !props.readOnly && !journey.value.has_baseline && isJourneyCreated(journey.value.status))
const page = usePage()
const returnTo = computed(() => {
    const query = new URLSearchParams((page.url || '').split('?')[1] || '')
    const value = query.get('return_to')
    // Accetta soltanto il percorso noto della dashboard, mantenendo la data selezionata.
    return value && /^\/dashboard-driver(?:\?date=\d{4}-\d{2}-\d{2})?$/.test(value)
        ? value : route('driver.home')
})
const stops = ref([])
const journeyOrders = computed(() => uniqueContainerOrders(stops.value.flatMap(stopContainerOrders)))
const selectedStopId = ref(null)
const reorderEnabled = ref(false)
const reorderDraft = ref([])
const isLoading = ref(false)
const skipPanelOpen = ref(false)
const showFinished = ref(false)
const detailPanel = ref(null)
const notificationStore = useStore()
const notificationIds = new Set()
onUnmounted(() => { for (const id of notificationIds) notificationStore.dispatch('flash/removeMessage',id) })
const notify = async (type,text,action = null) => {
    const id = await notificationStore.dispatch('flash/queueMessage',{type,text,action})
    if (action) notificationIds.add(id)
}
const notifySuccess = text => {
    const undo = undoAction.value
    notify('success',text,undo ? {
        label:'Annulla ripristino',
        disabled:()=>isLoading.value || undoAction.value?.event_id !== undo.event_id || undo.revision !== journey.value.execution_revision,
        run:undoRestore,
    }:null)
}
const undoAction = ref(null)
const journeyDateLabel = computed(() => journey.value.planned_start_at
    ? new Intl.DateTimeFormat('it-IT', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Europe/Rome' }).format(new Date(journey.value.planned_start_at))
    : 'Partenza non pianificata')

watch(selectedStopId, () => { skipPanelOpen.value = false })

const selectStop = async (id) => {
    selectedStopId.value = id
    await nextTick()
    if (window.matchMedia('(max-width: 1023px)').matches) {
        detailPanel.value?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', block: 'start' })
        detailPanel.value?.focus({ preventScroll: true })
    }
}

const moveStop = (index, direction) => {
    const target = index + direction
    if (isLoading.value || target < 0 || target >= reorderDraft.value.length) return
    const draft = [...reorderDraft.value]
    ;[draft[index], draft[target]] = [draft[target], draft[index]]
    reorderDraft.value = draft
}

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

const normalizeStops = (list) => {
    const items = Array.isArray(list) ? [...list] : []
    return items.sort((a, b) => {
        const aSeq = a.sequence ?? a.planned_sequence ?? 0
        const bSeq = b.sequence ?? b.planned_sequence ?? 0
        return aSeq - bSeq
    })
}

const selectedStop = computed(() => stops.value.find((stop) => stop.id === selectedStopId.value) || null)
const isSelectedCompleted = computed(() => normalizeJourneyStopStatus(selectedStop.value?.status) === JOURNEY_STOP_STATUS.DONE)

const currentStop = computed(() => stops.value.find((stop) => normalizeJourneyStopStatus(stop.status) === JOURNEY_STOP_STATUS.IN_PROGRESS) || null)

const isCurrentSelected = computed(() => currentStop.value && selectedStop.value?.id === currentStop.value.id)

const reorderableStops = computed(() =>
    stops.value.filter((stop) => [JOURNEY_STOP_STATUS.PLANNED, JOURNEY_STOP_STATUS.IN_PROGRESS].includes(normalizeJourneyStopStatus(stop.status)))
)
const remainingStops = computed(() => reorderableStops.value)
const completedStops = computed(() => stops.value.filter((stop) => normalizeJourneyStopStatus(stop.status) === JOURNEY_STOP_STATUS.DONE))
const finishedStops = computed(() => stops.value.filter((stop) => !remainingStops.value.some((remaining) => remaining.id === stop.id)))

const refreshFromJourney = (data) => {
    journey.value = data
    stops.value = normalizeStops(data?.stops || [])
    if (!stops.value.some((stop) => stop.id === selectedStopId.value)) {
        const current = stops.value.find((stop) => normalizeJourneyStopStatus(stop.status) === JOURNEY_STOP_STATUS.IN_PROGRESS)
        selectedStopId.value = current?.id ?? stops.value[0]?.id ?? null
        if (finishedStops.value.some((stop) => stop.id === selectedStopId.value)) showFinished.value = true
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

const stopPrimarySite = (stop) => stopOrders(stop)[0]?.site ?? null

const stopMachinerySummary = (stop) => {
    const site = stopPrimarySite(stop)
    if (!site) return 'non disponibile'

    const machines = []
    if (site.has_muletto) machines.push('Muletto')
    if (site.has_electric_pallet_truck ?? site.has_transpallet_el) machines.push('Transpallet elettrico')
    if (site.has_manual_pallet_truck ?? site.has_transpallet_ma) machines.push('Transpallet manuale')
    if (site.other_machines) machines.push(site.other_machines)

    return machines.length > 0 ? machines.join(', ') : 'nessuna attrezzatura indicata'
}

const stopTimetableSummary = (stop) => {
    const site = stopPrimarySite(stop)
    const raw = site?.timetable?.hours_json
    if (!raw) return []

    try {
        const dayLabels = {
            1: 'Lunedì',
            2: 'Martedì',
            3: 'Mercoledì',
            4: 'Giovedì',
            5: 'Venerdì',
            6: 'Sabato',
            7: 'Domenica',
        }
        const rows = JSON.parse(raw)
        if (!Array.isArray(rows)) return []
        const today = new Date()
        const todayPos = today.getDay() === 0 ? 7 : today.getDay()

        return rows
            .sort((a, b) => Number(a.position ?? 0) - Number(b.position ?? 0))
            .map((row) => {
                const dayNumber = Number(row.position ?? 0)
                const day = dayLabels[dayNumber] ?? `Giorno ${dayNumber || '-'}`
                const morning = formatTimeRange(row.orarioApM, row.orarioChM)
                const afternoon = formatTimeRange(row.orarioApP, row.orarioChP)

                return {
                    dayNumber,
                    label: day,
                    output: `${morning} - ${afternoon}`,
                    isToday: dayNumber === todayPos,
                }
            })
    } catch (error) {
        return []
    }
}

const formatTimeRange = (from, to) => {
    const start = (from ?? '00:00').toString().trim()
    const end = (to ?? '00:00').toString().trim()
    if (start === '00:00' && end === '00:00') return 'Chiuso'
    return `${start}-${end}`
}

const orderCustomerNotes = (order) => order?.customer?.notes ?? null


const toggleReorder = () => {
    if (isLoading.value) return
    if (canStart.value) { startReorderError.value = ''; startReorderPrompt.value = true; return }
    if (!canManage.value || journey.value.status !== 'attivo') return
    reorderEnabled.value = !reorderEnabled.value
    if (reorderEnabled.value) {
        reorderDraft.value = reorderableStops.value.map((stop) => stop)
    }
}

const executeCommand = async (operation, input = {}, onSaved = null) => {
    if (isLoading.value || (!canManage.value && !(props.warehouseAccess && ['warehouse_item','close'].includes(operation)))) return false
    const previousJourney = journey.value
    undoAction.value = null
    isLoading.value = true
    try {
        const response = operation === 'update_site_notes'
            ? await axios.put(`/api/journeys/${journey.value.id}/sites/${input.site_id}/notes`, {notes:input.notes,notes_version:input.notes_version})
            : await axios.post(`/api/journeys/${journey.value.id}/execution/commands`, {revision:journey.value.execution_revision,operation,input})
        refreshFromJourney(response.data.journey)
        if (typeof onSaved === 'function') onSaved()
        undoAction.value = response.data.undo ?? null
        notifySuccess(operation === 'update_site_notes' ? 'Note della sede aggiornate correttamente.'
            : operation === 'set_item_allocations' ? 'Suddivisione salvata correttamente.'
            : operation === 'set_item_loaded' ? (input.is_loaded ? 'Carico aggiornato.' : 'Materiale da ritirare.')
            : operation === 'undo_restore' ? 'Ripristino annullato.'
            : ['add_item','update_item'].includes(operation) ? materialCommandMessage(operation,input,response.data.journey,previousJourney)
            : operation === 'reopen' ? 'Tappa riaperta correttamente.'
            : undoAction.value ? 'Valore ripristinato correttamente.' : 'Modifica salvata correttamente.')
        return true
    } catch (error) {
        notify('error',(error?.response?.data?.message || 'Operazione non riuscita. Riprova.') + (error?.response?.status === 409 ? ' Ricarica la pagina per vedere i dati aggiornati.' : ''))

        return false
    } finally { isLoading.value = false }
}

const reopenStop = async id => {
    if (await executeCommand('reopen', { stop_id: id })) { selectedStopId.value = id; skipPanelOpen.value = false }
}

const undoRestore = async () => {
    if (!undoAction.value || undoAction.value.revision !== journey.value.execution_revision) return
    return executeCommand('undo_restore', { event_id: undoAction.value.event_id })
}

const saveReorder = async () => {
    if (await executeCommand('reorder', { stop_ids: reorderDraft.value.map(s => s.id) })) {
        reorderEnabled.value = false
        reorderDraft.value = []
    }
}
const startJourney = async (reorderAfterStart = false) => {
    if (!canStart.value || isLoading.value) return
    isLoading.value = true
    startReorderError.value = ''
    try {
        const response = await axios.post(`/api/driver/journeys/${journey.value.id}/start`)
        executionCatalogs.value = response.data.executionLookups ?? {}
        refreshFromJourney(response.data.journey)
        startReorderPrompt.value = false
        selectedStopId.value = currentStop.value?.id ?? selectedStopId.value
        if (reorderAfterStart) {
            reorderDraft.value = reorderableStops.value.map(stop => stop)
            reorderEnabled.value = true
        }
        notify('success','Viaggio iniziato correttamente.')
    } catch (error) {
        const message = error?.response?.data?.message || 'Avvio non riuscito.'
        if (reorderAfterStart) startReorderError.value = message
        else notify('error',message)
    } finally { isLoading.value = false }
}
const afterStopOutcome = () => {
    skipPanelOpen.value = false
    if (currentStop.value) selectedStopId.value = currentStop.value.id
    else showFinished.value = true
}
const completeCurrentStop = async () => {
    if (currentStop.value && await executeCommand('complete', { stop_id: currentStop.value.id, confirm_current_quantities: true })) afterStopOutcome()
}
const confirmSkip = async () => {
    if (!currentStop.value) return
    if (await executeCommand('skip', { stop_id: currentStop.value.id, ...skipForm.value })) {
        skipForm.value = { reason_code: '', driver_notes: '' }
        afterStopOutcome()
    }
}
</script>
