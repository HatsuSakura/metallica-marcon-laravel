import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString as renderRaw} from '@vue/server-renderer'
const renderToString = async app => (await renderRaw(app)).replace(/<!--[\s\S]*?-->/g,'')
import {loadVueComponent} from './support/loadVueComponent.mjs'
const Editor = await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyExecutionOrderEditor.vue', import.meta.url))
const Badge = await loadVueComponent(new URL('../../resources/js/Components/UI/CerCodeBadge.vue', import.meta.url))
const Actions = await loadVueComponent(new URL('../../resources/js/Pages/Driver/Journey/Components/JourneyStopOutcomeActions.vue', import.meta.url))
const Disclosure = await loadVueComponent(new URL('../../resources/js/Components/UI/DisclosureCard.vue', import.meta.url))

test('removed material has visible restore and hide actions without expanding a menu', async () => {
    const html = await renderToString(createSSRApp(Editor, {order:{execution_order_id:1,items:[],holders:[],removed_items:[{id:7,cer_code:'020107',description:'Materiale rimosso'}]},lookups:{cers:[],holders:[]}}))
    assert.match(html,/CER 020107/)
    assert.match(html,/>Ripristina<\/button>/)
    assert.match(html,/>Elimina definitivamente<\/button>/)
    assert.doesNotMatch(html,/<summary[^>]*>Materiali rimossi/)
})
test('hazard styling is based on CER danger independently of ADR', async () => {
    const dangerous = await renderToString(createSSRApp(Badge,{code:'020108',dangerous:true}))
    assert.match(dangerous,/badge-error/)
    assert.match(dangerous,/Pericoloso/)
    const normal = await renderToString(createSSRApp(Badge,{code:'020107',dangerous:false}))
    assert.doesNotMatch(normal,/badge-error|Pericoloso/)
})
test('shared stop controls provide both operations and unique accessible reason fields', async () => {
    for(const idPrefix of ['card-outcome','detail-outcome']) {
        const html = await renderToString(createSSRApp(Actions,{idPrefix,busy:true,skipReasons:[{value:'other',label:'Altro'}],skipPanelOpen:true,skipForm:{reason_code:'other',driver_notes:''}}))
        assert.match(html,/>Completa<\/button>/)
        assert.match(html,/>Annulla salto<\/button>/)
        assert.ok(html.includes(`for="${idPrefix}-reason"`))
        assert.ok(html.includes(`id="${idPrefix}-notes"`))
        assert.equal((html.match(/<button[^>]*disabled/g)||[]).length,3)
    }
})
test('header disclosure respects the initial per journey opening flag', async () => {
    const open = await renderToString(createSSRApp(Disclosure,{open:true}))
    const closed = await renderToString(createSSRApp(Disclosure,{open:false}))
    assert.match(open,/<details[^>]*\sopen(?:\s|>)/)
    assert.doesNotMatch(closed,/<details[^>]*\sopen(?:\s|>)/)
})
