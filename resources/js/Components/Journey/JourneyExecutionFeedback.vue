<template>
    <div v-if="message" :role="tone === 'error' ? 'alert' : 'status'" :aria-live="tone === 'error' ? 'assertive' : 'polite'" class="fixed inset-x-3 z-50 mx-auto max-w-xl rounded-xl border bg-base-100 p-3 text-sm shadow-lg" :class="tone === 'error' ? 'border-error' : 'border-success'" style="top: calc(4rem + max(0.75rem, env(safe-area-inset-top)))">
        <div class="flex items-start gap-2"><p class="min-w-0 flex-1 break-words pt-2">{{ message }}</p><button type="button" class="btn btn-circle btn-outline min-h-12 min-w-12 shrink-0 text-2xl font-semibold" aria-label="Chiudi messaggio" @click="emit('dismiss')">×</button></div>
        <button v-if="undoAvailable" type="button" class="btn btn-outline min-h-12 w-full sm:w-auto" :disabled="busy" @click="emit('undo')">Annulla ripristino</button>
    </div>
</template>
<script setup>
defineProps({message:String, undoAvailable:Boolean, busy:Boolean, tone:{type:String,default:'success'}})
const emit = defineEmits(['undo','dismiss'])
</script>
