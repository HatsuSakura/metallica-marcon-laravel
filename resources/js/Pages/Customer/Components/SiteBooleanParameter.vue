<template>
    <div class="min-w-0 space-y-2 rounded-lg bg-base-200 p-3">
        <div class="flex min-w-0 items-start gap-2">
            <font-awesome-icon :icon="['fas', iconString]" class="mt-0.5 h-5 w-5 shrink-0" :class="booleanValue ? 'text-success' : 'text-base-content'" aria-hidden="true" />
            <span class="text-sm font-semibold">{{ title }}</span>
        </div>
        <label v-if="isEditable" class="flex min-h-12 w-fit cursor-pointer items-center gap-2 text-sm">
            <input :checked="booleanValue" type="checkbox" class="toggle toggle-success shrink-0" :aria-label="title" @change="$emit('update:booleanValue', $event.target.checked)" />
            <span>{{ booleanValue ? positiveLabel : negativeLabel }}</span>
        </label>
        <span v-else class="badge h-auto min-h-7 gap-1 whitespace-normal py-1" :class="booleanValue ? 'badge-success' : 'badge-neutral'">
            <font-awesome-icon :icon="['fas', booleanValue ? 'check' : 'minus']" aria-hidden="true" />{{ booleanValue ? positiveLabel : negativeLabel }}
        </span>
    </div>
</template>
<script setup>
defineProps({title:String, booleanValue:Boolean, iconString:String, isEditable:Boolean, positiveLabel:{type:String,default:'Disponibile'}, negativeLabel:{type:String,default:'Non disponibile'}})
defineEmits(['update:booleanValue'])
</script>
