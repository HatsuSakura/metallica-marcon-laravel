import test from 'node:test'
import assert from 'node:assert/strict'
import {createSSRApp, h} from 'vue'
import {renderToString} from '@vue/server-renderer'
import {loadVueComponent} from './support/loadVueComponent.mjs'
import {registrySnapshot} from '../../resources/js/utils/registryFormState.js'
const Form = await loadVueComponent(new URL('../../resources/js/Components/Registry/RegistryForm.vue', import.meta.url))
const render = (editing, processing = false, errors = {}) => renderToString(createSSRApp({render: () => h(Form, {editing, form:{data:()=>({name:'Esempio'}), processing, errors}}, {default:({canSubmit})=>h('button',{type:'submit', disabled:!canSubmit},'Salva')})}))
test('existing records cannot be saved unchanged while new records remain submittable', async () => {
    assert.match(await render(true), /<button[^>]*disabled/)
    assert.doesNotMatch(await render(false), /<button[^>]*disabled/)
    const pending = await render(false, true)
    assert.match(pending, /<fieldset[^>]*disabled/)
    assert.match(pending, /aria-busy="true"/)
})
test('validation retains an accessible summary alongside the supplied fields', async () => {
    const html = await render(true, false, {name:'Nome obbligatorio'})
    assert.match(html, /tabindex="-1" role="alert"/)
    assert.match(html, /Nome obbligatorio/)
})
test('dirty comparison matches displayed optional and numeric values, preserving identifiers and zero', () => {
    assert.equal(registrySnapshot({description:null, load_capacity:'11000.00'}), registrySnapshot({load_capacity:11000, description:''}))
    assert.notEqual(registrySnapshot({volume:0}), registrySnapshot({volume:null}))
    assert.notEqual(registrySnapshot({vat_number:'00123'}), registrySnapshot({vat_number:'123'}))
    assert.notEqual(registrySnapshot({name:'A'}), registrySnapshot({name:'B'}))
})
test('date values retain their meaning in the shared form dirty comparison',()=>{
 assert.notEqual(registrySnapshot({requested_at:new Date('2026-11-16T08:00:00Z')}),registrySnapshot({requested_at:new Date('2026-11-16T09:00:00Z')}))
 assert.equal(registrySnapshot({requested_at:new Date('2026-11-16T08:00:00Z')}),registrySnapshot({requested_at:new Date('2026-11-16T08:00:00Z')}))
})
