<template>
    <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-3">
        <div class="min-w-0 space-y-1">
            <h3 class="text-sm font-semibold">Cliente</h3>
            <div class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1">
                <p class="min-w-0 break-words font-semibold">{{ site.customer?.company_name || 'Cliente non indicato' }}</p>
                <Address :address="site.customer?.legal_address" class="text-sm" />
            </div>
        </div>
        <div class="min-w-0 space-y-1">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h3 class="text-sm font-semibold">Sede di carico</h3>
                <p class="text-sm">Fattore di rischio: <span class="badge badge-outline" :class="riskTone">{{ riskPercent }}%</span></p>
            </div>
            <div class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1">
                <p class="min-w-0 break-words font-semibold">{{ site.name }}</p>
                <Address :address="site.address" class="text-sm" />
            </div>
        </div>
        <div class="min-w-0 space-y-1">
            <h3 class="text-sm font-semibold">Consulente ADR</h3>
            <div class="flex items-center gap-2">
                <font-awesome-icon :icon="['fas', 'vial-circle-check']" :class="hasAdrConsultant ? 'text-success' : 'text-base-content'" aria-hidden="true" />
                <span class="badge" :class="hasAdrConsultant ? 'badge-success' : 'badge-neutral'">{{ hasAdrConsultant ? 'Presente' : 'Assente' }}</span>
            </div>
        </div>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import Address from '@/Components/Address.vue'
const props = defineProps({site:{type:Object,required:true}})
const hasAdrConsultant = computed(() => [true, 1, '1'].includes(props.site.has_adr_consultant))
const factor = computed(() => Number(props.site.calculated_risk_factor ?? 0))
const riskPercent = computed(() => Number.isFinite(factor.value) ? Number((factor.value*100).toFixed(1)) : 0)
const riskTone = computed(() => factor.value >= .85 ? 'badge-error' : factor.value >= .75 ? 'badge-warning' : factor.value >= .5 ? 'badge-info' : 'badge-success')
</script>
