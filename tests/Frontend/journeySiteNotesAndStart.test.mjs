import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
const Notes = await loadVueComponent(new URL('../../resources/js/Pages/Driver/Journey/Components/JourneyStopSiteNotes.vue',import.meta.url))
const Prompt = await loadVueComponent(new URL('../../resources/js/Pages/Driver/Journey/Components/JourneyStartReorderPrompt.vue',import.meta.url))
const Stops = await loadVueComponent(new URL('../../resources/js/Pages/Driver/Journey/Components/JourneyStopsPanel.vue',import.meta.url))
const site={id:1,name:'Magazzino',notes:'Sbarra nuova: chiamare prima di entrare.',notes_version:'a'.repeat(64),notes_editable:true}
const order=(id,s=site)=>({id,execution_status:'pending',site:s,items:[],holders:[]})
test('site notes appear once per site across multiple orders with the same site',async()=>{
    const html=await renderToString(createSSRApp(Notes,{stop:{kind:'customer',orders:[order(1),order(2)]},editable:true}))
    assert.equal((html.match(/Sbarra nuova:/g)||[]).length,1)
    assert.equal((html.match(/aria-label="Modifica note della sede"/g)||[]).length,1)
})
test('multiple sites retain distinct notes and deleted/released sites are read-only',async()=>{
    const other={...site,id:2,name:'Deposito',notes:null,notes_editable:false}
    const html=await renderToString(createSSRApp(Notes,{stop:{kind:'customer',orders:[order(1),order(2,other)]},editable:true}))
    assert.match(html,/Magazzino/)
    assert.match(html,/Deposito/)
    assert.match(html,/Nessuna nota della sede/)
    assert.equal((html.match(/aria-label="Modifica note della sede"/g)||[]).length,1)
    const released=await renderToString(createSSRApp(Notes,{stop:{kind:'customer',orders:[{...order(1),execution_status:'released'}]},editable:true}))
    assert.doesNotMatch(released,/aria-label="Modifica note della sede"/)
})
test('pre-start reorder stays discoverable without enabling operational add-stop',async()=>{
    const html=await renderToString(createSSRApp(Stops,{stops:[{id:1,status:'planned'},{id:2,status:'planned'}],readOnly:true,canStart:true}))
    assert.match(html,/>Riordina</)
    assert.doesNotMatch(html,/\+ Aggiungi/)
    assert.doesNotMatch(html,/Salva ordine/)
    const history=await renderToString(createSSRApp(Stops,{stops:[{id:1,status:'planned'},{id:2,status:'planned'}],readOnly:true,canStart:false}))
    assert.doesNotMatch(history,/>Riordina</)
})
test('confirmation explicitly records departure and shows blocked-start errors',async()=>{
    const html=await renderToString(createSSRApp(Prompt,{busy:false,error:'Genera documenti aggiornati prima di partire.'}))
    assert.match(html,/inizio effettivo del viaggio/)
    assert.match(html,/contatta la Logistica/)
    assert.match(html,/Avvia e riordina/)
    assert.match(html,/Annulla/)
    assert.match(html,/role="alert"/)
    assert.match(html,/Genera documenti aggiornati/)
    const busy=await renderToString(createSSRApp(Prompt,{busy:true}))
    assert.equal((busy.match(/ disabled/g)||[]).length,2)
})
