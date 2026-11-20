<template>
    <Head :title="`Viaggio #${journey.id}`" />
    <JourneyStopTransition v-if="stopTransition" :outcome="stopTransition" @finished="finishStopTransition" />
    <section class="mx-auto w-full max-w-6xl min-w-0 space-y-4" :inert="stopTransition ? true : undefined" :aria-busy="isLoading || !!stopTransition">
        <header class="space-y-2">
            <div class="flex items-center gap-2">
                <Link :href="returnTo" class="btn btn-square btn-outline min-h-12 shrink-0" aria-label="Torna ai viaggi"><font-awesome-icon :icon="['fas', 'arrow-left']" aria-hidden="true" /></Link>
                <h1 class="min-w-0 flex-1 text-lg font-bold break-words">Viaggio #{{ journey.id }}</h1>
                <JourneyStatusBadge :status="journey.status" class="shrink-0" />
            </div>
            <DisclosureCard :key="journey.id" :open="headerOpen" @toggle="headerOpen = $event">
                <template #summary>
                    <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1 text-xs">
                        <span>{{ journeyDateLabel }}</span>
                        <span>{{ completedStops.length }} di {{ stops.length }} tappe completate</span>
                    </div>
                </template>
                <label v-if="!readOnly" class="ml-auto flex min-h-12 w-fit max-w-full cursor-pointer items-center justify-end gap-2 text-right text-xs">
                    <span>Testata aperta ad ogni accesso</span>
                    <input type="checkbox" role="switch" class="toggle toggle-sm toggle-primary shrink-0" :checked="journey.header_open_on_entry ?? true" :disabled="preferenceBusy" @change="saveHeaderPreference($event.target.checked)" />
                </label>
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
        <ExecutionInterventionActions v-if="canBatchExecutionChange && canManage && journey.status === 'attivo'" :active="intervention.active" :count="intervention.commands.length" :busy="isLoading || intervention.busy" @begin="intervention.begin" @confirm="confirmIntervention" @cancel="cancelIntervention" />
        <JourneyExecutionTools v-if="journey.has_baseline" :journey="journey" :outcome-disabled="intervention.active" :read-only="!canManage" :can-view-history="canViewExecutionHistory" :busy="isLoading || intervention.busy" @command="executeCommand" />
        <p v-if="isLoading" role="status" class="text-sm">Salvataggio in corso…</p>

        <div class="grid min-w-0 grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)]">
            <JourneyStopsPanel
                :stops="stops"
                :lookups="executionCatalogs"
                @command="executeCommand"
                :selected-stop-id="selectedStopId"
                :current-stop-id="currentStop?.id ?? null"
                :reorder-enabled="reorderEnabled"
                :is-loading="isLoading || intervention.busy"
                :read-only="!canManage || journey.status !== 'attivo'"
                :site-notes-editable="!intervention.active"
                :can-start="canStart"
                :census="journey.load_census_items ?? []"
                v-model:reorder-draft="reorderDraft"
                v-model:show-finished="showFinished"
                @toggle-reorder="toggleReorder"
                @save-reorder="saveReorder"
                @move-stop="moveStop"
                @select-stop="selectStop"
            >
                <template #stop-actions="{stop}"><JourneyStopOutcomeActions v-if="canManage && journey.status === 'attivo' && stop.id === currentStop?.id" id-prefix="card-outcome" compact class="mx-3 mb-3" :busy="isLoading || intervention.active || intervention.busy" :skip-reasons="skipReasons" v-model:skip-panel-open="skipPanelOpen" v-model:skip-form="skipForm" @complete="completeCurrentStop" @skip="confirmSkip" /></template>
                <template #before-stops><JourneyStartReorderPrompt v-if="startReorderPrompt" :busy="isLoading || intervention.busy" :error="startReorderError" @cancel="startReorderPrompt = false" @confirm="startJourney(true)" /></template>
            </JourneyStopsPanel>

            <section ref="detailPanel" class="min-w-0 scroll-mt-20 space-y-4 lg:border-l lg:border-base-300 lg:pl-4" aria-labelledby="stop-detail-heading" tabindex="-1">
                <hr class="border-base-300 lg:hidden" />
                <div class="flex flex-wrap items-center justify-between gap-2"><h2 id="stop-detail-heading" tabindex="-1" class="text-lg font-semibold">Dettaglio tappa</h2><JourneyStatusBadge v-if="isCurrentSelected" :status="selectedStop.status" kind="stop">Tappa corrente</JourneyStatusBadge></div>
                <p v-if="!selectedStop" class="text-sm opacity-70">Seleziona una tappa per vedere i dettagli.</p>
                <template v-else>
                    <AppAction v-if="intervention.active && ['planned','in_progress'].includes(selectedStop.status)" variant="remove" :disabled="isLoading || intervention.busy" @click="executeCommand('cancel_stop',{stop_id:selectedStop.id})">Rimuovi questa tappa</AppAction>
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold break-words">{{ stopTitle(selectedStop) }}</h3>
                        <p v-if="stopSubtitle(selectedStop)" class="text-sm break-words opacity-80">{{ stopSubtitle(selectedStop) }}</p>
                        <div v-if="!isCurrentSelected || selectedStop.kind === 'technical'" class="flex flex-wrap gap-2"><JourneyStatusBadge v-if="!isCurrentSelected" :status="selectedStop.status" kind="stop" /><span v-if="selectedStop.kind === 'technical'" class="badge badge-neutral">Sosta tecnica</span></div>
                    </div>
                    <JourneyStopSiteNotes :stop="selectedStop" :id-prefix="`detail-stop-${selectedStop.id}`" :editable="canManage && !intervention.active && journey.status === 'attivo'" :busy="isLoading || intervention.busy" @command="executeCommand" />
                    <JourneyStopContacts :stop="selectedStop" />
                    <div v-if="canManage && ['done','skipped','cancelled'].includes(selectedStop.status)" class="space-y-2">
                        <button type="button" class="btn btn-primary btn-outline min-h-12" :disabled="isLoading || intervention.active || !selectedStop.reopen_available" @click="reopenStop(selectedStop.id)">Riapri tappa</button>
                        <p class="text-sm" :class="selectedStop.reopen_available ? 'opacity-70' : 'text-warning'">{{ selectedStop.reopen_available ? 'La tappa torna corrente. Quantità, pesi e consegne già registrati vengono conservati.' : selectedStop.reopen_unavailable_reason }}</p>
                    </div>
                    <details v-if="selectedStop.kind !== 'technical' && stopPrimarySite(selectedStop)" class="rounded-xl border border-base-300 p-3">
                        <summary class="min-h-12 cursor-pointer py-3 font-semibold">Orari e attrezzature della sede</summary>
                        <p class="mt-2 text-sm break-words"><span class="font-medium">Attrezzature: </span>{{ stopMachinerySummary(selectedStop) }}</p>
                        <p v-if="!stopTimetableSummary(selectedStop).length" class="mt-2 text-sm opacity-70">Nessun orario configurato.</p>
                        <dl v-else class="mt-3 space-y-2"><div v-for="slot in stopTimetableSummary(selectedStop)" :key="slot.dayNumber" class="flex flex-wrap justify-between gap-x-3 gap-y-1 text-sm" :class="{ 'font-bold': slot.isToday }"><dt>{{ slot.label }}</dt><dd>{{ slot.output }}</dd></div></dl>
                    </details>
                    <section v-if="stopOrdersCount(selectedStop)" class="space-y-3" aria-label="Ordini collegati">
                        <article v-for="order in stopOrders(selectedStop)" :key="order.id" :id="`execution-order-${order.execution_order_id || order.id}`" class="min-w-0 rounded-xl border border-base-300 p-3 space-y-3 text-sm">
                            <h4 class="font-semibold break-words">Ordine {{ order.legacy_code || `#${order.id}` }}</h4>
                            <p v-if="orderCustomerNotes(order)" class="whitespace-pre-line break-words"><span class="font-semibold">Note cliente: </span>{{ orderCustomerNotes(order) }}</p>
                            <p v-if="order.notes" class="whitespace-pre-line break-words"><span class="font-semibold">Note ordine: </span>{{ order.notes }}</p>
                            <JourneyExecutionOrderEditor v-if="canManage && journey.status === 'attivo' && order.execution_status === 'pending'" :order="order" :balance="journey.container_balance" :has-trailer="!!journey.trailer_id" :show-original-plan="canViewExecutionHistory" :lookups="executionCatalogs" :census="journey.load_census_items ?? []" :busy="isLoading || intervention.busy" @command="executeCommand" />
                            <JourneyOrderMaterials v-else :show-original-plan="canViewExecutionHistory" :order="order" :census="journey.load_census_items ?? []" :completed="isSelectedCompleted" />
                            <p v-if="order.execution_status === 'released'" class="text-warning">Ordine rilasciato alla Logistica: nessuna movimentazione eseguita.</p>
                        </article>
                    </section>
                    <JourneyStopOutcomeActions v-if="canManage && journey.status === 'attivo' && isCurrentSelected" id-prefix="detail-outcome" :busy="isLoading || intervention.active || intervention.busy" :skip-reasons="skipReasons" v-model:skip-panel-open="skipPanelOpen" v-model:skip-form="skipForm" @complete="completeCurrentStop" @skip="confirmSkip" />
                </template>
            </section>
        </div>
        <JourneyWarehouseExecution v-if="warehouseAccess && journey.has_baseline && !intervention.active" :journey="journey" :orders="journeyOrders" :warehouses="executionCatalogs.warehouses || []" :busy="isLoading || intervention.busy" @command="executeCommand" />
        <aside class="rounded-2xl border border-base-300 p-4 space-y-2"><h2 class="font-semibold">Scarico a magazzino</h2><p class="text-sm opacity-70">Terminate le tappe, chiama la Logistica per concordare le modalità di scarico.</p></aside>
    </section>
