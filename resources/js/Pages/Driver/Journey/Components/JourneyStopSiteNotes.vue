<template>
    <div v-if="stop && stop.kind !== 'technical'" class="space-y-2">
        <JourneySiteNotesEditor v-for="site in sites" :key="site.id" :site="site" :show-site-name="sites.length > 1" :editable="canEdit(site)" :busy="busy" @command="(operation,input,onSaved) => $emit('command',operation,input,onSaved)" />
        <section v-if="!sites.length" class="text-sm space-y-1"><h4 class="font-medium">Note della sede</h4><p>Nessuna nota della sede</p></section>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import JourneySiteNotesEditor from '@/Components/Journey/JourneySiteNotesEditor.vue'
import {stopSites,stopOrders} from './driverStopPresentation'
const props = defineProps({stop:Object,editable:Boolean,busy:Boolean})
defineEmits(['command'])
const sites = computed(() => stopSites(props.stop).filter(site => site?.id))
const canEdit = site => props.editable && site.notes_editable && !!site.notes_version
    && stopOrders(props.stop).some(order => order.site?.id === site.id && ['pending','completed'].includes(order.execution_status))
</script>
