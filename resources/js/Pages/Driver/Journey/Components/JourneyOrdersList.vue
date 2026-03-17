<template>
    <ul class="timeline timeline-vertical journey-orders-list">
        <li>
            <div class="timeline-start timeline-box">
                Partenza
            </div>
            <div class="timeline-middle">
                <font-awesome-icon :icon="['fas', 'circle-check']" />
            </div>
            <div class="timeline-end timeline-box">
                <div class="flex flex-row gap-2 items-center">
                    <span>
                        <font-awesome-icon :icon="['fas', 'route']" class="text-4xl"/>
                    </span>
                    <span v-if="journey.status == 'attivo' || journey.status == 'eseguito'" class="inline-flex items-center justify-center w-10 h-10 p-4 rounded-full bg-success text-white">
                        <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                    </span>

                </div>
            </div>
            <hr />
        </li>

        <!-- ELENCO FERMATE (EDIT MODE) -->
        <li v-if="props.reorderEnabled">
            <hr/>
            <div class="timeline-start timeline-box">
                <div class="text-sm opacity-70">
                    Trascina le tappe per cambiare l'ordine.
                </div>
            </div>
            <div class="timeline-middle">
                <font-awesome-icon :icon="['fas', 'circle-check']" />
            </div>
            <div class="timeline-end timeline-box w-full">
                <div class="w-full">
                    <draggable
                        v-model="reorderStops"
                        :item-key="stop => stop.id"
                        handle=".stop-handle"
                        :animation="150"
                        tag="div"
                        class="space-y-2"
                        @start="isDragging = true"
                        @end="onDragEnd"
                    >
                        <template #item="{ element: stop, index }">
                            <Box padding="p-2" class="w-full">
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
                                            <div v-if="stopSubtitle(stop)" class="text-sm opacity-80 truncate">
                                                Sede: {{ stopSubtitle(stop) }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <span v-if="stopOrdersCount(stop) > 0" class="badge badge-neutral">
                                            {{ stopOrdersCount(stop) }} ordini
                                        </span>
                                    </div>
                                </div>
                            </Box>
                        </template>
                    </draggable>
                </div>
            </div>
            <hr />
        </li>
        <!-- FINE ELENCO FERMATE (EDIT MODE) -->

        <!-- ELENCO FERMATE (VIEW MODE) -->
        <template v-else>
            <li v-for="stop in localStops" :key="stop.id">
                <hr/>
                <div class="timeline-start timeline-box">
                    <div>
                        <div class="font-semibold">
                            {{ stopTitle(stop) }}
                        </div>
                        <div v-if="stopSubtitle(stop)" class="text-sm opacity-80">
                            Sede: {{ stopSubtitle(stop) }}
                        </div>
                        <div v-if="customerNotesForStop(stop)" class="mt-2 text-sm whitespace-pre-line">
                            <span class="font-semibold">Note Cliente:</span>
                            {{ customerNotesForStop(stop) }}
                        </div>
                    </div>
                </div>
                <div class="timeline-middle">
                    <font-awesome-icon :icon="['fas', 'circle-check']" />
                </div>
                <div class="timeline-end timeline-box min-w-[22rem]">
                    <div class="flex flex-row gap-2 items-center">
                        <span v-if="stop.kind === 'technical'">
                            <font-awesome-icon :icon="['fas', 'screwdriver-wrench']" class="text-3xl"/>
                        </span>
                        <span v-else>
                            <font-awesome-icon :icon="['fas', 'map-marker-alt']" class="text-3xl"/>
                        </span>

                        <div class="flex flex-row gap-2 items-center">
                            <span
                                v-for="loc in stopTruckLocations(stop)"
                                :key="loc"
                            >
                                <font-awesome-icon v-if="loc === 'vehicle'" :icon="['fas', 'truck']" class="text-3xl"/>
                                <font-awesome-icon v-else-if="loc === 'trailer'" :icon="['fas', 'trailer']" class="text-3xl"/>
                                <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-3xl"/>
                            </span>

                            <span v-if="stopOrdersCount(stop) > 0" class="badge badge-neutral">
                                {{ stopOrdersCount(stop) }} ordini
                            </span>

                            <span v-if="isStopCompleted(stop)" class="inline-flex items-center justify-center w-10 h-10 p-4 rounded-full bg-success text-white">
                                <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                            </span>
                        </div>

                    </div>
                    <div v-if="stopOrders(stop).length > 0" class="mt-3 space-y-2">
                        <div
                            v-for="order in stopOrders(stop)"
                            :key="order.id"
                            class="rounded-box border border-base-300 bg-base-100 p-2 text-sm"
                        >
                            <div class="font-semibold">
                                Ordine {{ order.legacy_code ?? `#${order.id}` }}
                            </div>
                            <div v-if="order.site?.notes" class="mt-1 whitespace-pre-line">
                                <span class="font-semibold">Note Sede:</span>
                                {{ order.site.notes }}
                            </div>
                            <div v-if="orderItems(order).length > 0" class="mt-1">
                                <div tabindex="0" class="collapse collapse-arrow journey-items-collapse">
                                    <input type="checkbox" />
                                    <div class="collapse-title text-sm font-semibold">
                                        Dettaglio elementi ({{ orderItems(order).length }})
                                    </div>
                                    <div class="collapse-content pt-0">
                                        <ul class="list-disc ml-5">
                                            <li v-for="item in orderItems(order)" :key="item.id ?? item.uuid ?? item.temp_id">
                                                {{ itemSummary(item) }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div v-if="order.notes" class="mt-1 whitespace-pre-line">
                                <span class="font-semibold">Note Ordine:</span>
                                {{ order.notes }}
                            </div>
                        </div>
                    </div>
                </div>
                <hr />
            </li>
        </template>
        <!-- FINE ELENCO FERMATE (VIEW MODE) -->

        <!-- ELENCO MAGAZZINI -->
        <li>
            <hr/>
            <div class="timeline-start timeline-box">
                <span v-if="props.is_double_load">PRIMO </span>Scarico Magazzino
            </div>
            <div class="timeline-middle">
                <font-awesome-icon :icon="['fas', 'circle-check']" />
            </div>
            <div class="timeline-end timeline-box">
                <div class="flex flex-row gap-2 items-center">
                    <span>
                        <font-awesome-icon :icon="['fas', 'warehouse']" class="text-4xl"/>
                    </span>

                        <span v-if="journey.status == 'eseguito'" class="inline-flex items-center justify-center w-10 h-10 p-4 rounded-full bg-success text-white">
                            <font-awesome-icon :icon="['fas', 'check']" class="text-lg"/>
                        </span>

                </div>
            </div>
        </li>
    </ul>
</template>

<script setup>

import { defineProps, ref, watch } from 'vue'
import draggable from 'vuedraggable'
import Box from '@/Components/UI/Box.vue'

const props = defineProps({
    journey : Object,
    warehouses: Object,
    is_double_load: Boolean,
    reorderEnabled: Boolean,
})

const emit = defineEmits(['reorder-changed'])

const isDragging = ref(false)
const localStops = ref([])
const reorderStops = ref([])

const normalizeStops = (stops) => {
    const list = Array.isArray(stops) ? [...stops] : []
    return list.sort((a, b) => {
        const aSeq = a.sequence ?? a.planned_sequence ?? 0
        const bSeq = b.sequence ?? b.planned_sequence ?? 0
        return aSeq - bSeq
    })
}

watch(
    () => props.journey?.stops,
    (stops) => {
        if (!isDragging.value) {
            const normalized = normalizeStops(stops)
            localStops.value = normalized
            reorderStops.value = normalized.filter((stop) => ['planned', 'in_progress'].includes(stop.status))
        }
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

const stopTruckLocations = (stop) => {
    const orders = stopOrders(stop)
    const locations = []
    for (const order of orders) {
        const loc = order?.cargo_location ?? null
        if (!loc) continue
        if (!locations.includes(loc)) locations.push(loc)
    }
    return locations
}

const customerNotesForStop = (stop) => {
    if (!stop || stop.kind === 'technical') return null
    if (stop.customer?.notes) return stop.customer.notes
    const firstOrder = stopOrders(stop)[0]
    return firstOrder?.customer?.notes ?? null
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

const isStopCompleted = (stop) => {
    if (!stop) return false
    if (stop.completed_at) return true
    const status = String(stop.status ?? '').toLowerCase()
    return ['completed', 'executed', 'done'].includes(status)
}

const onDragEnd = () => {
    isDragging.value = false
    emit('reorder-changed', reorderStops.value.map((stop) => stop.id))
}
</script>

<style scoped>
.journey-orders-list .journey-items-collapse .collapse-title {
    padding: 0.125rem 0.75rem !important;
    min-height: 1.75rem !important;
    line-height: 1.1rem !important;
}

.journey-orders-list .journey-items-collapse > input[type="checkbox"] {
    min-height: 1.75rem !important;
    height: 1.75rem !important;
}

.journey-orders-list .journey-items-collapse > input[type="checkbox"] ~ .collapse-content {
    padding-top: 0 !important;
}

.journey-orders-list .journey-items-collapse.collapse-arrow > .collapse-title:after {
    top: 50% !important;
    inset-inline-end: 1rem !important;
    --tw-translate-y: -50% !important;
}
</style>
