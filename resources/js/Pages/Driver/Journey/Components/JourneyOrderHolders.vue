<template>

<div class="flex flex-col gap-4">
    <div v-for="item in props.items">
        Tipologia:
        <span class="font-medium">{{ props.holders.find(holder => holder.id === item.holder_id).name }}</span>
        &nbsp;<font-awesome-icon :icon="['fas', 'arrow-right']" />&nbsp;
        Piene da ritirare:
        <span class="font-medium">{{ item.holder_piene }} | </span>
        Differenza richiesta:
        <span class="font-medium">{{ item.holder_vuote }} | </span>
        Vuote da consegnare:
        <span class="font-medium">{{ item.holder_totale }}</span>
    </div>
</div>

</template>

<script setup>

const props = defineProps({
    items: Object,
    holders: Object
})

</script>