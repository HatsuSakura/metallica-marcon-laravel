import {computed,reactive,ref,watch,onBeforeUnmount,inject} from 'vue'
import {localDraftStorage as storage} from './localDraftStorage'
import {compareDraft,draftChanges,draftEqual,validDraftRecord} from '@/utils/localDraftRules'
import {setDraftFlusher,sessionState} from './sessionRecovery'

export const LOCAL_DRAFT_CONTEXT=Symbol('local-draft-context')
const tabId=globalThis.crypto?.randomUUID?.() || String(Date.now())+'-'+Math.random().toString(36).slice(2)
export const draftStorageState=reactive({error:''})
const offers=reactive(new Map()), bindings=new Map(), writers=new Map(), version=ref(0)
let sequence=0,writeChain=Promise.resolve()
const clone=value=>JSON.parse(JSON.stringify(value))
const failure=error=>{draftStorageState.error=error?.message?.includes('bozza è cambiata')?'La bozza è cambiata in un’altra scheda. Rileggi i dati prima di recuperarla o scartarla.':'Il recupero locale non è disponibile in questo browser. Salva le modifiche sul server prima di lasciare la pagina; in caso di scadenza potrebbero andare perse.'}
const enqueue=task=>{
    const next=writeChain.catch(()=>{}).then(task)
    writeChain=next
    next.catch(failure)
    return next
}
const resourceKey=(owner,scope,key)=>`${owner}|${scope}|${key}`
const idFor=b=>resourceKey(b.owner,b.scope,b.key)+'|'+tabId
const label=b=>typeof b.label==='function'?b.label():b.label
const evaluate=b=>clone(b.baseline())
const eligible=b=>sessionState.ownerId!=null && String(sessionState.ownerId)===b.owner && b.canRestore()
const matching=record=>[...(bindings.get(resourceKey(record.ownerId,record.scope,record.resource))?.values() || [])]
const target=record=>matching(record).findLast(eligible) || matching(record).at(-1)
export const draftOffers=computed(()=>{
    void version.value
    return [...offers.values()].filter(record=>String(record.ownerId)===String(sessionState.ownerId) && matching(record).length).map(record=>{
        const b=target(record), comparison=compareDraft(record,evaluate(b))
        return {record,label:record.label,status:!eligible(b)?'blocked':comparison.conflicts.length?'conflict':'ready',comparison,fields:b.fields || {}}
    })
})
export const unsavedDrafts=computed(()=>{
    void version.value
    return [...bindings.values()].map(group=>[...group.values()].findLast(b=>String(sessionState.ownerId)===b.owner && Object.keys(draftChanges(b.value(),b.baseline())).length)).filter(Boolean).map(b=>({label:label(b),fields:b.fields || {},changes:draftChanges(b.value(),b.baseline())}))
})
const load=async b=>{
    try {
        const records=await storage.list(b.owner,b.scope)
        for(const record of records) {
            if(!validDraftRecord(record,b.owner,b.scope)) {await storage.remove(record.id,record.writeVersion ?? record.savedAt);continue}
            if(record.resource!==b.key)continue
            if(matching(record).some(binding=>Object.keys(draftChanges(binding.value(),binding.baseline())).length))continue
            if(compareDraft(record,evaluate(b)).alreadySaved){await storage.remove(record.id,record.writeVersion ?? record.savedAt);continue}
            offers.set(record.id,record)
        }
    } catch(error) {failure(error)}
}
const capture=async b=>{
    const baseline=b.editBaseline || evaluate(b),changes=draftChanges(clone(b.value()),evaluate(b))
    const writerKey=resourceKey(b.owner,b.scope,b.key)
    if(writers.has(writerKey) && writers.get(writerKey)!==b.id)return
    if(!Object.keys(changes).length){await storage.remove(idFor(b));return}
    // Never silently claim to have stored a truncated form.
    const record={schema:1,id:idFor(b),ownerId:b.owner,scope:b.scope,resource:b.key,tabId,label:label(b),baseline:clone(baseline),changes,savedAt:b.lastEditedAt || Date.now(),writeVersion:`${tabId}:${++sequence}`,context:clone(b.startedContext || b.context?.() || {})}
    if(JSON.stringify(record).length>64*1024)throw new Error('Bozza troppo grande')
    await storage.put(record)
    if(compareDraft(record,evaluate(b)).conflicts.length)offers.set(record.id,record)
}
const flushers=new Set()
export const flushLocalDrafts=async()=>{
    for(const b of flushers) {if(b.timer){clearTimeout(b.timer);b.timer=null;enqueue(()=>capture(b))}}
    await writeChain
}
setDraftFlusher(flushLocalDrafts)
export const discardLocalDraft=async offer=>{
    try {await enqueue(()=>storage.remove(offer.record.id,offer.record.writeVersion ?? offer.record.savedAt));offers.delete(offer.record.id);const b=target(offer.record);if(b && offer.record.id===idFor(b))b.restore(evaluate(b))}catch(error){failure(error)}
}
export const recoverLocalDraft=async offer=>{
    if(sessionState.mode!=='ready')return
    const b=target(offer.record)
    if(!b || !eligible(b))return
    const comparison=compareDraft(offer.record,evaluate(b))
    if(comparison.conflicts.length)return
    try {
        await enqueue(()=>storage.remove(offer.record.id,offer.record.writeVersion ?? offer.record.savedAt))
        offers.delete(offer.record.id)
        b.restore({...evaluate(b),...clone(comparison.changes)})
    } catch(error) {failure(error)}
}
export const clearSavedDraftResource=async(owner,scope,key)=>{
    const id=resourceKey(String(owner),scope,key)+'|'+tabId
    await enqueue(()=>storage.remove(id)).catch(failure)
    offers.delete(id)
}
export const useDraftBindings=(providedContext=null)=>{
    const context=providedContext || inject(LOCAL_DRAFT_CONTEXT,null)
    const own=new Map()
    const bind=(key,options)=>{
        if(!context || own.has(key))return
        const owner=String(context.owner()),scope=context.scope()
        if(!owner || owner==='null' || owner==='undefined')return
        const b={...options,key,owner,scope,context:()=>context.metadata?.() || {},canRestore:()=>context.editable() && (options.canRestore?.() ?? true),timer:null,id:++sequence}
        const group=resourceKey(owner,scope,key)
        if(!bindings.has(group))bindings.set(group,new Map())
        bindings.get(group).set(b.id,b);own.set(key,b);flushers.add(b);version.value++
        b.editBaseline=evaluate(b);b.lastServer=evaluate(b);b.lastValue=clone(b.value())
        let initializing=true
        b.stop=watch([()=>clone(b.value()),()=>clone(b.baseline())],()=>{
            const current=clone(b.value()),server=evaluate(b)
            for(const [key,value] of Object.entries(server)) {
                const wasDirty=!draftEqual(b.lastValue[key],b.lastServer[key])
                if(!wasDirty || draftEqual(current[key],value))b.editBaseline[key]=value
                if(!draftEqual(current[key],b.lastValue[key]) && !draftEqual(current[key],value)){b.lastEditedAt=Date.now();writers.set(group,b.id)}
            }
            if(!b.startedContext && Object.keys(draftChanges(current,server)).length)b.startedContext=clone(b.context())
            if(!Object.keys(draftChanges(current,server)).length)b.startedContext=null
            b.lastServer=server;b.lastValue=current
            if(initializing){b.changedWhileLoading=true;return}
            for(const [id,record] of offers)if(record.resource===b.key && record.scope===b.scope && record.ownerId===b.owner && compareDraft(record,evaluate(b)).alreadySaved){offers.delete(id);if(id!==idFor(b))enqueue(()=>storage.remove(id,record.writeVersion ?? record.savedAt).catch(()=>{}))}
            clearTimeout(b.timer)
            b.timer=setTimeout(()=>{b.timer=null;enqueue(()=>capture(b))},250)
        },{deep:true,flush:'sync'})
        void load(b).finally(()=>{initializing=false;if(b.changedWhileLoading)enqueue(()=>capture(b))})
    }
    const clear=async key=>{
        const b=own.get(key)
        if(!b)return
        clearTimeout(b.timer);b.timer=null
        await enqueue(()=>storage.remove(idFor(b))).catch(failure)
        offers.delete(idFor(b))
    }
    const dispose=()=>{
        for(const b of own.values()){
            if(b.timer){clearTimeout(b.timer);b.timer=null;enqueue(()=>capture(b))}
            b.stop();flushers.delete(b)
            const group=bindings.get(resourceKey(b.owner,b.scope,b.key));group?.delete(b.id)
        }
        own.clear();version.value++
    }
    onBeforeUnmount(dispose)
    return {bind,clear}
}
