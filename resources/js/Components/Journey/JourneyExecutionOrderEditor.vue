<template>
    <div class="space-y-3">
        <button type="button" class="btn btn-primary min-h-12 w-full sm:w-auto" :disabled="busy" :aria-expanded="addingMaterial" @click="addingMaterial = !addingMaterial">{{ addingMaterial ? 'Chiudi aggiunta materiale' : 'Aggiungi materiale / CER' }}</button>
        <form v-if="addingMaterial" class="space-y-3 border-y border-base-300 py-3" @submit.prevent="addItem">
            <h5 class="font-semibold">Nuovo materiale nell’ordine</h5>
            <JourneyExecutionMaterialFields v-model="newItem" :lookups="lookups" :disabled="busy" :has-trailer="hasTrailer" />
            <button class="btn btn-primary min-h-12" :disabled="busy">Aggiungi all’ordine</button>
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
                        <button type="button" class="btn btn-circle btn-outline min-h-12 min-w-12" :disabled="busy" title="Annulla e chiudi" :aria-label="`Annulla e chiudi modifica materiale CER ${item.cer_code?.code || 'non indicato'}`" @click="cancelItem(item.id)"><font-awesome-icon :icon="['fas', 'xmark']" aria-hidden="true" /></button>
                        <button type="submit" :form="materialFormId(item.id)" class="btn btn-circle btn-primary min-h-12 min-w-12" :disabled="busy || !itemDirty(item.id)" title="Salva materiale" :aria-label="`Salva materiale CER ${item.cer_code?.code || 'non indicato'}`"><font-awesome-icon :icon="['fas', 'floppy-disk']" aria-hidden="true" /></button>
                    </template>
                    <button v-else type="button" class="btn btn-circle btn-outline min-h-12 min-w-12" :disabled="busy" aria-expanded="false" title="Modifica materiale" :aria-label="`Modifica materiale CER ${item.cer_code?.code || 'non indicato'}${item.description ? ` · ${item.description}` : ''}`" @click="openItem(item.id)"><font-awesome-icon :icon="['fas', 'pencil']" aria-hidden="true" /></button>
                </div>
            </template>
            <template #item-editor="{item}">
                <div v-if="isEditing(item.id)" class="space-y-3 border-t border-base-300 pt-3">
                    <form :id="materialFormId(item.id)" class="space-y-3" @submit.prevent="saveItem(item.id)">
                        <JourneyExecutionMaterialFields v-model="items[item.id]" :lookups="lookups" :disabled="busy" include-actuals />
                        <button type="button" class="btn btn-outline min-h-12" :disabled="busy" @click="send('remove_item', {item_id:item.id})">Rimuovi materiale</button>
                    </form>
                    <JourneyRestoreMenu :item-id="item.id" :options="item.restore_options" :busy="busy" :dirty="itemDirty(item.id)" @command="send" />
                </div>
            </template>
            <template #delivery-actions><button type="button" class="btn btn-outline min-h-12 mt-2" :disabled="busy" :aria-expanded="editingDeliveries" @click="editingDeliveries = !editingDeliveries">{{ editingDeliveries ? 'Chiudi consegne' : 'Modifica contenitori in consegna' }}</button></template>
        </JourneyOrderMaterials>
        <details v-if="order.removed_items?.length" class="border-t border-base-300 pt-2"><summary class="cursor-pointer py-3">Materiali rimossi ({{ order.removed_items.length }})</summary><div v-for="item in order.removed_items" :key="item.id" class="mt-3"><p>CER {{ item.cer_code }} · {{ item.description }} (rimosso)</p><JourneyRestoreMenu :item-id="item.id" :options="item.restore_options" :fields="[{value:'removed',label:'Presenza del materiale'}]" :busy="busy" @command="send" /></div></details>
        <section v-if="editingDeliveries" class="mt-3 border-t border-base-300 pt-3 space-y-3">
            <form v-for="holder in order.holders" :key="holder.id" class="space-y-2" @submit.prevent="send('update_holder', { holder_id: holder.id, total_holders_count: numeric(holders[holder.id].total_holders_count), actual_delivered: numeric(holders[holder.id].actual_delivered) })">
                <p>{{ holder.holder?.name }}</p>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2"><div class="form-control min-w-0"><span>Da consegnare</span><QuantityCounter v-model="holders[holder.id].total_holders_count" label="Contenitori da consegnare" required :disabled="busy" /></div><div class="form-control min-w-0"><span>Consegnati</span><QuantityCounter v-model="holders[holder.id].actual_delivered" label="Contenitori consegnati" placeholder="—" :disabled="busy" /></div></div>
                <button class="btn btn-primary min-h-12" :disabled="busy">Salva consegna</button>
            </form>
            <form class="space-y-2" @submit.prevent="send('add_holder', { execution_order_id: order.execution_order_id, holder_id: newHolder.holder_id, total_holders_count: numeric(newHolder.quantity) })">
                <label class="form-control min-w-0">Tipo contenitore<select v-model="newHolder.holder_id" required class="select select-bordered w-full min-w-0"><option disabled value="">Seleziona tipo</option><option v-for="holder in lookups.holders" :value="holder.id" :key="holder.id">{{ holder.name }}</option></select></label>
                <div class="form-control min-w-0"><span>Da consegnare</span><QuantityCounter v-model="newHolder.quantity" label="Nuovi contenitori da consegnare" required :disabled="busy" /></div><button class="btn btn-outline min-h-12" :disabled="busy">Aggiungi consegna</button>
            </form>
        </section>

    </div>
