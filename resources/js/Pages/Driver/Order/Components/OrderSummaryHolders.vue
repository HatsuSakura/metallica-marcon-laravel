<template>
    <div class="collapse collapse-arrow bg-base-100">
        <input type="checkbox"/>
        <div class="collapse-title my-collapse text-xl font-medium">Gestione Contenitori</div>
        <div class="collapse-content">
            <div v-for="item in props.items">
                {{ holders.find(holder => holder.id === item.holder_id).name }}
                <font-awesome-icon :icon="['fas', 'arrow-right']" />
                piene da ritirare: {{ item.holder_piene }} |
                variazione : {{ item.holder_vuote }} |
                vuote da consegnare: {{ item.holder_totale }}
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

