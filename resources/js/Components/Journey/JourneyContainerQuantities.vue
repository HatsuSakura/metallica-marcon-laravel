<template>
    <dl v-if="groups.length" class="flex flex-wrap items-stretch gap-1.5">
        <div v-for="group in groups" :key="group.key" class="flex min-w-16 max-w-32 shrink-0 flex-col items-center justify-center rounded-lg bg-base-200 px-2 py-1 text-center" :aria-label="accessibleLabel(group)" :class="{'text-warning': !group.missing && group.quantity < 0}">
            <dt class="order-2 mt-0.5 max-w-full break-words text-xs font-medium leading-none">{{ group.type }}</dt>
            <dd class="order-1 text-lg font-semibold leading-none tabular-nums">{{ group.bulk || group.missing ? '—' : group.quantity }}</dd>
        </div>
    </dl>
    <p v-else class="text-sm opacity-70">{{ emptyLabel }}</p>
</template>
<script setup>
const props = defineProps({groups:{type:Array,default:()=>[]},completed:Boolean,emptyLabel:{type:String,default:'Non indicati'}})
const accessibleLabel = group => group.bulk ? 'Materiale sfuso' : group.missing
    ? `${group.type}: quantità ${props.completed ? 'non registrata' : 'non disponibile'}`
    : `${group.quantity} ${group.type}`
</script>
