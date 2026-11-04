<template>
    <details class="border-t border-base-300 pt-2" @toggle="onToggle">
        <summary class="cursor-pointer py-3 font-semibold">Documenti</summary>
        <p v-if="error" role="alert" class="text-error">{{ error }}</p>
        <p v-if="status" class="text-sm">{{ status.current ? 'Documenti aggiornati' : status.documents_status === 'generating' ? 'Generazione in corso…' : 'Documenti da aggiornare' }} · versione {{ status.documents_version }}</p>
        <div class="flex flex-wrap gap-2 mt-2"><button type="button" class="btn btn-outline min-h-12" :disabled="loading" @click="load()">Aggiorna stato</button><button v-if="!readOnly && status?.can_generate" type="button" class="btn btn-primary min-h-12" :disabled="loading" @click="load(true)">Genera documenti aggiornati</button></div>
        <a v-for="document in documents" :key="document.name" class="btn btn-link h-auto min-h-12 max-w-full break-all whitespace-normal" :href="`${url}/${encodeURIComponent(document.name)}/download`">{{ document.name }}</a>
    </details>
</template>
<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue'
import axios from 'axios'
const props = defineProps({ journeyId: Number, executionOrderId: Number, revision: Number, readOnly: Boolean })
const url = computed(() => `/api/journeys/${props.journeyId}/execution/orders/${props.executionOrderId}/documents`)
const loading = ref(false), error = ref(''), status = ref(null), documents = ref([])
const load = async (generate = false) => {
    loading.value = true; error.value = ''
    try { const response = generate ? await axios.post(url.value) : await axios.get(url.value); status.value = response.data.data; documents.value = response.data.documents }
    catch (e) { error.value = e?.response?.data?.message || 'Documenti non disponibili.' }
    finally { loading.value = false }
}
const expanded = ref(false)
let timer = null
const stopPolling = () => { if (timer) clearInterval(timer); timer = null }
const onToggle = e => { expanded.value = e.target.open; if (expanded.value) load(); else stopPolling() }
watch(() => `${expanded.value}:${status.value?.documents_status}`, () => { stopPolling(); if (status.value?.documents_status === 'generating' && expanded.value) timer = setInterval(() => { if (!loading.value) load() }, 3000) })
watch(() => props.revision, () => { if (expanded.value) load() })
onBeforeUnmount(stopPolling)
</script>
