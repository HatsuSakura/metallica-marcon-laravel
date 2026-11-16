<template>
    <Box padding="p-2" class="bg-base-100">
    <div class="flex flex-col gap-2">
        <div class="min-w-0 flex flex-col items-start">
            <div>
                <font-awesome-icon :icon="['fas', 'grip-vertical']" class="stop-handle cursor-grab select-none opacity-60 mr-2" />
                <span class="break-words text-sm font-medium">{{props.element.customer.company_name}}</span>
            </div>
            <div class="text-xs text-base-content/75">{{props.element.legacy_code }}</div>
        </div>
        <div class="flex items-center gap-1">
            <AppAction size="sm" type="button"
                @click.prevent="emitEditItem()"
             variant="function" shape="circle" aria-label="Modifica ordine">
                <font-awesome-icon :icon="['fas', 'pencil']" class="text-sm"/>
            </AppAction>
            <AppAction size="sm" type="button"
                @click.prevent="emitViewItem()"
             variant="neutral" shape="circle" aria-label="Visualizza ordine">
                <font-awesome-icon :icon="['fas', 'eye']" class="text-sm"/>
            </AppAction>
            <AppAction size="sm" type="button"
                @click.prevent="emitViewMap()"
             variant="neutral" shape="circle" aria-label="Mostra posizione ordine">
                <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-sm"/>
            </AppAction>
        </div>
    </div>
    </Box>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'

import Box from '@/Components/UI/Box.vue';

const props = defineProps({
    element: Object,
    index: Number,
});

const emit = defineEmits(['edit-item', 'view-item', 'view-map']);

const emitEditItem = () => {
    emit('edit-item', props.element);
};

const emitViewItem = () => {
    emit('view-item', props.element);
};

const emitViewMap = () => {
    emit('view-map', props.element);
};

</script>
