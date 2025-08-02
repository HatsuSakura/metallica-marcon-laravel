<template>
    
<WorkerItemRow  v-for="(item, index) in props.items"
:key="item.id"
:item="item"
:index="index"
/>

    <hr>
{{ props.items }}
</template>

<script setup>
import WorkerItemRow from '../Components/WorkerItemRow.vue';

const props = defineProps({
    items: Object,
})
</script>