import test from 'node:test'
import assert from 'node:assert/strict'
import {compareDraft,draftChanges,validDraftRecord,DRAFT_TTL_MS} from '../../resources/js/utils/localDraftRules.js'
const record={schema:1,ownerId:'1',scope:'journey:41',baseline:{quantity:5,notes:'Prima'},changes:{quantity:6},savedAt:100000}
test('unrelated server edits do not prevent recovery; numeric input types match',()=>{
    assert.deepEqual(compareDraft(record,{quantity:'5',notes:'Modificata da logistica'}),{changes:{quantity:6},conflicts:[],alreadySaved:false})
    assert.deepEqual(draftChanges({quantity:'5',notes:null},{quantity:5,notes:''}),{})
})
test('a server edit of the same field is a conflict and cannot be silently merged',()=>{
    const comparison=compareDraft(record,{quantity:4,notes:'Prima'})
    assert.deepEqual(comparison.changes,{})
    assert.deepEqual(comparison.conflicts,[{field:'quantity',baseline:5,current:4,local:6}])
    assert.equal(compareDraft(record,{quantity:6,notes:'Prima'}).alreadySaved,true)
})
test('owner, scope, schema and 24h TTL are mandatory; zero is not missing',()=>{
    assert.ok(validDraftRecord(record,1,'journey:41',100001))
    assert.equal(validDraftRecord(record,2,'journey:41',100001),false)
    assert.equal(validDraftRecord(record,1,'journey:48',100001),false)
    assert.equal(validDraftRecord(record,1,'journey:41',100000+DRAFT_TTL_MS),false)
    assert.deepEqual(draftChanges({quantity:0},{quantity:null}),{quantity:0})
})
