<template>
    <form class="space-y-3 rounded-xl border border-base-300 p-3" @submit.prevent="applyFilters" aria-label="Filtri clienti">
        <div class="flex flex-wrap gap-x-4 gap-y-1">
            <label v-for="option in options" :key="option.key" class="flex min-h-12 cursor-pointer items-center gap-2 text-sm"><input v-model="filterForm[option.key]" type="checkbox" class="toggle toggle-primary" />{{ option.label }}</label>
        </div>
        <div class="flex flex-wrap items-end gap-2">
            <div class="min-w-0 flex-1 basis-48"><label for="customer-search" class="label">Cerca clienti</label><input id="customer-search" v-model="filterForm.chiave" type="search" class="input input-bordered w-full" placeholder="Ragione sociale o partita IVA" /></div>
            <AppAction type="submit" variant="function" :busy="busy"><font-awesome-icon :icon="['fas', 'magnifying-glass']" aria-hidden="true" />Cerca</AppAction>
            <AppAction :disabled="busy || !filterForm.chiave" @click="resetChiave">Azzera ricerca</AppAction>
        </div>
    </form>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import {reactive, ref} from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    filters: Object,
})

const busy = ref(false)
const options = [{key:'continuativo',label:'Continuativi'},{key:'occasionale',label:'Occasionali'},{key:'deleted',label:'Cancellati'}]
const filterForm = reactive({
    localTable: props.filters?.localTable ?? false,
    continuativo: props.filters?.continuativo ?? true,
    occasionale: props.filters?.occasionale ?? false,
    deleted: props.filters?.deleted ?? false,
    chiave: props.filters?.chiave ?? null,
})

const applyFilters = () => {
    if (busy.value) return
    busy.value = true
    router.get(
        route('customer.index'),
        filterForm,
        {preserveState: true, preserveScroll: true, onFinish: () => { busy.value = false }},
    )
}

const resetChiave = () => {
    filterForm.chiave = null
    applyFilters()
}
</script>
