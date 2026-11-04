<template>
    <div class="inline-flex items-center gap-1" role="group" aria-label="Carico del materiale">
        <button v-for="option in options" :key="option.value" type="button" class="btn btn-square h-[42px] min-h-[42px] w-[42px] min-w-[42px] p-0" :class="modelValue === option.value ? 'btn-primary' : 'btn-outline'" :disabled="disabled || (option.value === 'trailer' && !hasTrailer)" :aria-pressed="modelValue === option.value" :aria-label="`Carica in ${option.label.toLowerCase()}`" :title="option.value === 'trailer' && !hasTrailer ? 'Rimorchio non presente' : option.label" @click="modelValue !== option.value && $emit('update:modelValue', option.value)">
            <font-awesome-icon :icon="['fas', option.icon]" class="h-5 w-5" aria-hidden="true" />
            <span class="sr-only">{{ option.label }}</span>
        </button>
        <span v-if="!modelValue" class="sr-only">Zona di carico da scegliere</span>
    </div>
</template>
<script setup>
defineProps({modelValue:{type:String,default:null},disabled:Boolean,hasTrailer:{type:Boolean,default:true}})
defineEmits(['update:modelValue'])
const options = [{value:'vehicle',label:'Motrice',icon:'truck'},{value:'trailer',label:'Rimorchio',icon:'trailer'}]
</script>
