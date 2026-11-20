<template>
    <div class="space-y-3">
        <AppAction type="button" class="w-full sm:w-auto" :disabled="busy" :aria-expanded="addingMaterial" @click="addingMaterial = !addingMaterial" variant="function">{{ addingMaterial ? 'Chiudi aggiunta materiale' : 'Aggiungi materiale / CER' }}</AppAction>
        <form v-if="addingMaterial" class="space-y-3 border-y border-base-300 py-3" @submit.prevent="addItem">
            <h5 class="font-semibold">Nuovo materiale nell’ordine</h5>
            <JourneyExecutionMaterialFields v-model="newItem" :lookups="lookups" :disabled="busy" :has-trailer="hasTrailer" />
            <AppAction type="submit" :disabled="busy" variant="confirm">Aggiungi all’ordine</AppAction>
        </form>
        <JourneyOrderMaterials :order="order" :census="census" :show-original-plan="showOriginalPlan">
            <template #item-checklist="{item}">
                <JourneyItemLoadChecklist :loaded="!!item.is_loaded" :cer="item.cer_code?.code" :disabled="busy || isSplitting(item.id) || itemDirty(item.id) || item.load_allocation_valid === false" @change="loaded => send('set_item_loaded', {item_id:item.id,is_loaded:loaded})" />
            </template>
            <template #item-load="{item}">
                <JourneyItemLoadPlacement :item="item" :editing="isSplitting(item.id)" :has-trailer="hasTrailer" :disabled="busy || itemDirty(item.id)" :census="census" @update:editing="editing => setSplitting(item.id,editing)" @command="send" />
            </template>
            <template #item-actions="{item}">
                <div v-if="!isSplitting(item.id)" class="flex shrink-0 items-center gap-2">
                    <template v-if="isEditing(item.id)">
                        <AppAction type="button" :disabled="busy" title="Annulla e chiudi" :aria-label="`Annulla e chiudi modifica materiale CER ${item.cer_code?.code || 'non indicato'}`" @click="cancelItem(item.id)" shape="circle" variant="cancel"><font-awesome-icon :icon="['fas', 'xmark']" aria-hidden="true" /></AppAction>
                        <AppAction type="submit" :form="materialFormId(item.id)" :disabled="busy || !itemDirty(item.id)" title="Salva materiale" :aria-label="`Salva materiale CER ${item.cer_code?.code || 'non indicato'}`" shape="circle" variant="confirm"><font-awesome-icon :icon="['fas', 'floppy-disk']" aria-hidden="true" /></AppAction>
                    </template>
                    <AppAction v-else type="button" :disabled="busy" aria-expanded="false" title="Modifica materiale" :aria-label="`Modifica materiale CER ${item.cer_code?.code || 'non indicato'}${item.description ? ` · ${item.description}` : ''}`" @click="openItem(item.id)" shape="circle" variant="function"><font-awesome-icon :icon="['fas', 'pencil']" aria-hidden="true" /></AppAction>
                </div>
            </template>
            <template #item-editor="{item}">
                <div v-if="isEditing(item.id)" class="space-y-3 border-t border-base-300 pt-3">
                    <form :id="materialFormId(item.id)" class="space-y-3" @submit.prevent="saveItem(item.id)">
                        <JourneyExecutionMaterialFields v-model="items[item.id]" :lookups="lookups" :disabled="busy" include-actuals />
                        <AppAction type="button" :disabled="busy" @click="send('remove_item', {item_id:item.id})" variant="remove">Rimuovi materiale</AppAction>
                    </form>
                    <JourneyRestoreMenu :item-id="item.id" :options="item.restore_options" :busy="busy" :dirty="itemDirty(item.id)" @command="send" />
                </div>
            </template>
            <template #delivery-actions><AppAction type="button" class="mt-2" :disabled="busy" :aria-expanded="editingDeliveries" @click="editingDeliveries = !editingDeliveries" variant="function">{{ editingDeliveries ? 'Chiudi consegne' : 'Modifica contenitori in consegna' }}</AppAction></template>
        </JourneyOrderMaterials>
        <section v-if="order.removed_items?.length" class="space-y-2" aria-label="Materiali rimossi">
            <article v-for="item in order.removed_items" :key="item.id" class="rounded-xl border border-base-300 bg-base-200 p-3 space-y-2">
                <p class="text-sm opacity-60">CER {{ item.cer_code }} · {{ item.description }} (rimosso)</p>
                <div class="flex flex-wrap gap-2"><AppAction type="button" :disabled="busy" @click="send('restore_item', {item_id:item.id})" variant="function">Ripristina</AppAction><AppAction type="button" :disabled="busy" @click="hideRemoved(item)" variant="remove">Elimina definitivamente</AppAction></div>
            </article>
        </section>
        <section v-if="editingDeliveries" class="mt-3 border-t border-base-300 pt-3 space-y-3">
            <form v-for="holder in order.holders" :key="holder.id" class="space-y-2" @submit.prevent="saveDelivery(holder)">
                <h5 class="text-base font-semibold">{{ holder.holder?.name || 'Contenitore' }}</h5>
                <QuantityCounter v-model="holders[holder.id].actual_delivered" :label="`${holder.holder?.name || 'Contenitori'} consegnati`" :max="deliveryLimit(balance, holder.holder_id, holder.actual_delivered)" required :disabled="busy" />
                <div class="flex items-center justify-between gap-2">
                    <p class="min-w-0 text-sm">Disponibili: {{ availableContainers(balance, holder.holder_id) ?? 'da verificare' }}</p>
                    <AppAction type="submit" class="shrink-0 px-3" :disabled="!canSaveDelivery(holder)" variant="confirm">Salva</AppAction>
                </div>
                <p v-if="!deliveryWithinLimit(holders[holder.id].actual_delivered, deliveryLimit(balance, holder.holder_id, holder.actual_delivered))" class="text-sm text-warning">Riduci la quantità ai vuoti disponibili prima di salvare.</p>
            </form>
            <hr class="border-base-300" />
            <form class="space-y-2" @submit.prevent="send('add_holder', { execution_order_id: order.execution_order_id, holder_id: newHolder.holder_id, total_holders_count: numeric(newHolder.quantity), actual_delivered: numeric(newHolder.quantity) },()=>{newHolder.holder_id='';newHolder.quantity=0})">
                <label class="form-control min-w-0"><span class="text-base font-semibold">Aggiungi contenitore</span><select v-model="newHolder.holder_id" required class="select select-bordered w-full min-w-0"><option disabled value="">Seleziona tipo</option><option v-for="holder in lookups.holders" :value="holder.id" :key="holder.id">{{ holder.name }}</option></select></label>
                <p v-if="newHolder.holder_id" class="text-sm">Disponibili: {{ deliveryLimit(balance, newHolder.holder_id) ?? 'da verificare' }}.</p>
                <div class="form-control min-w-0"><span>Consegnati</span><QuantityCounter v-model="newHolder.quantity" label="Nuovi contenitori consegnati" :max="deliveryLimit(balance, newHolder.holder_id)" required :disabled="busy" /></div><AppAction type="submit" variant="confirm" :disabled="busy || !newHolder.holder_id || !deliveryWithinLimit(newHolder.quantity, deliveryLimit(balance, newHolder.holder_id))">Aggiungi consegna</AppAction>
            </form>
        </section>

    </div>
