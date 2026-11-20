import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {draftInput} from '../../resources/js/utils/executionDraft.js'
import {notificationValue,notificationDate} from '../../resources/js/utils/executionNotification.js'
import {loadVueComponent} from './support/loadVueComponent.mjs'

test('temporary execution references never replace catalog IDs',()=>{
    const references={stop:{9:'@a:stop:0'},order:{7:'@a:order:0'},holder:{4:'@b:holder:0'},item:{8:'@c:item:0'}}
    assert.deepEqual(draftInput('add_item',{execution_order_id:7,holder_id:4},references),{execution_order_id:'@a:order:0',holder_id:4})
    assert.deepEqual(draftInput('update_holder',{holder_id:4,actual_delivered:0},references),{holder_id:'@b:holder:0',actual_delivered:0})
    assert.deepEqual(draftInput('reorder',{stop_ids:[1,9]},references),{stop_ids:[1,'@a:stop:0']})
})

test('notification values preserve zero, unknown quantity and booleans',()=>{
    assert.equal(notificationValue(0),'0')
    assert.equal(notificationValue(null),'—')
    assert.equal(notificationValue(false),'No')
    assert.equal(notificationValue(true),'Sì')
    assert.equal(notificationValue('in_progress'),'In corso')
    assert.match(notificationDate('2026-11-17T09:24:00Z'),/10:24/)
})

test('delta table escapes content and distinguishes planned zero from missing actual quantity',async()=>{
    const Changes=await loadVueComponent(new URL('../../resources/js/Pages/Notification/Components/NotificationChanges.vue',import.meta.url))
    const html=await renderToString(createSSRApp(Changes,{beforeLabel:'Pianificato',changes:[{label:'<script>test</script>',field_label:'Contenitori',before:0,after:null}]}))
    assert.ok(html.includes('Pianificato'))
    assert.ok(html.includes('&lt;script&gt;test&lt;/script&gt;'))
    assert.match(html,/>0<\/td>/)
    assert.match(html,/>—<\/td>/)
})

test('driver outcome without changes shows the planned comparison without operational status noise',async()=>{
    const Card=await loadVueComponent(new URL('../../resources/js/Pages/Notification/Components/NotificationCard.vue',import.meta.url))
    const app=createSSRApp(Card,{notification:{id:'n1',type:'journey-execution-update',read_at:'2026-11-17',created_at:'2026-11-17T09:00:00Z',orders:[],data:{message:'Tappa conclusa',event:'complete',summary_basis:'plan',changes:[],baseline_changes:[],previous_outcome_changes:null,stop:{label:'Cliente di prova',values:{reason_code:null}}}}})
    const html=await renderToString(app)
    assert.ok(html.includes('Cliente di prova'))
    assert.equal((html.match(/Nessuna variazione rispetto al pianificato/g)||[]).length,1)
    assert.ok(!html.includes('Stato del viaggio aggiornato'))
})

test('identical planned and actual deltas collapse per item, preserving genuinely different changes',async()=>{
    const {notificationChanges,notificationDelta}=await import('../../resources/js/utils/executionNotification.js')
    const row=(field,before,after,record_key='item:1')=>({record_key,label:'Ordine P1 · CER 020104',execution_order_id:1,field,before,after})
    const rows=notificationChanges([row('holder_quantity',4,6),row('weight_declared',400,600),row('actual_containers',4,6),row('actual_weight_kg',400,'600.000')])
    assert.deepEqual(rows.map(r=>r.field),['actual_containers','actual_weight_kg'])
    assert.equal(notificationValue(rows[1].after),'600')
    assert.equal(notificationDelta(rows[1]),'+200')
    assert.equal(notificationChanges([row('holder_quantity',4,6),row('actual_containers',4,5)]).length,2)
    assert.equal(notificationChanges([row('holder_quantity',4,6,'item:1'),row('actual_containers',4,6,'item:2')]).length,2)
    assert.equal(notificationChanges([row('holder_quantity',4,0),row('actual_containers',4,'0.000')])[0].after,0)
})

test('legacy notification pairs are compacted without losing other fields',async()=>{
    const {notificationChanges}=await import('../../resources/js/utils/executionNotification.js')
    const rows=notificationChanges([{label:'CER x',field:'weight_declared',before:400,after:600},{label:'CER x',field:'actual_weight_kg',before:400,after:'600.000'},{label:'CER x',field:'description',before:'a',after:'b'}])
    assert.deepEqual(rows.map(r=>r.field),['actual_weight_kg','description'])
})

test('notification summary shows each change once and keeps read action with compact accessible links',async()=>{
    const Card=await loadVueComponent(new URL('../../resources/js/Pages/Notification/Components/NotificationCard.vue',import.meta.url))
    const change={label:'CER 020104',field:'actual_containers',field_label:'Contenitori ritirati',before:4,after:6}
    const app=createSSRApp(Card,{notification:{id:'n2',type:'journey-execution-update',read_at:null,created_at:'2026-11-17T09:00:00Z',journey_url:'/journey/1',orders:[{label:'PMM_2603_0008',url:'/order/8'}],data:{journey_id:1,event:'complete',summary_basis:'plan',changes:[change],baseline_changes:[change]}}})
    app.config.globalProperties.route=()=>'/notification/n2/seen'
    const html=await renderToString(app)
    assert.equal((html.match(/Contenitori ritirati/g)||[]).length,1)
    assert.ok(!html.includes('Tutte le variazioni'))
    assert.ok(html.includes('aria-label="Viaggio #1"'))
    assert.ok(html.includes('aria-label="Ordine PMM_2603_0008"'))
    assert.equal((html.match(/Segna come letta/g)||[]).length,1)
    assert.match(html,/ml-auto/)
})


test('comparison disclosure defaults open for unread notifications and closed for read ones',async()=>{
    const Card=await loadVueComponent(new URL('../../resources/js/Pages/Notification/Components/NotificationCard.vue',import.meta.url))
    const notification={id:'n3',type:'journey-execution-update',created_at:'2026-11-17T09:00:00Z',orders:[],data:{event:'complete',summary_basis:'plan',changes:[{label:'CER',field:'actual_containers',field_label:'Contenitori ritirati',before:4,after:6}],baseline_changes:[]}}
    for(const read of [false,true]){
        const app=createSSRApp(Card,{notification:{...notification,read_at:read?'2026-11-20':null}})
        app.config.globalProperties.route=()=>'/seen'
        const html=await renderToString(app)
        assert.equal(/<details[^>]* open(?:>| )/.test(html),!read)
        assert.ok(html.includes('Variazioni rispetto al pianificato'))
    }
})
