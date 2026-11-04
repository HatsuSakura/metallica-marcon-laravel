<template>
    <details class="border-t border-base-300 pt-2">
        <summary class="cursor-pointer py-3 font-semibold">Ricezione e pesatura</summary>
        <p class="text-sm opacity-70">Dati di magazzino riferiti a questa esecuzione. Le quantità ritirate e il piano originale restano conservati.</p>
        <form v-for="item in items" :key="item.id" class="mt-3 space-y-2 border-t border-base-300 pt-3" @submit.prevent="save(item)">
            <p class="font-semibold">CER {{ item.cer_code?.code }} · {{ item.description }}</p>
            <p class="text-sm">Ritirato: {{ item.actual_containers ?? '—' }} contenitori · {{ item.actual_weight_kg ?? '—' }} kg</p>
            <label class="form-control min-w-0">Magazzino<select v-model="forms[item.id].warehouse_id" required class="select select-bordered w-full min-w-0"><option v-for="warehouse in warehouses" :value="warehouse.id" :key="warehouse.id">{{ warehouse.name }}</option></select></label>
            <div class="grid grid-cols-2 gap-3"><label class="form-control min-w-0">Lordo (kg)<input v-model="forms[item.id].weight_gross" type="number" min="0" step="0.001" class="input input-bordered w-full" /></label><label class="form-control min-w-0">Tara (kg)<input v-model="forms[item.id].weight_tare" type="number" min="0" step="0.001" class="input input-bordered w-full" /></label></div>
            <label class="form-control min-w-0">Stato<select v-model="forms[item.id].warehouse_status" class="select select-bordered w-full min-w-0"><option value="received">Ricevuto</option><option value="weighed">Pesato</option><option value="classified">Pesato e classificato</option></select></label>
            <label class="form-control min-w-0">Note<textarea v-model="forms[item.id].warehouse_notes" class="textarea textarea-bordered w-full min-w-0" /></label>
            <button class="btn btn-primary min-h-12" :disabled="busy || journey.status === 'chiuso'">Salva ricezione</button>
        </form>
        <button v-if="journey.status === 'eseguito'" class="btn btn-success min-h-12 mt-3" :disabled="busy" @click="emit('command', 'close', {})">Chiudi viaggio</button>
    </details>
</template>
<script setup>
import { computed, reactive, watch } from 'vue'
const props = defineProps({journey:Object, orders:Array, warehouses:Array, busy:Boolean})
const emit = defineEmits(['command'])
const forms = reactive({})
const items = computed(() => (props.orders || []).filter(o => o.execution_status === 'completed').flatMap(o => o.items || []))
watch(items, list => { for (const item of list) forms[item.id] = { warehouse_id:item.warehouse_id || props.warehouses[0]?.id, weight_gross:item.weight_gross ?? '', weight_tare:item.weight_tare ?? '', warehouse_status:item.warehouse_status || 'received', warehouse_notes:item.warehouse_notes || '' } }, {immediate:true})
const number = value => value === '' || value == null ? null : Number(value)
const save = item => emit('command','warehouse_item', {item_id:item.id, ...forms[item.id], weight_gross:number(forms[item.id].weight_gross), weight_tare:number(forms[item.id].weight_tare)})
</script>
