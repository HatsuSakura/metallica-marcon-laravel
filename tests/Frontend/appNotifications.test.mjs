import test from 'node:test'
import assert from 'node:assert/strict'
import {createStore} from 'vuex'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {readFile} from 'node:fs/promises'
import {createFlashModule} from '../../resources/js/store/modules/flash.js'
import {loadVueComponent} from './support/loadVueComponent.mjs'
const Stack = await loadVueComponent(new URL('../../resources/js/Components/UI/AppNotificationStack.vue',import.meta.url))
function fixture() {
    let time=0, sequence=0
    const tasks=new Map()
    const store=createStore({modules:{flash:createFlashModule({schedule:(run,delay)=>{const id=++sequence;tasks.set(id,{at:time+delay,run});return id},cancel:id=>tasks.delete(id)})}})
    const advance=async ms=>{
        const target=time+ms
        while(true){const next=[...tasks].filter(([,t])=>t.at<=target).sort((a,b)=>a[1].at-b[1].at)[0];if(!next)break;time=next[1].at;tasks.delete(next[0]);await next[1].run()}
        time=target
    }
    return {store,advance,messages:()=>store.state.flash.messageQueue,send:message=>store.dispatch('flash/queueMessage',message)}
}
test('legacy dispatch accepts all four tones and retains identical messages in insertion order',async()=>{
    const f=fixture()
    for(const type of ['success','info','warning','error']) await f.send({type,text:type})
    await f.send({type:'success',text:'Carico aggiornato'})
    await f.send({type:'success',text:'Carico aggiornato'})
    assert.equal(f.messages().length,6)
    assert.equal(new Set(f.messages().map(m=>m.id)).size,6)
    assert.deepEqual(f.messages().map(m=>m.type),['success','info','warning','error','success','success'])
    await f.advance(3000)
    assert.deepEqual(f.messages().map(m=>m.type),['error'])
})
test('each message expires three seconds after its own arrival; manual dismissal cannot expire its neighbour',async()=>{
    const f=fixture()
    await f.send({type:'success',text:'first'})
    await f.advance(1000)
    await f.send({type:'success',text:'second'})
    await f.store.dispatch('flash/removeMessageByIndex',0)
    await f.advance(2000)
    assert.deepEqual(f.messages().map(m=>m.text),['second'])
    await f.advance(1000)
    assert.equal(f.messages().length,0)
})
test('simultaneous burst has independent timers without serial 55-second backlog',async()=>{
    const f=fixture()
    for(let i=0;i<10;i++)await f.send({type:'success',text:'Carico aggiornato'})
    await f.advance(2999);assert.equal(f.messages().length,10)
    await f.advance(1);assert.equal(f.messages().length,0)
})
test('legacy add action and index dismiss remain supported, including stale indices',async()=>{
    const f=fixture()
    await f.store.dispatch('flash/addMessageToQueue',{type:'info',text:'legacy'})
    await f.store.dispatch('flash/removeMessageByIndex',99)
    assert.equal(f.messages().length,1)
    await f.store.dispatch('flash/removeMessageByIndex',0)
    await f.advance(9000)
    assert.equal(f.messages().length,0)
})
test('undo remains available without expiry but never runs after invalidation',async()=>{
    const f=fixture();let valid=true,count=0
    const id=await f.send({type:'success',text:'Ripristinato',action:{label:'Annulla ripristino',disabled:()=>!valid,run:()=>{count++}}})
    await f.advance(20000);assert.equal(f.messages().length,1)
    valid=false;await f.store.dispatch('flash/runAction',id);assert.equal(count,0)
    valid=true;await f.store.dispatch('flash/runAction',id);assert.equal(count,1);assert.equal(f.messages().length,0)
})
test('independent store instances do not share notification queues',async()=>{
    const a=fixture(),b=fixture();await a.send({type:'info',text:'one'});assert.equal(b.messages().length,0)
})
test('shared rendering preserves legacy DaisyUI graphics, FIFO DOM order and separate duplicate messages',async()=>{
    const messages=['success','error','warning','info'].map((type,id)=>({id,type,text:'Messaggio '+id}))
    messages.push({id:5,type:'success',text:'Carico aggiornato'},{id:6,type:'success',text:'Carico aggiornato'})
    const html=await renderToString(createSSRApp(Stack,{messages}))
    for(const type of ['success','error','warning','info'])assert.ok(html.includes('alert-'+type))
    assert.match(html,/mt-8 space-y-4 container w-full mx-auto/)
    assert.match(html,/btn btn-circle btn-outline/)
    assert.equal((html.match(/Carico aggiornato/g)||[]).length,2)
    assert.ok(html.indexOf('Messaggio 0')<html.indexOf('Messaggio 1'))
    assert.equal((html.match(/aria-label="Chiudi messaggio"/g)||[]).length,6)
})
test('single host lives at application root and layout no longer duplicates server flash or queue',async()=>{
    const root=await readFile(new URL('../../resources/js/app.js',import.meta.url),'utf8')
    const layout=await readFile(new URL('../../resources/js/Layouts/MainLayout.vue',import.meta.url),'utf8')
    assert.match(root,/h\(AppNotifications\)/)
    assert.doesNotMatch(layout,/messageQueue|flash\/queueMessage/)
})
test('failed undo keeps its action notification; stale callbacks cannot run after dismissal',async()=>{
    const f=fixture();let attempts=0
    const id=await f.send({type:'success',text:'Ripristinato',action:{label:'Annulla',run:()=>{attempts++;return false}}})
    await f.store.dispatch('flash/runAction',id)
    assert.equal(f.messages().length,1)
    await f.store.dispatch('flash/removeMessage',id)
    await f.store.dispatch('flash/runAction',id)
    assert.equal(attempts,1)
})
test('action rendering reflects invalidation and preserves persistent errors with manual dismissal',async()=>{
    const html=await renderToString(createSSRApp(Stack,{messages:[{id:1,type:'success',text:'Ripristinato',action:{label:'Annulla ripristino',disabled:()=>true}},{id:2,type:'error',text:'Salvataggio non riuscito'}]}))
    assert.match(html,/<button[^>]*disabled[^>]*>Annulla ripristino/)
    assert.match(html,/role="alert"/)
    assert.match(html,/Salvataggio non riuscito/)
})
