import test from 'node:test'
import assert from 'node:assert/strict'
import {readFile} from 'node:fs/promises'
import {parse,compileScript} from '@vue/compiler-sfc'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'

const path = new URL('../../resources/js/Components/Journey/JourneyRestoreMenu.vue',import.meta.url)
const {descriptor}=parse(await readFile(path,'utf8'))
const script=compileScript(descriptor,{id:'journey-restore-test',inlineTemplate:true})
const source=script.content.replace("'@/utils/journeyRestore'",JSON.stringify(new URL('../../resources/js/utils/journeyRestore.js',import.meta.url).href))
// Data URLs cannot resolve the bare Vue package import; use its installed file URL.
const compiled=source.replaceAll('from "vue"',`from ${JSON.stringify(import.meta.resolve('vue'))}`).replaceAll("from 'vue'",`from ${JSON.stringify(import.meta.resolve('vue'))}`)
const component=(await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`)).default
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
