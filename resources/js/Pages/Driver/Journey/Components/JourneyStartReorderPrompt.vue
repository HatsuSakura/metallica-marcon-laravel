<template>
    <section class="space-y-2 rounded-xl border border-base-300 bg-base-200 p-3 text-sm" aria-label="Avvio viaggio per riordino tappe">
        <h3 class="font-semibold">Avvia il viaggio per riordinare le tappe</h3>
        <p>Il riordino modifica il viaggio operativo. Se devi correggere il piano prima della partenza, contatta la Logistica.</p>
        <p>Avviando ora, verrà registrato l’inizio effettivo del viaggio.</p>
        <p v-if="error" role="alert" class="text-error">{{ error }}</p>
        <div class="grid grid-cols-2 gap-2">
            <button type="button" class="btn btn-outline min-h-12" :disabled="busy" @click="$emit('cancel')">Annulla</button>
            <button type="button" class="btn btn-primary h-auto min-h-12 whitespace-normal px-2 py-2" :disabled="busy" @click="$emit('confirm')">{{ busy ? 'Avvio…' : 'Avvia e riordina' }}</button>
        </div>
    </section>
</template>
<script setup>
defineProps({busy:Boolean,error:String})
defineEmits(['confirm','cancel'])
</script>
