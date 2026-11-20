import {savedValueEquals} from './journeyExecutionDrafts.js'
export const DRAFT_TTL_MS = 24 * 60 * 60 * 1000
export const draftEqual = (a,b) => savedValueEquals(a,b) || (!!a && !!b && typeof a==='object' && typeof b==='object' && JSON.stringify(a)===JSON.stringify(b))
export const draftChanges = (value={},baseline={}) => Object.fromEntries(Object.entries(value).filter(([key,v])=>!draftEqual(v,baseline[key])))
export const compareDraft = (record,current={}) => {
    const changes={}, conflicts=[]
    for(const [field,local] of Object.entries(record.changes || {})) {
        if(draftEqual(local,current[field])) continue
        if(!Object.hasOwn(current,field) || !draftEqual(record.baseline[field],current[field])) conflicts.push({field,baseline:record.baseline[field],current:current[field],local})
        else changes[field]=local
    }
    return {changes,conflicts,alreadySaved:!Object.keys(changes).length&&!conflicts.length}
}
export const validDraftRecord = (record,owner,scope,now=Date.now()) => record?.schema===1 && String(record.ownerId)===String(owner) && record.scope===scope && Number.isFinite(record.savedAt) && now-record.savedAt<DRAFT_TTL_MS && record.savedAt<=now+60_000 && record.baseline && record.changes
