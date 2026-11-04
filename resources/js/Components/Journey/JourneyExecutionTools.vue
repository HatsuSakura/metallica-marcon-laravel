<template>
    <div v-if="canViewHistory || (!readOnly && journey.status === 'attivo' && !pending)" class="space-y-3">
        <button v-if="!readOnly && journey.status === 'attivo' && !pending" type="button" class="btn btn-success min-h-12 w-full sm:w-auto" :disabled="busy" @click="emit('command', 'finish', {})">Termina viaggio</button>
        <details v-if="canViewHistory" class="border-t border-base-300" @toggle="onToggle"><summary class="cursor-pointer py-3 font-semibold">Piano originale e storico modifiche · rev. {{ journey.execution_revision }}</summary>
            <p v-if="error" role="alert" class="text-error">{{ error }}</p>
            <template v-if="history"><p class="text-sm">Piano congelato alla prima partenza: {{ date(history.baseline.captured_at) }}</p>
                <details class="mt-2"><summary class="cursor-pointer py-3">Consulta il piano originale</summary>
                    <p>{{ history.baseline.data.vehicle?.name }} {{ history.baseline.data.vehicle?.plate }} · {{ history.baseline.data.trailer?.name }} {{ history.baseline.data.trailer?.plate }}</p>
                    <ol class="list-decimal pl-5 space-y-2"><li v-for="stop in history.baseline.data.stops" :key="stop.id">{{ stop.customer?.company_name || stop.description || 'Sosta tecnica' }}<div v-for="id in stop.orders" :key="id"><template v-for="order in history.baseline.data.orders.filter(o => o.id === id)" :key="order.id"><p>Ordine {{ order.legacy_code || order.id }}</p><p v-for="item in order.items" :key="item.id" class="text-sm">CER {{ item.cer_code?.code }} · {{ item.holder_quantity ?? '—' }} {{ item.holder?.name }} · {{ item.weight_declared ?? '—' }} kg · {{ item.description }}</p><p v-for="holder in order.holders" :key="holder.id" class="text-sm">In consegna: {{ holder.total_holders_count }} {{ holder.holder?.name }}</p></template></div></li></ol>
                </details>
                <ol class="mt-3 space-y-3"><li v-for="event in history.events" :key="event.id" class="border-t border-base-300 pt-2 text-sm"><p class="font-semibold">{{ date(event.created_at) }} · {{ event.actor }} · {{ operationLabel(event.payload.operation || event.payload.event) }} · rev. {{ event.payload.revision }}</p><p v-if="event.payload.reason_code">Motivo: {{ event.payload.reason_code }} {{ event.payload.driver_notes }}</p><ul class="space-y-1"><li v-for="change in event.payload.changes" :key="change.path" class="break-words">{{ fieldLabel(change.path) }}: {{ value(change.before) }} → {{ value(change.after) }}</li></ul></li></ol>
            </template>
        </details>
    </div>
</template>
<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
const props = defineProps({ journey: Object, readOnly: Boolean, busy: Boolean, canViewHistory: {type:Boolean,default:false} })
const emit = defineEmits(['command'])
const pending = computed(() => (props.journey.stops || []).some(stop => ['planned','in_progress'].includes(stop.status)))
const history = ref(null), error = ref('')
const onToggle = async e => {
    if (!props.canViewHistory || !e.target.open) return
    try { history.value = (await axios.get(`/api/journeys/${props.journey.id}/execution/history`)).data; error.value = '' }
    catch (e) { error.value = e?.response?.data?.message || 'Storico non disponibile.' }
}
const date = value => value ? new Intl.DateTimeFormat('it-IT', {dateStyle:'short', timeStyle:'short', timeZone:'Europe/Rome'}).format(new Date(value)) : '—'
const value = v => v === null ? 'non registrato' : typeof v === 'object' ? 'elemento aggiunto/modificato' : String(v)
const labels = {is_loaded:'Caricato',vehicle:'Contenitori in motrice',trailer:'Contenitori in rimorchio',holder_quantity:'Contenitori da ritirare',actual_containers:'Contenitori ritirati',weight_declared:'Peso dichiarato',actual_weight_kg:'Peso effettivo',total_holders_count:'Contenitori da consegnare',actual_delivered:'Contenitori consegnati',sequence:'Sequenza',status:'Stato',description:'Descrizione',notes:'Note',cargo_location:'Zona di carico',removed:'Materiale rimosso'}
const fieldLabel = path => labels[path.split('.').at(-1)] || path
const operationLabel = op => ({set_item_allocations:'Suddivisione del carico',set_item_loaded:'Verifica carico',execution_started:'Partenza',reorder:'Riordino tappe',complete:'Tappa completata',skip:'Tappa saltata',reopen:'Tappa riaperta',add_stop:'Tappa aggiunta',update_journey:'Viaggio modificato',update_order:'Ordine modificato',add_item:'Materiale aggiunto',update_item:'Materiale modificato',remove_item:'Materiale rimosso',add_holder:'Consegna aggiunta',update_holder:'Consegna modificata',restore_original:'Ripristino originale',restore_previous:'Ripristino precedente',undo_restore:'Ripristino annullato',finish:'Viaggio terminato'})[op] || op
</script>
