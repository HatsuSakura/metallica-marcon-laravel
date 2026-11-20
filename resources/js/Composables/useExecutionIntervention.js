import {reactive,onBeforeUnmount} from 'vue'
import {router} from '@inertiajs/vue3'
import axios from 'axios'
import {draftInput,draftOperations} from '@/utils/executionDraft'

export function useExecutionIntervention(journey,refresh) {
    const state=reactive({active:false,busy:false,id:null,revision:0,commands:[],references:{}})
    const reset=()=>Object.assign(state,{active:false,busy:false,id:null,revision:0,commands:[],references:{}})
    const url=()=>`/api/journeys/${journey.value.id}/execution/interventions`
    const begin=()=>{if(state.busy)return;Object.assign(state,{active:true,id:crypto.randomUUID(),revision:journey.value.execution_revision,commands:[],references:{}})}
    const queue=async(operation,input)=>{
        if(!draftOperations.has(operation)) throw new Error('Conferma o annulla le modifiche prima di eseguire questa azione.')
        if(state.commands.length>=50) throw new Error('Conferma questo intervento prima di aggiungere altre modifiche.')
        const command={id:crypto.randomUUID(),operation,input:draftInput(operation,input,state.references)}
        const commands=[...state.commands,command]
        const response=await axios.post(url(),{intervention_id:state.id,revision:state.revision,commands,preview:true})
        state.commands=commands
        state.references=response.data.references || {}
        return response
    }
    const commit=async()=>{
        if(!state.active || state.busy || !state.commands.length)return
        state.busy=true
        try {
            const response=await axios.post(url(),{intervention_id:state.id,revision:state.revision,commands:state.commands})
            refresh(response.data.journey,response.data.references)
            reset()
        }finally{state.busy=false}
    }
    const cancel=async()=>{
        if(state.busy)return
        state.busy=true
        try {refresh((await axios.get(`/api/journeys/${journey.value.id}/execution`)).data.journey);reset()}
        finally{state.busy=false}
    }
    const warn=event=>{if(state.active && state.commands.length){event.preventDefault();event.returnValue=''}}
    window.addEventListener('beforeunload',warn)
    const unsubscribe=router.on('before',event=>{
        if(state.active && state.commands.length && !window.confirm('Le modifiche non sono ancora confermate. Vuoi lasciare la pagina?')) event.preventDefault()
    })
    onBeforeUnmount(()=>{window.removeEventListener('beforeunload',warn);unsubscribe?.()})
    return Object.assign(state,{begin,queue,commit,cancel})
}
