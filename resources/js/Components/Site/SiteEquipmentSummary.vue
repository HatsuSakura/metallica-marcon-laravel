<template>
    <div class="space-y-3">
        <div class="grid grid-cols-1 gap-3" :class="compact ? '' : includeAdr ? 'sm:grid-cols-2 xl:grid-cols-4' : 'sm:grid-cols-2 xl:grid-cols-3'">
            <SiteOperationalParameter v-for="entry in entries" :key="entry.key" :title="entry.title" :icon-string="entry.icon" :boolean-value="entry.value" :positive-label="entry.key === 'has_adr_consultant' ? 'Presente' : 'Disponibile'" :negative-label="entry.key === 'has_adr_consultant' ? 'Assente' : 'Non disponibile'" />
        </div>
        <p v-if="showOtherMachines" class="break-words text-sm"><span class="font-semibold">Altri macchinari: </span>{{ site.other_machines || 'Nessuna indicazione specifica' }}</p>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import SiteOperationalParameter from './SiteOperationalParameter.vue'
const props = defineProps({site:{type:Object,required:true}, includeAdr:{type:Boolean,default:true}, showOtherMachines:Boolean, compact:Boolean})
const available = value => value === true || value === 1 || value === '1'
const entries = computed(() => [
    ...(props.includeAdr ? [{key:'has_adr_consultant',title:'Consulente ADR',icon:'vial-circle-check',value:available(props.site.has_adr_consultant)}] : []),
    {key:'has_muletto',title:'Muletto',icon:'truck-ramp-box',value:available(props.site.has_muletto)},
    {key:'has_electric_pallet_truck',title:'Transpallet elettrico',icon:'cart-flatbed',value:available(props.site.has_electric_pallet_truck ?? props.site.has_transpallet_el)},
    {key:'has_manual_pallet_truck',title:'Transpallet manuale',icon:'dolly',value:available(props.site.has_manual_pallet_truck ?? props.site.has_transpallet_ma)},
])
</script>
