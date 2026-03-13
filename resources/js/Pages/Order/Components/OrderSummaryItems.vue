<template>
    <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl"/>
    Peso stimato totale:
        <span class="font-medium">{{ totalDeclaredWeight }} Kg</span> <span> [= {{ totalDeclaredWeight / 1000 }} ton.]</span>
    
    <div class="collapse collapse-arrow bg-base-100">
        <input type="checkbox"/>
        <div class="collapse-title my-collapse text-xl font-medium">Materiali presenti nell'ordine</div>
        <div class="collapse-content">
            <div v-for="item in props.items">
                {{ item.holder_quantity ?? 0 }} x {{ getHolderName(item.holder_id) }} di
                "{{ item.description? item.description : 'materiale' }}" : 
                {{ item.weight_declared }} Kg
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

const getHolderName = (holderId) => {
    const holder = props.holders?.find((h) => Number(h.id) === Number(holderId));
    return holder?.name ?? 'contenitore non definito';
};

const totalDeclaredWeight = computed(() => {
    return props.items.reduce((total, item) => total + Number(item.weight_declared ?? 0), 0);
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
