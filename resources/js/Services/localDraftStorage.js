const DB_NAME='metallica-marcon-local-drafts'
let opening
const open=()=>{
    if(!globalThis.indexedDB)return Promise.reject(new Error('IndexedDB non disponibile'))
    if(!opening)opening=new Promise((resolve,reject)=>{
        const req=indexedDB.open(DB_NAME,1)
        req.onupgradeneeded=()=>{
            const store=req.result.createObjectStore('drafts',{keyPath:'id'})
            store.createIndex('ownerScope',['ownerId','scope'])
        }
        req.onsuccess=()=>{req.result.onversionchange=()=>{req.result.close();opening=null};resolve(req.result)}
        req.onerror=()=>{opening=null;reject(req.error)}
        req.onblocked=()=>{opening=null;reject(new Error('Archivio bozze bloccato da un’altra scheda'))}
    })
    return opening
}
const transaction=async(mode,work)=>{
    const db=await open()
    return new Promise((resolve,reject)=>{
        const tx=db.transaction('drafts',mode),store=tx.objectStore('drafts')
        let result
        tx.oncomplete=()=>resolve(result)
        tx.onerror=()=>reject(tx.error || new Error('Archivio bozze non disponibile'))
        tx.onabort=()=>reject(tx.error || new Error('La bozza è cambiata in un’altra scheda: rileggila.'))
        work(store,tx,value=>{result=value})
    })
}
export const localDraftStorage={
    put:record=>transaction('readwrite',store=>store.put(JSON.parse(JSON.stringify(record)))),
    list:(owner,scope)=>transaction('readonly',(store,tx,done)=>{const req=store.index('ownerScope').getAll(IDBKeyRange.only([String(owner),scope]));req.onsuccess=()=>done(req.result)}),
    remove:(id,expectedVersion)=>transaction('readwrite',(store,tx)=>{
        if(expectedVersion===undefined){store.delete(id);return}
        const req=store.get(id)
        req.onsuccess=()=>{if(req.result && (typeof expectedVersion==='string'?req.result.writeVersion:req.result.savedAt)!==expectedVersion)tx.abort();else store.delete(id)}
    }),
}
