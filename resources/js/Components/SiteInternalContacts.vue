<template>
    <div class="space-y-3">
        <button v-if="editable" type="button" class="btn btn-primary min-h-12" @click="openEditor()">+ Aggiungi referente</button>
        <p v-if="message" role="status" class="text-sm text-success">{{ message }}</p>
        <div v-if="contacts?.length" class="space-y-2">
            <article v-for="contact in contacts" :key="contact.id" class="flex min-w-0 items-start justify-between gap-3 border-b border-base-300 py-2">
                <div class="flex min-w-0 flex-1 items-center gap-3">
                    <font-awesome-icon :icon="['fas', siteContactRole(contact.role).icon]" class="h-6 w-6 shrink-0" aria-hidden="true" />
                    <div class="min-w-0 space-y-1 text-sm break-words">
                        <p><span class="font-semibold">{{ [contact.name, contact.surname].filter(Boolean).join(' ') }}</span><span class="px-1 opacity-50" aria-hidden="true">|</span><span>{{ roleLabel(contact.role) }}</span></p>
                        <p v-if="contact.mobile || contact.phone || contact.email">{{ contactDetails(contact).join(' | ') }}</p>
                    </div>
                </div>
                <button v-if="editable" type="button" class="btn btn-circle btn-outline min-h-12 min-w-12 shrink-0" :aria-label="`Modifica referente ${contact.name} ${contact.surname || ''}`" @click="openEditor(contact)"><font-awesome-icon :icon="['fas', 'pencil']" aria-hidden="true" /></button>
            </article>
        </div>
        <p v-else class="opacity-70 text-sm">Nessun referente interno registrato.</p>
        <dialog v-if="editable" ref="dialog" class="modal" :aria-labelledby="`${idPrefix}-heading`" @cancel="busy && $event.preventDefault()">
            <div class="modal-box w-full max-w-lg">
                <form class="space-y-3" @submit.prevent="save">
                    <div class="flex items-center justify-between gap-2">
                        <h3 :id="`${idPrefix}-heading`" class="text-lg font-semibold">{{ editingId ? 'Modifica referente' : 'Aggiungi referente' }}</h3>
                        <button type="button" class="btn btn-circle btn-outline min-h-12 min-w-12 text-xl" aria-label="Chiudi" :disabled="busy" @click="dialog.close()">×</button>
                    </div>
                    <p v-if="error" role="alert" class="text-sm text-error">{{ error }}</p>
                    <label class="form-control" :for="`${idPrefix}-role`">
                        <span class="label-text mb-1">Categoria *</span>
                        <select :id="`${idPrefix}-role`" v-model="draft.role" required :aria-invalid="!!errors.role" class="select select-bordered min-h-12 w-full" :disabled="busy"><option disabled value="">Seleziona categoria</option><option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option></select>
                        <span v-if="errors.role" class="text-xs text-error">{{ errors.role[0] }}</span>
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label v-for="field in fields" :key="field.key" class="form-control min-w-0" :for="`${idPrefix}-${field.key}`">
                            <span class="label-text mb-1">{{ field.label }}{{ field.required ? ' *' : '' }}</span>
                            <input :id="`${idPrefix}-${field.key}`" v-model="draft[field.key]" :type="field.type || 'text'" :required="field.required" :maxlength="field.type === 'tel' ? 50 : 191" :autocomplete="field.autocomplete" class="input input-bordered min-h-12 w-full min-w-0" :disabled="busy" :aria-invalid="!!errors[field.key]" />
                            <span v-if="errors[field.key]" class="text-xs text-error">{{ errors[field.key][0] }}</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <button type="button" class="btn btn-outline min-h-12" :disabled="busy" @click="dialog.close()">Annulla</button>
                        <button type="submit" class="btn btn-primary min-h-12" :disabled="busy">{{ busy ? 'Salvataggio…' : 'Salva' }}</button>
                    </div>
                </form>
            </div>
        </dialog>
    </div>
</template>
<script setup>
import {reactive, ref} from 'vue'
import axios from 'axios'
import {siteContactRole} from '@/utils/siteContactPresentation'
const props = defineProps({contacts:{type:Array,default:()=>[]},siteId:Number,roles:{type:Array,default:()=>[]},editable:Boolean})
const emit = defineEmits(['saved'])
const dialog = ref(null), editingId = ref(null), busy = ref(false), error = ref(''), errors = ref({}), message = ref('')
const idPrefix = `site-${props.siteId}-contact`
const fields = [
    {key:'name',label:'Nome',required:true,autocomplete:'given-name'},
    {key:'surname',label:'Cognome',autocomplete:'family-name'},
    {key:'phone',label:'Telefono',type:'tel',autocomplete:'off'},
    {key:'mobile',label:'Cellulare',type:'tel',autocomplete:'off'},
    {key:'email',label:'Email',type:'email',autocomplete:'off'},
]
const draft = reactive({name:'',surname:'',phone:'',mobile:'',email:'',role:''})
const contactDetails = contact => [contact.mobile && `Cellulare: ${contact.mobile}`, contact.phone && `Telefono: ${contact.phone}`, contact.email].filter(Boolean)
const roleLabel = value => props.roles.find(role => role.value === value)?.label || siteContactRole(value).label
const openEditor = (contact = null) => {
    editingId.value = contact?.id ?? null
    for (const key of Object.keys(draft)) draft[key] = contact?.[key] ?? ''
    error.value = ''; errors.value = {}; message.value = ''
    dialog.value.showModal()
}
const save = async () => {
    if (busy.value) return
    busy.value = true; error.value = ''; errors.value = {}
    try {
        const base = `/api/sites/${props.siteId}/contacts`
        const response = editingId.value ? await axios.put(`${base}/${editingId.value}`, {...draft}) : await axios.post(base, {...draft})
        emit('saved', response.data.contact)
        message.value = editingId.value ? 'Referente modificato correttamente.' : 'Referente aggiunto correttamente.'
        dialog.value.close()
    } catch (e) {
        errors.value = e.response?.data?.errors || {}
        error.value = e.response?.status === 422 ? 'Controlla i dati indicati.' : 'Salvataggio non riuscito. Riprova.'
    } finally { busy.value = false }
}
</script>
