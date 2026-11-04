import test from 'node:test'
import assert from 'node:assert/strict'
import {restoreActions} from '../../resources/js/utils/journeyRestore.js'
import {mergeSavedDraft, hasUnsavedDraft, changedDraftValues} from '../../resources/js/utils/journeyExecutionDrafts.js'

test('coincident previous/original targets expose a single operation that advances undo history', () => {
    assert.deepEqual(restoreActions({original:{available:true},previous:{available:true},same_targets:true}).map(a=>a.operation), ['restore_previous'])
    assert.equal(restoreActions({original:{available:true},previous:{available:true},same_targets:false}).length, 2)
})
test('added items offer only previous values and unavailable actions never appear', () => {
    assert.deepEqual(restoreActions({original:{available:false},previous:{available:true}}).map(a=>a.operation), ['restore_previous'])
    assert.deepEqual(restoreActions({original:{available:false},previous:{available:false}}), [])
    assert.deepEqual(restoreActions(null), [])
})
test('refreshing another material preserves dirty inputs while adopting saved changes', () => {
    const previous={description:'Ferro',weight_declared:'100.000',holder_quantity:6}
    const draft={description:'Nota non salvata',weight_declared:100,holder_quantity:6}
    const next={description:'Ferro',weight_declared:'100.000',holder_quantity:4}
    assert.deepEqual(mergeSavedDraft(draft,previous,next),{description:'Nota non salvata',weight_declared:'100.000',holder_quantity:4})
    assert.equal(hasUnsavedDraft(draft,previous),true)
    assert.equal(hasUnsavedDraft({...previous,weight_declared:100},previous),false)
    assert.equal(hasUnsavedDraft({actual_containers:0},{actual_containers:null}),true)
})
test('initial load and a successful save result in clean drafts', () => {
    assert.deepEqual(mergeSavedDraft(undefined,undefined,{description:'Ferro'}),{description:'Ferro'})
    assert.deepEqual(mergeSavedDraft({cargo_location:'vehicle'},undefined,{cargo_location:'trailer'}),{cargo_location:'trailer'})
    const saved=mergeSavedDraft({description:'Modificato'},{description:'Ferro'},{description:'Modificato'})
    assert.equal(hasUnsavedDraft(saved,{description:'Modificato'}),false)
})

test('material updates send only edited fields without introducing default cargo overrides or clearing actuals', () => {
    const saved={cer_code_id:1,holder_quantity:3,weight_declared:'900.000',description:'',cargo_location:'trailer',actual_containers:null}
    assert.deepEqual(changedDraftValues({...saved,description:'Descrizione aggiornata',weight_declared:900},saved),{description:'Descrizione aggiornata'})
    assert.deepEqual(changedDraftValues({...saved,actual_containers:0},saved),{actual_containers:0})
})
