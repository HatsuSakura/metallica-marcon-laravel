import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
import {describeContainers,describeDeliveryContainers} from '../../resources/js/utils/journeyContainers.js'
import {materialCommandMessage} from '../../resources/js/utils/journeyCommandFeedback.js'
const Tiles = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyContainerQuantities.vue',import.meta.url))
const Movements = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyContainerMovements.vue',import.meta.url))
const Feedback = await loadVueComponent(new URL('../../resources/js/Components/UI/AppNotificationStack.vue',import.meta.url))
const box = (id,count)=>({id,holder_id:1,holder:{name:'Cassa'},holder_quantity:count})

test('pickup and delivery groups use the same totals preserving actual zero and unknown quantities',()=>{
    assert.equal(describeContainers([box(1,2),box(2,3)]).groups[0].quantity,5)
    assert.equal(describeDeliveryContainers([{id:1,holder_id:1,holder:{name:'Cassa'},total_holders_count:4,actual_delivered:0}],true).groups[0].quantity,0)
    assert.equal(describeContainers([box(1,6)],[],true).groups[0].missing,true)
})
test('tiles show number and container type on separate centred rows without truncating real totals',async()=>{
    const html=await renderToString(createSSRApp(Tiles,{groups:describeContainers([box(1,123)]).groups}))
    assert.match(html,/flex-wrap/)
    assert.match(html,/items-center justify-center/)
    assert.match(html,/<dt[^>]*order-2[^>]*>Cassa<\/dt>/)
    assert.match(html,/<dd[^>]*order-1[^>]*>123<\/dd>/)
})
test('trip uses identical tile markup for independent pickup and delivery',async()=>{
    const order={id:1,items:[box(1,2),box(2,3)],holders:[{id:1,holder_id:1,holder:{name:'Cassa'},total_holders_count:4,actual_delivered:0}]}
    const html=await renderToString(createSSRApp(Movements,{orders:[order]}))
    assert.equal((html.match(/<dl /g)||[]).length,2)
    assert.match(html,/>5<\/dd>/)
    assert.match(html,/>4<\/dd>/)
    assert.match(html,/Contenitori da ritirare/)
    assert.match(html,/Contenitori da consegnare/)
})
test('unregistered actual and bulk material display no fabricated container count',async()=>{
    const html=await renderToString(createSSRApp(Tiles,{groups:describeContainers([box(1,6)],[],true).groups,completed:true}))
    assert.match(html,/non registrata/)
    assert.match(html,/>—<\/dd>/)
    const bulk=await renderToString(createSSRApp(Tiles,{groups:describeContainers([{...box(1,6),is_bulk:true}]).groups}))
    assert.match(bulk,/>Sfuso<\/dt>/)
    assert.match(bulk,/>—<\/dd>/)
})
const journey=items=>({stops:[{orders:[{execution_order_id:7,items}]}]})
test('material notifications identify the actual CER with its leading zero',()=>{
    const previous=journey([{id:1,cer_code:{code:'170405'}}])
    const current=journey([{id:1,cer_code:{code:'170405'}},{id:2,cer_code:{code:'020107'}}])
    assert.equal(materialCommandMessage('add_item',{execution_order_id:7},current,previous),'Materiale CER 020107 aggiunto correttamente all’ordine.')
    assert.equal(materialCommandMessage('update_item',{item_id:2},current,previous),'Materiale CER 020107 modificato correttamente.')
    assert.equal(materialCommandMessage('add_item',{execution_order_id:7},{stops:[]},previous),'Materiale aggiunto correttamente all’ordine.')
})
test('notification preserves the previous global alert and circular dismiss button',async()=>{
    const html=await renderToString(createSSRApp(Feedback,{messages:[{id:1,type:'success',text:'Materiale modificato correttamente.'}]}))
    assert.doesNotMatch(html,/bottom:/)
    assert.match(html,/btn-circle btn-outline/)
    assert.match(html,/Chiudi messaggio/)
})


test('residual stock uses the same tiles alongside pickups and deliveries',async()=>{
    const balance={groups:[{key:'holder:1',type:'Cassa',quantity:8,missing:false,bulk:false}],has_shortage:false}
    const html=await renderToString(createSSRApp(Movements,{orders:[],balance}))
    assert.match(html,/Contenitori residui/)
    assert.match(html,/>8<\/dd>/)
    assert.doesNotMatch(html,/Le consegne superano/)
})
