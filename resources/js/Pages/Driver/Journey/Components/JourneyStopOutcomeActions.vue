<template>
                    <div class="space-y-3 border-t border-base-300 pt-3" aria-label="Esito della tappa">
                        <p v-if="!compact" class="text-sm font-medium">Esito della tappa</p>
                        <ul v-if="!compact" class="list-disc pl-4 text-sm space-y-1">
                            <li><strong>Completa:</strong> conferma le quantità correnti.</li>
                            <li><strong>Salta:</strong> esclude la tappa dal viaggio corrente.</li>
                        </ul>
                        <div class="grid grid-cols-2 items-stretch gap-3">
                            <AppAction type="button" :variant="skipPanelOpen ? 'cancel' : 'neutral'" class="min-w-0 whitespace-normal px-2 py-3" :disabled="busy" :aria-expanded="skipPanelOpen" @click="skipPanelOpen = !skipPanelOpen">{{ skipPanelOpen ? 'Annulla salto' : 'Salta' }}</AppAction>
                            <AppAction type="button" class="min-w-0 whitespace-normal px-2 py-3" :disabled="busy" @click="$emit('complete')" variant="success">Completa</AppAction>
                        </div>
                        <div v-if="skipPanelOpen" class="space-y-3">
                            <label :for="`${idPrefix}-reason`" class="block text-sm font-medium">Motivo del salto</label>
                            <select :id="`${idPrefix}-reason`" v-model="skipForm.reason_code" class="select select-bordered min-h-12 w-full" :disabled="busy"><option value="" disabled>Seleziona un motivo</option><option v-for="reason in skipReasons" :key="reason.value" :value="reason.value">{{ reason.label }}</option></select>
                            <label :for="`${idPrefix}-notes`" class="block text-sm font-medium">Note {{ skipForm.reason_code === 'other' ? '(obbligatorie)' : '(facoltative)' }}</label>
                            <textarea :id="`${idPrefix}-notes`" v-model="skipForm.driver_notes" class="textarea textarea-bordered w-full" rows="3" :disabled="busy"></textarea>
                            <AppAction type="button" class="w-full" :disabled="busy || !skipForm.reason_code || (skipForm.reason_code === 'other' && !skipForm.driver_notes.trim())" @click="$emit('skip')" variant="warning">Conferma salto</AppAction>
                        </div>
                    </div>
</template>
<script setup>
import {watch} from 'vue'
import {useDraftBindings} from '@/Services/localDrafts'
import AppAction from '@/Components/UI/AppAction.vue'
const props=defineProps({stopId:[Number,String],busy:Boolean,compact:Boolean, idPrefix:{type:String,required:true}, skipReasons:{type:Array,required:true}})
defineEmits(['complete','skip'])
const skipPanelOpen = defineModel('skipPanelOpen', {type:Boolean,default:false})
const skipForm = defineModel('skipForm', {type:Object,required:true})
const localDrafts=useDraftBindings()
watch(()=>props.stopId,id=>{
    if(!id)return
    localDrafts.bind(`stop-note:${id}`,{label:`Note della tappa #${id}`,value:()=>props.stopId===id?skipForm.value:{reason_code:'',driver_notes:''},baseline:()=>({reason_code:'',driver_notes:''}),fields:{reason_code:'Motivo',driver_notes:'Note'},canRestore:()=>props.stopId===id,restore:value=>{skipForm.value={...value};skipPanelOpen.value=true}})
},{immediate:true})

</script>
