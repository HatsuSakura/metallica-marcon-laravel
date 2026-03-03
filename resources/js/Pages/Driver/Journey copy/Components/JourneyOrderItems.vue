<template>

<div class="flex flex-col gap-4">
    <div v-for="item in props.items">
        {{ item.holder_quantity }} x {{ props.holders.find(holder => holder.id === item.holder_id).name }} di
        <span class="badge badge-primary badge-lg mr-2">
            CER {{ props.cerList.find(cerList => cerList.id === item.cer_code_id).code }}
        </span>
        {{ item.description }}
    </div>
</div>

</template>

<script setup>

const props = defineProps({
    items: Object,
    cerList: Object,
    holders: Object
})

</script>