<template>
    <div class="space-y-4">
        <section v-for="group in sections" :key="group.key" class="space-y-2 border-b border-base-300 pb-2" :aria-label="group.isUnassigned ? 'CER non indicato' : `CER ${group.cerCode}`">
            <h5 class="flex flex-wrap items-center gap-2 font-bold"><UiBadge :tone="group.isUnassigned ? 'warning' : 'primary'">{{ group.isUnassigned ? 'CER non indicato' : `CER ${group.cerCode}` }}</UiBadge><slot v-if="group.items.length === 1" name="item-checklist" :item="group.items[0].item"><span class="text-xs font-normal opacity-70">{{ completed ? 'Ritirato' : group.items[0].item.is_loaded ? 'Caricato' : 'Da ritirare' }}</span></slot></h5>
                <ul class="divide-y divide-base-300">
                    <li v-for="row in group.items" :key="row.item.id ?? row.index" class="space-y-1 py-2 break-words">
                        <slot v-if="group.items.length > 1" name="item-checklist" :item="row.item"><span class="text-xs opacity-70">{{ completed ? 'Ritirato' : row.item.is_loaded ? 'Caricato' : 'Da ritirare' }}</span></slot>
                        <div class="flex items-start gap-2">
                            <div class="min-w-0 flex-1 space-y-1">
                                <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                    <slot name="item-load" :item="row.item">
                                        <JourneyItemLoadPlacement :item="row.item" :census="census" :completed="completed" read-only />
                                    </slot>
                                    <span class="text-sm font-medium">{{ materialWeightLabel(row.item, census, completed) }}</span>
                                </div>
                                <p v-if="row.item.description" class="text-sm opacity-80">{{ row.item.description }}</p>
                            </div>
                            <slot name="item-actions" :item="row.item" />
                        </div>
                        <slot name="item-editor" :item="row.item" />
                    </li>
                </ul>
        </section>
        <details v-if="showOriginalPlan && order.original_plan">
            <summary class="min-h-12 cursor-pointer py-3 text-primary">Piano alla partenza</summary>
            <p v-for="item in order.original_plan.items" :key="item.id" class="text-sm">CER {{ item.cer_code?.code }} · {{ item.is_bulk ? 'Sfuso' : `${item.holder_quantity ?? '—'} × ${item.holder?.name || 'contenitore'}` }} · {{ item.weight_declared ?? '—' }} kg · {{ item.description }}</p>
            <p class="mt-2 text-sm">Contenitori in consegna: {{ deliveryContainerSummary(order.original_plan.holders) || 'Non indicati' }}</p>
        </details>
        <details v-else-if="showOriginalPlan && containerComparison.changed">
            <summary class="min-h-12 cursor-pointer py-3 text-primary">Confronta con il ritiro previsto</summary>
            <p class="text-sm">Contenitori da ritirare nell’ordine: {{ containerComparison.plannedSummary }}</p>
        </details>
        <section class="space-y-1" aria-label="Contenitori in consegna">
            <h5 class="font-semibold">{{ completed ? 'Contenitori consegnati' : 'Contenitori in consegna' }}</h5>
            <JourneyContainerQuantities :groups="deliveryDescription.groups" :completed="completed" empty-label="Nessun contenitore in consegna indicato" />
            <slot name="delivery-actions" />
            <p v-if="completed && order.holders?.length" class="text-xs opacity-70">Quantità effettivamente consegnate.</p>
        </section>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import UiBadge from '@/Components/UI/UiBadge.vue';
import JourneyItemLoadPlacement from './JourneyItemLoadPlacement.vue';
import JourneyContainerQuantities from './JourneyContainerQuantities.vue';
import { groupOrderItems } from '@/utils/orderItemGroups';
import { describeContainers, describeDeliveryContainers, deliveryContainerSummary, materialWeightLabel } from '@/utils/journeyContainers';
const props = defineProps({ order: { type: Object, required: true }, census: { type: Array, default: () => [] }, completed: Boolean, showOriginalPlan: {type:Boolean,default:false} });
const sections = computed(() => groupOrderItems(props.order.items ?? []));
const deliveryDescription = computed(() => describeDeliveryContainers(props.order.holders, props.completed));
const containerComparison = computed(() => describeContainers(props.order.items ?? [], props.census, props.completed));
</script>