</template>
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import { reactive, ref, watch } from 'vue'
import {useDraftBindings} from '@/Services/localDrafts'
import {availableContainers,deliveryLimit,deliveryWithinLimit} from '@/utils/deliveryAvailability'
import QuantityCounter from '@/Components/UI/QuantityCounter.vue'
import JourneyRestoreMenu from './JourneyRestoreMenu.vue'
import JourneyItemLoadPlacement from './JourneyItemLoadPlacement.vue'
import JourneyItemLoadChecklist from './JourneyItemLoadChecklist.vue'
import JourneyOrderMaterials from './JourneyOrderMaterials.vue'
import JourneyExecutionMaterialFields from './JourneyExecutionMaterialFields.vue'
import {mergeSavedDraft, hasUnsavedDraft, changedDraftValues} from '@/utils/journeyExecutionDrafts'
const props = defineProps({ order: Object, lookups: Object, busy: Boolean, balance: {type:Object,default:null}, hasTrailer: {type:Boolean,default:true}, showOriginalPlan: {type:Boolean,default:false}, census: { type: Array, default: () => [] } })
const emit = defineEmits(['command'])
const localDrafts=useDraftBindings()
const draftFields={cer_code_id:'Codice CER',holder_id:'Tipo contenitore',holder_quantity:'Quantità',weight_declared:'Peso dichiarato',description:'Descrizione',actual_containers:'Quantità ritirata',actual_weight_kg:'Peso effettivo',cargo_location:'Mezzo',is_bulk:'Sfuso',actual_delivered:'Consegnati',quantity:'Quantità'}
const savedItems = reactive({}), savedHolders = reactive({})
const items = reactive({}), holders = reactive({})
const emptyMaterial = () => ({cer_code_id:'',holder_id:null,holder_quantity:null,weight_declared:null,description:'',is_bulk:false,cargo_location:['vehicle','trailer'].includes(props.order.cargo_location) ? props.order.cargo_location : props.order.cargo_location === 'fulfill' ? null : 'vehicle'})
const newItem = ref(emptyMaterial())
const addingMaterial = ref(false), editingItemIds = ref([]), editingDeliveries = ref(false)
const splittingItemIds = ref([])
const newHolder = reactive({ holder_id: '', quantity: 0 })
watch(() => props.order, order => {
    for (const item of order.items || []) {
        const next = Object.fromEntries(['cer_code_id','holder_id','holder_quantity','weight_declared','description','actual_containers','actual_weight_kg','cargo_location'].map(key => [key, item[key] ?? (key === 'description' ? '' : null)]))
        next.is_bulk = !!item.is_bulk
        items[item.id] = mergeSavedDraft(items[item.id], savedItems[item.id], next)
        savedItems[item.id] = {...next}
        localDrafts.bind(`material:${item.id}`,{label:()=>`Materiale CER ${item.cer_code?.code || ''}`,value:()=>items[item.id],baseline:()=>savedItems[item.id],fields:draftFields,canRestore:()=>props.order.execution_status==='pending' && props.order.items.some(row=>row.id===item.id),restore:value=>{items[item.id]={...value};openItem(item.id)}})
    }
    for (const holder of order.holders || []) {
        const next = { total_holders_count: holder.total_holders_count, actual_delivered: holder.actual_delivered ?? holder.total_holders_count }
        holders[holder.id] = mergeSavedDraft(holders[holder.id], savedHolders[holder.id], next)
        savedHolders[holder.id] = {...next}
        localDrafts.bind(`delivery:${holder.id}`,{label:()=>`Consegna ${holder.holder?.name || ''}`,value:()=>holders[holder.id],baseline:()=>savedHolders[holder.id],fields:draftFields,canRestore:()=>props.order.execution_status==='pending' && props.order.holders.some(row=>row.id===holder.id),restore:value=>{holders[holder.id]={...value};editingDeliveries.value=true}})
    }
}, { immediate: true })
const itemDirty = id => hasUnsavedDraft(items[id], savedItems[id])
const materialFormId = id => `execution-order-${props.order.execution_order_id}-item-${id}`
const isEditing = id => editingItemIds.value.includes(id)
const openItem = id => { if (!isEditing(id) && !isSplitting(id)) editingItemIds.value.push(id) }
const closeItem = id => { editingItemIds.value = editingItemIds.value.filter(value => value !== id) }
const cancelItem = id => { items[id] = {...savedItems[id]}; void localDrafts.clear(`material:${id}`); closeItem(id) }
const isSplitting = id => splittingItemIds.value.includes(id)
const setSplitting = (id, editing) => {
    if (editing && (props.busy || itemDirty(id))) return
    splittingItemIds.value = splittingItemIds.value.filter(value => value !== id)
    if (editing) {
        closeItem(id)
        splittingItemIds.value.push(id)
    }
}
const numeric = value => value === '' || value === null || value === undefined ? null : Number(value)
const send = (operation, input, onSaved) => emit('command', operation, input, onSaved)
const canSaveDelivery = holder => !props.busy
    && numeric(holders[holder.id]?.actual_delivered) !== numeric(savedHolders[holder.id]?.actual_delivered)
    && deliveryWithinLimit(holders[holder.id]?.actual_delivered, deliveryLimit(props.balance, holder.holder_id, holder.actual_delivered))
