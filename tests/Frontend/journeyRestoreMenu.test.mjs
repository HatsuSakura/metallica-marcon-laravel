import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
const component=await loadVueComponent(new URL('../../resources/js/Components/Journey/JourneyRestoreMenu.vue',import.meta.url))
const preview={current:{label:'4',value:4},previous:{available:true,value:6,label:'6'},original:{available:true,exists:true,value:6,label:'6'},same_targets:true}

test('mobile restore menu renders target values and just one coincident action', async () => {
    const html=await renderToString(createSSRApp(component,{itemId:1,fields:[{value:'holder_quantity',label:'Numero'}],options:{holder_quantity:preview}}))
    assert.match(html,/Valore salvato attuale:/)
    assert.match(html,/Precedente e originale alla partenza:/)
    assert.equal((html.match(/Ripristina valore precedente/g)||[]).length,1)
    assert.doesNotMatch(html,/Ripristina originale alla partenza/)
})
test('unsaved inputs disable restore rather than discarding the driver draft', async () => {
    const html=await renderToString(createSSRApp(component,{itemId:1,dirty:true,fields:[{value:'holder_quantity',label:'Numero'}],options:{holder_quantity:preview}}))
    assert.match(html,/salva o scarta/)
    assert.match(html,/<button[^>]*disabled/)
})