</template>
<script setup>
import { reactive, ref, watch } from 'vue'
import QuantityCounter from '@/Components/UI/QuantityCounter.vue'
import JourneyRestoreMenu from './JourneyRestoreMenu.vue'
import JourneyItemLoadPlacement from './JourneyItemLoadPlacement.vue'
import JourneyItemLoadChecklist from './JourneyItemLoadChecklist.vue'
import JourneyOrderMaterials from './JourneyOrderMaterials.vue'
import JourneyExecutionMaterialFields from './JourneyExecutionMaterialFields.vue'
import {mergeSavedDraft, hasUnsavedDraft, changedDraftValues} from '@/utils/journeyExecutionDrafts'
const props = defineProps({ order: Object, lookups: Object, busy: Boolean, hasTrailer: {type:Boolean,default:true}, showOriginalPlan: {type:Boolean,default:false}, census: { type: Array, default: () => [] } })
const emit = defineEmits(['command'])
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
    }
    for (const holder of order.holders || []) {
        const next = { total_holders_count: holder.total_holders_count, actual_delivered: holder.actual_delivered }
        holders[holder.id] = mergeSavedDraft(holders[holder.id], savedHolders[holder.id], next)
        savedHolders[holder.id] = {...next}
    }
}, { immediate: true })
const itemDirty = id => hasUnsavedDraft(items[id], savedItems[id])
const materialFormId = id => `execution-order-${props.order.execution_order_id}-item-${id}`
const isEditing = id => editingItemIds.value.includes(id)
const openItem = id => { if (!isEditing(id) && !isSplitting(id)) editingItemIds.value.push(id) }
const closeItem = id => { editingItemIds.value = editingItemIds.value.filter(value => value !== id) }
const cancelItem = id => { items[id] = {...savedItems[id]}; closeItem(id) }
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
const saveItem = id => {
    if (props.busy || !itemDirty(id)) return
    const normalized = {...items[id], ...Object.fromEntries(['holder_quantity','weight_declared','actual_containers','actual_weight_kg'].map(key => [key,numeric(items[id][key])]))}
    send('update_item', {item_id:id,...changedDraftValues(normalized,savedItems[id])}, () => closeItem(id))
}
const addItem = () => send('add_item', { ...newItem.value, execution_order_id: props.order.execution_order_id, holder_quantity: numeric(newItem.value.holder_quantity), weight_declared: numeric(newItem.value.weight_declared) }, () => { newItem.value = emptyMaterial(); addingMaterial.value = false })

</script>
