import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
import {countValue,stepCount} from '../../resources/js/utils/quantityCounter.js'
import {changeSplit} from '../../resources/js/utils/journeyItemLoad.js'
const Counter = await loadVueComponent(new URL('../../resources/js/Components/UI/QuantityCounter.vue',import.meta.url))

test('counter distinguishes unrecorded from zero and supports integer entry and limits',()=>{
    assert.equal(countValue(''),null)
    assert.equal(countValue(null),null)
    assert.equal(countValue(0),0)
    assert.equal(countValue('1.5'),null)
    assert.equal(stepCount(null,1),1)
    assert.equal(stepCount(0,-1),0)
    assert.equal(stepCount(5,1,0,5),5)
    assert.equal(countValue(999,0,5),5)
    assert.equal(stepCount(5,-1,0,4),4)
    assert.deepEqual(changeSplit({vehicle:3,trailer:2},'vehicle',stepCount(3,1,0,5),5),{vehicle:4,trailer:1})
})
test('touch controls provide named buttons, numeric keyboard and correct disabled limits',async()=>{
    const zero=await renderToString(createSSRApp(Counter,{modelValue:0,label:'Contenitori',max:5}))
    assert.match(zero,/h-\[42px\]/)
    assert.match(zero,/<button[^>]*disabled[^>]*aria-label="Diminuisci contenitori"/)
    assert.match(zero,/inputmode="numeric"/)
    const max=await renderToString(createSSRApp(Counter,{modelValue:5,label:'Contenitori',max:5}))
    assert.match(max,/<button[^>]*disabled[^>]*aria-label="Aumenta contenitori"/)
    const disabled=await renderToString(createSSRApp(Counter,{modelValue:2,label:'Contenitori',disabled:true}))
    assert.equal((disabled.match(/ disabled/g)||[]).length,3)
})
test('existing allocations remain visible even when a new total requires reconciliation',async()=>{
    const html=await renderToString(createSSRApp(Counter,{modelValue:5,label:'Contenitori in motrice',max:4}))
    assert.match(html,/value="5"/)
    assert.match(html,/max="4"/)
    const empty=await renderToString(createSSRApp(Counter,{modelValue:null,label:'Consegnati',placeholder:'—'}))
    assert.doesNotMatch(empty,/value="0"/)
})
