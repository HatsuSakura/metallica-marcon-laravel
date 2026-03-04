<template>
    <div class="collapse collapse-arrow bg-base-100">
        <input type="checkbox"/>
        <div class="collapse-title my-collapse text-xl font-medium">Gestione Contenitori</div>
        <div class="collapse-content">
            <div v-for="item in props.items">
                {{ holders.find(holder => holder.id === item.holder_id).name }}
                <font-awesome-icon :icon="['fas', 'arrow-right']" />
                piene da ritirare: {{ item.filled_holders_count }} |
                variazione : {{ item.empty_holders_count }} |
                vuote da consegnare: {{ item.total_holders_count }}
            </div>
        </div>
    </div>
</template>

<script setup>

import { defineProps, computed } from 'vue';

const props = defineProps({
    items: Object,
    holders: Object,
});

</script>

<style scoped>
div.collapse .my-collapse {
    padding-inline-end: 1.5rem;
    min-height: 2.5rem;
    font-size: 1.2rem;
    line-height: 1.0rem;
}
</style>

