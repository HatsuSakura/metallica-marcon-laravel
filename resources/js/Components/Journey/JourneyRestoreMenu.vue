<template>
    <details class="mt-2">
        <summary class="cursor-pointer py-3 font-semibold">Ripristina</summary>
        <label v-if="fields.length > 1" class="form-control min-w-0">Campo da ripristinare<select v-model="field" class="select select-bordered w-full min-w-0" :disabled="busy"><option v-for="option in fields" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
        <p v-if="dirty" class="mt-2 text-sm text-warning">Prima di ripristinare, salva o scarta le modifiche non salvate a questo materiale.</p>
        <template v-if="selected">
            <p class="mt-3 text-sm break-words"><span class="font-medium">Valore salvato attuale: </span>{{ selected.current.label }}</p>
            <p v-if="selected.same_targets" class="mt-2 text-sm break-words"><span class="font-medium">Precedente e originale alla partenza: </span>{{ selected.previous.label }}</p>
            <template v-else>
                <p v-if="selected.previous.available" class="mt-2 text-sm break-words"><span class="font-medium">Valore precedente: </span>{{ selected.previous.label }}</p>
                <p v-if="selected.original.available" class="mt-2 text-sm break-words"><span class="font-medium">Originale alla partenza: </span>{{ selected.original.label }}</p>
                <p v-else class="mt-2 text-sm opacity-70">{{ selected.original.reason }}</p>
            </template>
            <p v-if="!selected.previous.available" class="mt-2 text-sm opacity-70">{{ selected.previous.reason }}</p>
            <div class="mt-3 flex flex-wrap gap-2"><button v-for="action in actions" :key="action.operation" type="button" class="btn btn-outline h-auto min-h-12 max-w-full whitespace-normal" :disabled="busy || dirty" @click="emit('command', action.operation, {item_id:itemId,field})">{{ action.label }}</button></div>
            <p v-if="actions.length" class="mt-2 text-xs opacity-70">Puoi annullare il ripristino dal messaggio di conferma.</p>
        </template>
        <p v-else class="mt-2 text-sm opacity-70">Nessun valore ripristinabile.</p>
    </details>
</template>
<script setup>
import {computed, ref} from 'vue'
import {restoreActions, restoreFields} from '@/utils/journeyRestore'
const props = defineProps({itemId:Number, options:{type:Object,default:()=>({})}, fields:{type:Array,default:()=>restoreFields}, busy:Boolean, dirty:Boolean})
const emit = defineEmits(['command'])
const field = ref(props.fields[0]?.value || 'holder_quantity')
const selected = computed(() => props.options[field.value])
const actions = computed(() => restoreActions(selected.value))
</script>
