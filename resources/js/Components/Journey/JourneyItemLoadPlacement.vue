<template>
    <div class="min-w-0 space-y-1">
        <template v-if="editing">
            <form class="space-y-1" @submit.prevent="save">
                <p class="text-xs">Totale: <strong>{{ total ?? '—' }}</strong> {{ item.holder?.name || 'contenitori' }}</p>
                <div v-for="carrier in carriers" :key="carrier.value" class="flex items-center gap-2">
                    <font-awesome-icon :icon="['fas', carrier.icon]" class="h-5 w-5 shrink-0" :title="carrier.label" aria-hidden="true" />
                    <QuantityCounter class="min-w-0 flex-1" :model-value="draft[carrier.value]" :max="total" required :disabled="disabled" :label="`Contenitori in ${carrier.label.toLowerCase()}`" @update:model-value="value => draft = changeSplit(draft,carrier.value,value,total)" />
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button type="button" class="btn btn-circle btn-outline min-h-[42px] h-[42px] w-[42px]" :disabled="disabled" aria-label="Annulla suddivisione" title="Annulla" @click="editing = false"><font-awesome-icon :icon="['fas','xmark']" aria-hidden="true" /></button>
                    <button type="submit" class="btn btn-circle btn-primary min-h-[42px] h-[42px] w-[42px]" :disabled="disabled || !validSplit(draft,total)" aria-label="Salva suddivisione" title="Salva"><font-awesome-icon :icon="['fas','floppy-disk']" aria-hidden="true" /></button>
                    <template v-if="hasSplit">
                        <button v-for="carrier in carriers" :key="carrier.value" type="button" class="btn btn-circle btn-outline min-h-[42px] h-[42px] w-[42px] shrink-0" :disabled="disabled || (carrier.value === 'trailer' && !hasTrailer)" :aria-label="`Tutto in ${carrier.label.toLowerCase()}`" :title="`Tutto in ${carrier.label.toLowerCase()}`" @click="moveAll(carrier.value)"><font-awesome-icon :icon="['fas',carrier.icon]" class="h-5 w-5" aria-hidden="true" /></button>
                    </template>
                </div>
            </form>
        </template>
        <div v-else-if="hasSplit" class="space-y-1">
            <div v-for="carrier in carriers" :key="carrier.value" class="flex items-center gap-2">
                <JourneyContainerQuantities :groups="allocationGroups(carrier.value)" />
                <font-awesome-icon :icon="['fas',carrier.icon]" class="h-5 w-5 shrink-0" aria-hidden="true" />
                <span class="text-xs">{{ carrier.label }}</span>
                <button v-if="!readOnly && carrier.value === 'vehicle'" type="button" class="btn btn-square btn-outline h-[42px] min-h-[42px] w-[42px] shrink-0" :disabled="disabled || !hasTrailer" aria-label="Modifica suddivisione" title="Modifica suddivisione" @click="open"><font-awesome-icon :icon="['fas','code-branch']" aria-hidden="true" /></button>
            </div>
        </div>
        <div v-else class="flex flex-wrap items-center gap-2">
            <JourneyContainerSummary :items="[item]" :census="census" :completed="completed" :show-label="false" />
            <JourneyItemCargoSelector v-if="!readOnly" :model-value="item.cargo_location" :has-trailer="hasTrailer" :disabled="disabled" @update:model-value="moveAll" />
            <span v-else-if="item.cargo_location" class="inline-flex items-center gap-1 text-xs"><font-awesome-icon :icon="['fas',item.cargo_location === 'trailer' ? 'trailer' : 'truck']" aria-hidden="true" />{{ item.cargo_location === 'trailer' ? 'Rimorchio' : 'Motrice' }}</span>
            <button v-if="!readOnly && canSplit" type="button" class="btn btn-square btn-outline h-[42px] min-h-[42px] w-[42px] shrink-0" :disabled="disabled" aria-label="Suddividi carico tra motrice e rimorchio" title="Suddividi carico" @click="open"><font-awesome-icon :icon="['fas','code-branch']" aria-hidden="true" /></button>
        </div>
        <p v-if="item.load_allocation_valid === false" class="text-xs text-warning">Il totale è cambiato: aggiorna la suddivisione ({{ total ?? '—' }} contenitori).</p>
    </div>
</template>
<script setup>
import {computed,ref} from 'vue'
import QuantityCounter from '@/Components/UI/QuantityCounter.vue'
import JourneyContainerSummary from './JourneyContainerSummary.vue'
import JourneyContainerQuantities from './JourneyContainerQuantities.vue'
import JourneyItemCargoSelector from './JourneyItemCargoSelector.vue'
import {loadTotal,splitDraft,changeSplit,validSplit} from '@/utils/journeyItemLoad'
const props = defineProps({item:{type:Object,required:true},disabled:Boolean,readOnly:Boolean,hasTrailer:{type:Boolean,default:true},completed:Boolean,census:{type:Array,default:()=>[]}})
const emit = defineEmits(['command'])
const carriers = [{value:'vehicle',label:'Motrice',icon:'truck'},{value:'trailer',label:'Rimorchio',icon:'trailer'}]
const editing = defineModel('editing',{type:Boolean,default:false})
const draft = ref({vehicle:0,trailer:0})
const total = computed(() => loadTotal(props.item))
const hasSplit = computed(() => !!props.item.load_allocations?.length)
const canSplit = computed(() => props.hasTrailer && !props.item.is_bulk && total.value > 1)
const open = () => {if (props.disabled || props.readOnly) return; draft.value = splitDraft(props.item); editing.value = true}
const allocationGroups = location => [{key:location,type:props.item.holder?.name || 'contenitori',quantity:props.item.load_allocations.find(row => row.cargo_location === location)?.containers ?? 0,missing:false,bulk:false}]
const save = () => {
    if (props.disabled || !validSplit(draft.value,total.value)) return
    emit('command','set_item_allocations',{item_id:props.item.id,vehicle_containers:draft.value.vehicle,trailer_containers:draft.value.trailer},()=>{editing.value=false})
}
const moveAll = location => emit('command','update_item',{item_id:props.item.id,cargo_location:location},()=>{editing.value=false})
</script>
