import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
import {loadTotal,splitDraft,changeSplit,validSplit} from '../../resources/js/utils/journeyItemLoad.js'
const Checklist = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyItemLoadChecklist.vue',import.meta.url))
const Placement = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyItemLoadPlacement.vue',import.meta.url))

test('split keeps one fixed total, recalculating the opposite carrier in either direction',()=>{
    const draft=splitDraft({holder_quantity:5,cargo_location:'vehicle'})
    assert.deepEqual(draft,{vehicle:5,trailer:0})
    assert.deepEqual(changeSplit(draft,'vehicle','3',5),{vehicle:3,trailer:2})
    assert.deepEqual(changeSplit(draft,'trailer','2',5),{vehicle:3,trailer:2})
    assert.deepEqual(changeSplit(draft,'vehicle','99',5),{vehicle:5,trailer:0})
    assert.deepEqual(changeSplit(draft,'vehicle','-1',5),{vehicle:0,trailer:5})
    assert.equal(validSplit(changeSplit(draft,'vehicle','',5),5),false)
    assert.equal(validSplit(changeSplit(draft,'vehicle','1.5',5),5),false)
    assert.deepEqual(splitDraft({holder_quantity:5,cargo_location:'trailer'}),{vehicle:0,trailer:5})
    assert.deepEqual(splitDraft({holder_quantity:5,cargo_location:null}),{vehicle:5,trailer:0})
})
test('a changed total preserves recorded allocations for explicit reconciliation',()=>{
    const item={holder_quantity:6,actual_containers:7,load_allocations:[{cargo_location:'vehicle',containers:3},{cargo_location:'trailer',containers:2}]}
    assert.equal(loadTotal(item),7)
    assert.deepEqual(splitDraft(item),{vehicle:3,trailer:2})
    assert.equal(validSplit(splitDraft(item),7),false)
    assert.equal(validSplit(changeSplit(splitDraft(item),'vehicle',3,7),7),true)
    assert.equal(loadTotal({actual_containers:0,holder_quantity:5}),0)
    assert.equal(loadTotal({holder_quantity:null}),null)
})
test('the checklist defaults left and highlights loaded only when confirmed',async()=>{
    const pending=await renderToString(createSSRApp(Checklist,{loaded:false,cer:'020107'}))
    assert.match(pending,/Da ritirare/)
    assert.match(pending,/toggle-success/)
    assert.doesNotMatch(pending,/<input[^>]* checked/)
    const loaded=await renderToString(createSSRApp(Checklist,{loaded:true,cer:'020107'}))
    assert.match(loaded,/<input[^>]* checked/)
    assert.match(loaded,/font-bold[^>]*>Caricato/)
})
test('saved splits render two visual quantity rows for the same material',async()=>{
    const item={id:1,holder_quantity:5,holder:{name:'Cassa'},load_allocations:[{cargo_location:'vehicle',containers:3},{cargo_location:'trailer',containers:2}]}
    const html=await renderToString(createSSRApp(Placement,{item,readOnly:true}))
    assert.equal((html.match(/<dl/g)||[]).length,2)
    assert.match(html,/>3<\/dd>/)
    assert.match(html,/>2<\/dd>/)
    assert.match(html,/Motrice/)
    assert.match(html,/Rimorchio/)
    assert.doesNotMatch(html,/<form|<button/)
})
