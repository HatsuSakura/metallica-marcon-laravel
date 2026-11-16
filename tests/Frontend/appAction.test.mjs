import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
const Action=await loadVueComponent(new URL('../../resources/js/Components/UI/AppAction.vue',import.meta.url))
const render=async props=>renderToString(createSSRApp(Action,props))
test('opening a function and confirming it have distinct styling without implicit form submission',async()=>{
 const open=await render({variant:'function','aria-label':'Modifica materiale'})
 assert.match(open,/<button[^>]*type="button"/)
 assert.match(open,/btn-primary btn-outline/)
 const save=await render({variant:'confirm',type:'submit',form:'materiale'})
 assert.match(save,/<button[^>]*type="submit"/)
 assert.match(save,/form="materiale"/)
 assert.match(save,/btn-success/)
 assert.doesNotMatch(save,/btn-outline/)
})
test('busy actions are disabled and expose loading; icon actions retain their accessible name',async()=>{
 const busy=await render({variant:'confirm',busy:true,shape:'circle','aria-label':'Salva materiale'})
 assert.match(busy,/disabled/)
 assert.match(busy,/aria-busy="true"/)
 assert.match(busy,/aria-label="Salva materiale"/)
 assert.match(busy,/loading-spinner/)
 assert.match(busy,/btn-circle/)
})
test('link actions keep native link semantics and do not acquire a button type',async()=>{
 const link=await render({as:'a',href:'#viaggi','aria-label':'Torna ai viaggi'})
 assert.match(link,/<a /)
 assert.match(link,/href="#viaggi"/)
 assert.doesNotMatch(link,/type="button"/)
})

test('discard and delete use red with a distinct outline/filled treatment',async()=>{
 const cancel=await render({variant:'cancel','aria-label':'Annulla modifiche'})
 assert.match(cancel,/btn-error btn-outline/)
 const remove=await render({variant:'danger','aria-label':'Conferma eliminazione'})
 assert.match(remove,/btn-error/)
 assert.doesNotMatch(remove,/btn-outline/)
})