const saveDelivery = holder => {
    if (canSaveDelivery(holder)) send('update_holder', {holder_id:holder.id, actual_delivered:numeric(holders[holder.id].actual_delivered)})
}
const hideRemoved = item => {
    if (window.confirm(`Nascondere il materiale CER ${item.cer_code || 'non indicato'} dalla lista? Non sarà più ripristinabile da questa schermata.`)) send('hide_removed_item', {item_id:item.id})
}
const saveItem = id => {
    if (props.busy || !itemDirty(id)) return
    const normalized = {...items[id], ...Object.fromEntries(['holder_quantity','weight_declared','actual_containers','actual_weight_kg'].map(key => [key,numeric(items[id][key])]))}
    send('update_item', {item_id:id,...changedDraftValues(normalized,savedItems[id])}, () => closeItem(id))
}
const addItem = () => send('add_item', { ...newItem.value, execution_order_id: props.order.execution_order_id, holder_quantity: numeric(newItem.value.holder_quantity), weight_declared: numeric(newItem.value.weight_declared) }, () => { newItem.value = emptyMaterial(); addingMaterial.value = false })

localDrafts.bind(`new-material:${props.order.execution_order_id}`,{label:'Nuovo materiale',value:()=>newItem.value,baseline:()=>emptyMaterial(),fields:draftFields,canRestore:()=>props.order.execution_status==='pending',restore:value=>{newItem.value={...value};addingMaterial.value=true}})
localDrafts.bind(`new-delivery:${props.order.execution_order_id}`,{label:'Nuova consegna',value:()=>({...newHolder}),baseline:()=>({holder_id:'',quantity:0}),fields:draftFields,canRestore:()=>props.order.execution_status==='pending',restore:value=>{Object.assign(newHolder,value);editingDeliveries.value=true}})

</script>