</template>

<script setup>
import {useStore} from 'vuex'
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { materialCommandMessage } from '@/utils/journeyCommandFeedback'
import {draftOperations} from '@/utils/executionDraft'
import {useExecutionIntervention} from '@/Composables/useExecutionIntervention'
import ExecutionInterventionActions from '@/Components/Journey/ExecutionInterventionActions.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import DisclosureCard from '@/Components/UI/DisclosureCard.vue'
import JourneyWarehouseExecution from '@/Components/Journey/JourneyWarehouseExecution.vue'
import JourneyExecutionOrderEditor from '@/Components/Journey/JourneyExecutionOrderEditor.vue'
import JourneyExecutionTools from '@/Components/Journey/JourneyExecutionTools.vue'
import JourneyStopTransition from './Components/JourneyStopTransition.vue'
import JourneyStopOutcomeActions from './Components/JourneyStopOutcomeActions.vue'
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
    canBatchExecutionChange:Boolean,
})

const journey = ref(props.journey)
const headerOpen = ref(props.journey.header_open_on_entry ?? true)
const preferenceBusy = ref(false)
const saveHeaderPreference = async value => {
    if (preferenceBusy.value) return
    preferenceBusy.value = true
    try {
        const response = await axios.patch(`/api/journeys/${journey.value.id}/driver-preferences`, {header_open_on_entry:value})
        journey.value.header_open_on_entry = response.data.preferences.header_open_on_entry
        notify('success', 'Preferenza di apertura della testata salvata.')
    } catch (error) {
        notify('error', error?.response?.data?.message || 'Preferenza non salvata. Riprova.')
    } finally { preferenceBusy.value = false }
}
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
const stopTransition = ref(null)
const finishStopTransition = async () => {
    stopTransition.value = null
    await nextTick()
    document.getElementById("stop-detail-heading")?.focus({preventScroll:true})
}
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
    async (val) => {
        refreshFromJourney(val)
        const requested=Number(new URLSearchParams((page.url || '').split('?')[1] || '').get('stop'))
        if(requested && stops.value.some(stop=>stop.id===requested)){selectedStopId.value=requested;showFinished.value=true}
        const requestedOrder=Number(new URLSearchParams((page.url || '').split('?')[1] || '').get('execution_order'))
        if(requestedOrder){await nextTick();document.getElementById(`execution-order-${requestedOrder}`)?.scrollIntoView({block:'center'})}
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

const refreshIntervention = (data,references = {}) => {
    const selectedToken = intervention.references.stop?.[selectedStopId.value]
    const replacement = selectedToken && Object.entries(references.stop || {}).find(([,token])=>token === selectedToken)
    if(replacement) selectedStopId.value=Number(replacement[0])
    refreshFromJourney(data)
}
const intervention = useExecutionIntervention(journey,refreshIntervention)
const confirmIntervention = async () => {
    try {await intervention.commit();notify('success','Modifiche al viaggio confermate.')}
    catch(error){notify('error',error?.response?.data?.message || 'Conferma non riuscita. Le modifiche restano da confermare.')}
}
const cancelIntervention = async () => {
    try {await intervention.cancel();notify('info','Modifiche annullate.')}
    catch(error){notify('error','Impossibile ricaricare il viaggio. Riprova prima di lasciare la pagina.')}
}
let commandAttempt = null

const executeCommand = async (operation, input = {}, onSaved = null) => {
    if (stopTransition.value || isLoading.value || intervention.busy || (!canManage.value && !(props.warehouseAccess && ['warehouse_item','close'].includes(operation)))) return false
    if(props.canBatchExecutionChange && !intervention.active && draftOperations.has(operation)) intervention.begin()
    const previousJourney = journey.value
    const signature=JSON.stringify([journey.value.execution_revision,operation,input])
    if(commandAttempt?.signature !== signature) commandAttempt={signature,id:crypto.randomUUID()}
    undoAction.value = null
    isLoading.value = true
    try {
        const selectedToken = intervention.references.stop?.[selectedStopId.value]
        const response = intervention.active ? await intervention.queue(operation,input) : operation === 'update_site_notes'
            ? await axios.put(`/api/journeys/${journey.value.id}/sites/${input.site_id}/notes`, {notes:input.notes,notes_version:input.notes_version})
            : await axios.post(`/api/journeys/${journey.value.id}/execution/commands`, {revision:journey.value.execution_revision,operation,input,intervention_id:commandAttempt.id})
        const replacement = selectedToken && Object.entries(response.data.references?.stop || {}).find(([,token])=>token === selectedToken)
        if(replacement) selectedStopId.value=Number(replacement[0])
        refreshFromJourney(response.data.journey)
        if (typeof onSaved === 'function') onSaved()
        commandAttempt = null
        undoAction.value = intervention.active ? null : response.data.undo ?? null
        if(intervention.active){notify('info','Modifica aggiunta all’intervento. Conferma per applicarla.');return true}
        if (['complete','skip'].includes(operation)) {
            const finished = previousJourney.stops?.find(stop => stop.id === input.stop_id)
            stopTransition.value = {label: finished ? stopTitle(finished) : `#${input.stop_id}`, skipped: operation === 'skip', nextLabel: currentStop.value ? stopTitle(currentStop.value) : null}
            return true
        }
        notifySuccess(operation === 'update_site_notes' ? 'Note della sede aggiornate correttamente.'
            : operation === 'set_item_allocations' ? 'Suddivisione salvata correttamente.'
            : operation === 'set_item_loaded' ? (input.is_loaded ? 'Carico aggiornato.' : 'Materiale da ritirare.')
            : operation === 'undo_restore' ? 'Ripristino annullato.'
            : ['add_item','update_item','remove_item','restore_item','hide_removed_item'].includes(operation) ? materialCommandMessage(operation,input,response.data.journey,previousJourney)
            : operation === 'reopen' ? 'Tappa riaperta correttamente.'
            : undoAction.value ? 'Valore ripristinato correttamente.' : 'Modifica salvata correttamente.')
        return true
    } catch (error) {
        notify('error',(error?.response?.data?.message || error.message || 'Operazione non riuscita. Riprova.') + (error?.response?.status === 409 ? ' Ricarica la pagina per vedere i dati aggiornati.' : ''))

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
    if (!canStart.value || isLoading.value || stopTransition.value) return
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
