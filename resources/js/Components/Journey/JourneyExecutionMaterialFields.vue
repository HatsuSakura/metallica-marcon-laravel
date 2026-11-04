<template>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label class="form-control min-w-0">Codice CER<select v-model="cer" required class="select select-bordered min-h-12 w-full min-w-0" :disabled="disabled"><option disabled value="">Seleziona CER</option><option v-for="option in lookups.cers" :key="option.id" :value="option.id">{{ option.code }} — {{ option.description }}</option></select></label>
        <div class="min-w-0 space-y-1 sm:col-span-2">
            <label class="label min-h-12 justify-start gap-3"><input v-model="bulk" type="checkbox" class="checkbox" :disabled="disabled" />Materiale sfuso</label>
            <div class="grid grid-cols-[minmax(0,1fr)_9rem] items-end gap-3">
                <label class="form-control min-w-0">Tipo contenitore<select v-model="holder" class="select select-bordered min-h-12 w-full min-w-0" :disabled="disabled || bulk"><option :value="null">Non indicato</option><option v-for="option in lookups.holders" :key="option.id" :value="option.id">{{ option.name }}</option></select></label>
                <div class="form-control min-w-0"><span>Numero</span><QuantityCounter v-model="quantity" label="Numero contenitori" :required="!bulk" :disabled="disabled || bulk" /></div>
            </div>
        </div>
        <label class="form-control min-w-0 sm:col-span-2">Descrizione del materiale<textarea v-model="description" class="textarea textarea-bordered w-full min-w-0" rows="2" :disabled="disabled" /></label>
        <label class="form-control min-w-0">Peso del materiale (kg)<input v-model="weight" type="number" min="0" step="0.001" class="input input-bordered min-h-12 w-full min-w-0" :disabled="disabled" /></label>
        <div v-if="!includeActuals" class="min-w-0 space-y-1"><p>Zona di carico</p><JourneyItemCargoSelector v-model="cargo" :has-trailer="hasTrailer" :disabled="disabled" /></div>
    </div>
    <details v-if="includeActuals" :open="modelValue.actual_containers != null || modelValue.actual_weight_kg != null" class="border-t border-base-300 pt-2">
        <summary class="cursor-pointer py-3 font-medium">Quantità effettive registrate</summary>
        <p class="mb-2 text-xs opacity-70">Se non ancora registrate, le quantità correnti vengono confermate con «Completa».</p>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="form-control min-w-0"><span>Contenitori effettivamente ritirati</span><QuantityCounter v-model="actualQuantity" label="Contenitori effettivamente ritirati" placeholder="—" :disabled="disabled" /></div>
            <label class="form-control min-w-0">Peso effettivo (kg)<input v-model="actualWeight" type="number" min="0" step="0.001" placeholder="Non ancora registrato" class="input input-bordered min-h-12 w-full min-w-0" :disabled="disabled" /></label>
        </div>
    </details>
</template>
<script setup>
import {computed} from 'vue'
import QuantityCounter from '@/Components/UI/QuantityCounter.vue'
import JourneyItemCargoSelector from './JourneyItemCargoSelector.vue'
const props = defineProps({modelValue:{type:Object,required:true},lookups:{type:Object,required:true},disabled:Boolean,includeActuals:Boolean,hasTrailer:{type:Boolean,default:true}})
const emit = defineEmits(['update:modelValue'])
const field = key => computed({get:()=>props.modelValue[key],set:value=>emit('update:modelValue',{...props.modelValue,[key]:value})})
const bulk = computed({
    get:()=>!!props.modelValue.is_bulk,
    set:value=>emit('update:modelValue', value
        ? {...props.modelValue,is_bulk:true,holder_id:null,holder_quantity:null}
        : {...props.modelValue,is_bulk:false}),
})
const cer=field('cer_code_id'), holder=field('holder_id'), description=field('description'), quantity=field('holder_quantity'), weight=field('weight_declared'), cargo=field('cargo_location'), actualQuantity=field('actual_containers'), actualWeight=field('actual_weight_kg')
</script>
