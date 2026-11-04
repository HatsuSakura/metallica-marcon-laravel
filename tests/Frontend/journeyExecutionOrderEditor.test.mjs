import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
const Editor = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyExecutionOrderEditor.vue',import.meta.url))
const Feedback = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyExecutionFeedback.vue',import.meta.url))
const CargoSelector = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyItemCargoSelector.vue',import.meta.url))
const Fields = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyExecutionMaterialFields.vue',import.meta.url))
const lookups={cers:[{id:1,code:'020107',description:'Materiale A'},{id:2,code:'090106',description:'Materiale B'}],holders:[{id:1,name:'Fusto'},{id:2,name:'Cassa'}]}
const item=(id,quantity,weight)=>({id,cer_code_id:id,cer_code:lookups.cers[id-1],holder_id:id,holder:lookups.holders[id-1],holder_quantity:quantity,weight_declared:weight,description:'Descrizione '+id,is_bulk:false})

test('order exposes add CER and edit actions directly next to each material without duplicate lists',async()=>{
    const order={id:8,execution_order_id:1,notes:'',items:[item(1,3,900),item(2,4,840)],holders:[],removed_items:[]}
    const html=await renderToString(createSSRApp(Editor,{order,lookups,census:[]}))
    assert.match(html,/Aggiungi materiale \/ CER/)
    assert.equal((html.match(/aria-label="Modifica materiale CER/g)||[]).length,2)
    assert.equal((html.match(/Descrizione 1<\/p>/g)||[]).length,1)
    assert.equal((html.match(/Descrizione 2<\/p>/g)||[]).length,1)
    assert.doesNotMatch(html,/Modifica movimentazioni|Note dell’ordine e zona di carico|Salva note e zona di carico/)
    assert.equal((html.match(/aria-label="Carico del materiale"/g)||[]).length,2)
    assert.doesNotMatch(html,/Nuovo materiale nell’ordine/)
})
test('shared material fields expose CER, packaging, quantities, weight and description in execution',async()=>{
    const html=await renderToString(createSSRApp(Fields,{lookups,modelValue:{...item(1,3,900),actual_containers:0,actual_weight_kg:0,cargo_location:'vehicle'},includeActuals:true}))
    for(const label of ['Codice CER','Tipo contenitore','Descrizione del materiale','Numero','Peso del materiale','Contenitori effettivamente ritirati','Peso effettivo']) assert.ok(html.includes(label),label)
    assert.match(html,/<details[^>]*open/)
    assert.match(html,/value="0"/)
})

test('failed material updates stay visible in the shared mobile feedback',async()=>{
    const html=await renderToString(createSSRApp(Feedback,{message:'Il viaggio è stato aggiornato. Ricarica.',tone:'error'}))
    assert.match(html,/role="alert"/)
    assert.match(html,/border-error/)
    assert.match(html,/fixed/)
    assert.match(html,/Il viaggio è stato aggiornato/)
})

test('carrier choices support no default for capacity orders and disable an unavailable trailer',async()=>{
    const selected=await renderToString(createSSRApp(CargoSelector,{modelValue:'trailer',hasTrailer:true}))
    assert.equal((selected.match(/aria-pressed="true"/g)||[]).length,1)
    assert.match(selected,/aria-pressed="true"[^>]*aria-label="Carica in rimorchio"/)
    const capacity=await renderToString(createSSRApp(CargoSelector,{modelValue:null,hasTrailer:false}))
    assert.doesNotMatch(capacity,/aria-pressed="true"/)
    assert.match(capacity,/<button[^>]*disabled[^>]*aria-label="Carica in rimorchio"/)
    assert.match(capacity,/Zona di carico da scegliere/)
})
