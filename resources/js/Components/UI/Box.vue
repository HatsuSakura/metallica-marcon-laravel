<template>
    <div 
        class="border border-gray-500 rounded-md shadow-sm"
        :class="[computedPadding]"
    >
        <div class="text-gray-500 font-medium">
            <slot name="header"/>
        </div>
        <slot />
    </div>
</template>

<script setup>
import {computed} from 'vue'
    const props = defineProps({
        padding: {
            type: String,
            default: 'p-4', // Set the default padding directly in props
        },
    });

    const computedPadding = computed(() => props.padding);
</script>