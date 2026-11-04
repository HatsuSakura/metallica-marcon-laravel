<template>
            <form class="space-y-3" @submit.prevent="addStop">
                <h3 class="font-semibold">Aggiungi tappa</h3>
                <label class="form-control min-w-0">Tipo<select v-model="mode" class="select select-bordered w-full min-w-0"><option value="order">Ordine disponibile</option><option value="customer">Cliente senza ordine pianificato</option><option value="technical">Sosta tecnica</option></select></label>
                <div v-if="mode === 'order'" class="form-control min-w-0">
                    <label for="add-stop-order">Ordine</label>
                    <SearchableSelect v-model="stop.source_order_id" input-id="add-stop-order" :options="orderOptions" placeholder="Cerca codice ordine" required :disabled="busy" />
                </div>
                <template v-if="mode === 'customer'">
                    <div class="form-control min-w-0">
                        <label for="add-stop-customer">Cliente</label>
                        <SearchableSelect v-model="stop.customer_id" input-id="add-stop-customer" :options="customerOptions" placeholder="Cerca ragione sociale" required :disabled="busy" />
                    </div>
                    <div class="form-control min-w-0">
                        <label for="add-stop-site">Sede</label>
                        <SearchableSelect v-model="stop.site_id" input-id="add-stop-site" :options="siteOptions" placeholder="Cerca nome o indirizzo sede" required :disabled="busy || !stop.customer_id" />
                        <p v-if="stop.customer_id && !sites.length" class="text-sm text-warning">Nessuna sede disponibile per questo cliente.</p>
                    </div>
                </template>
                <label v-if="mode === 'technical'" class="form-control min-w-0">Attività<select v-model="stop.technical_action_id" required class="select select-bordered w-full min-w-0"><option disabled value="">Seleziona attività</option><option v-for="action in lookups.technicalActions" :key="action.id" :value="action.id">{{ action.label }}</option></select></label>
                <label class="form-control min-w-0">Descrizione<input v-model="stop.description" class="input input-bordered w-full min-w-0" /></label><label class="form-control min-w-0">Indirizzo<input v-model="stop.address_text" class="input input-bordered w-full min-w-0" /></label>
                <button class="btn btn-primary min-h-12" :disabled="busy">Aggiungi tappa</button>
            </form>
</template>
<script setup>
import { computed, reactive, ref, watch } from 'vue'
import SearchableSelect from '@/Components/UI/SearchableSelect.vue'
const props = defineProps({lookups:{type:Object,default:()=>({})},busy:Boolean})
const emit = defineEmits(['command','saved'])
const stop = reactive({source_order_id:'',customer_id:'',site_id:'',technical_action_id:'',description:'',address_text:''})
const mode = ref('order')
const sites = computed(() => (props.lookups.sites || []).filter(site => site.customer_id === stop.customer_id))
const customerOptions = computed(() => (props.lookups.customers || []).map(customer => ({value:customer.id,label:customer.company_name})))
const siteLabel = site => [site?.name, site?.address].filter(Boolean).join(' · ')
const siteOptions = computed(() => sites.value.map(site => ({value:site.id,label:siteLabel(site)})))
const orderOptions = computed(() => {
    const customers = new Map((props.lookups.customers || []).map(customer => [customer.id, customer.company_name]))
    const sites = new Map((props.lookups.sites || []).map(site => [site.id, site]))
    return (props.lookups.orders || []).map(order => ({value:order.id,label:[order.legacy_code || `#${order.id}`, customers.get(order.customer_id), siteLabel(sites.get(order.site_id))].filter(Boolean).join(' · ')}))
})
watch(() => stop.customer_id, () => { stop.site_id = '' })
const addStop = () => {
    if (mode.value === 'order' && !stop.source_order_id) return
    if (mode.value === 'customer' && (!stop.customer_id || !sites.value.some(site => site.id === stop.site_id))) return
    emit('command', 'add_stop', { kind: mode.value === 'technical' ? 'technical' : 'customer', description: stop.description, address_text: stop.address_text, ...(mode.value === 'order' ? { source_order_id: stop.source_order_id } : mode.value === 'customer' ? { customer_id: stop.customer_id, site_id: stop.site_id } : { technical_action_id: stop.technical_action_id }) }, () => emit('saved'))
}
</script>
