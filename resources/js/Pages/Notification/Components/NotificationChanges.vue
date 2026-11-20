<template>
    <div class="overflow-x-auto"><table class="table table-sm w-full [&_th]:px-2 [&_td]:px-2">
        <thead class="whitespace-nowrap"><tr><th class="whitespace-nowrap">Elemento</th><th class="whitespace-nowrap">{{ beforeLabel }}</th><th class="whitespace-nowrap">{{ beforeLabel === 'Pianificato' ? 'Effettivo' : 'Dopo' }}</th><th class="whitespace-nowrap">Delta</th></tr></thead>
        <tbody><tr v-for="(change,index) in rows" :key="index"><td><span class="font-medium">{{ change.label }}</span><span class="block md:inline"><span class="hidden md:inline" aria-hidden="true"> · </span>{{ change.field_label }}</span></td><td>{{ notificationValue(change.before) }}</td><td>{{ notificationValue(change.after) }}</td><td class="whitespace-nowrap tabular-nums">{{ notificationDelta(change) }}</td></tr></tbody>
    </table></div>
</template>
<script setup>
import {computed} from 'vue'
import {notificationValue,notificationChanges,notificationDelta} from '@/utils/executionNotification'
const props = defineProps({changes:{type:Array,default:()=>[]},beforeLabel:{type:String,default:'Prima'}})
const rows = computed(() => notificationChanges(props.changes))
</script>
