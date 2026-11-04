import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
const Tools = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyExecutionTools.vue',import.meta.url))
const Materials = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyOrderMaterials.vue',import.meta.url))
const journey={id:1,status:'attivo',execution_revision:2,stops:[{id:1,status:'in_progress'}]}
const lookups={orders:[],customers:[],sites:[],technicalActions:[]}

test('driver sees operational commands without the original-plan/history panel',async()=>{
    const html=await renderToString(createSSRApp(Tools,{journey,lookups,readOnly:false,canViewHistory:false}))
    assert.doesNotMatch(html,/Modifica viaggio \/ aggiungi tappa|Note viaggio|Salva note/)
    assert.doesNotMatch(html,/Piano originale e storico modifiche/)
    assert.doesNotMatch(html,/Consulta il piano originale/)
})
test('control roles retain history even in read-only viewing',async()=>{
    const html=await renderToString(createSSRApp(Tools,{journey,lookups,readOnly:true,canViewHistory:true}))
    assert.match(html,/Piano originale e storico modifiche/)
})
test('original-order comparison follows the same explicit permission',async()=>{
    const order={items:[],holders:[],original_plan:{items:[],holders:[]}}
    const driver=await renderToString(createSSRApp(Materials,{order}))
    const control=await renderToString(createSSRApp(Materials,{order,showOriginalPlan:true}))
    assert.doesNotMatch(driver,/Piano alla partenza/)
    assert.match(control,/Piano alla partenza/)
})
