<template>
    <div class="grid grid-cols-2 gap-2">
        <button type="button" class="btn btn-outline min-h-12 h-auto min-w-0 gap-1 px-2 py-2 text-sm" :disabled="busy" @click="$emit('cancel')">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6" /></svg>
            <span class="whitespace-normal">Annulla riordino</span>
        </button>
        <button type="button" class="btn btn-primary min-h-12 h-auto min-w-0 gap-1 px-2 py-2 text-sm" :disabled="busy" @click="$emit('save')">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 3h12l4 4v14H3V3h2Zm2 0v6h10V3M7 21v-8h10v8" /></svg>
            <span class="whitespace-normal">Salva ordine</span>
        </button>
    </div>
</template>
<script setup>
defineProps({busy:Boolean})
defineEmits(['cancel', 'save'])
</script>
