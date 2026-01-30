<template>
    <Box padding="p-2" class="bg-base-100">
    <div class="flex flex-row justify-between gap-1">
        <div class="flex flex-col items-start">
            <div>
                <span class="stop-handle font-semibold cursor-grab select-none opacity-60 mr-2">⋮⋮</span>
                <span class="text-sm font-medium">{{props.element.customer.ragione_sociale}}</span>
            </div>
            <div class="text-xs text-gray-500">{{props.element.legacy_code }}</div>
        </div>
        <div class="flex items-center gap-1">
            <button type="button" class="btn btn-primary btn-circle btn-sm"
                @click.prevent="emitEditItem()"
            >
                <font-awesome-icon :icon="['fas', 'pencil']" class="text-lg"/>
            </button>
            <button type="button" class="btn btn-primary btn-circle btn-sm"
                @click.prevent="emitViewItem()"
            >
                <font-awesome-icon :icon="['fas', 'eye']" class="text-lg"/>
            </button>
            <button type="button" class="btn btn-primary btn-circle btn-sm"
                @click.prevent="emitViewMap()"
            >
                <font-awesome-icon :icon="['fas', 'map-location-dot']" class="text-lg"/>
            </button>
        </div>
    </div>
    </Box>
</template>

<script setup>
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