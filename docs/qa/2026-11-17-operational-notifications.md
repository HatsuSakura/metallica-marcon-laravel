<template>
    <dialog ref="dialog" class="modal stop-transition" aria-labelledby="stop-transition-title" aria-describedby="stop-transition-next" @cancel.prevent>
        <div class="modal-box flex max-w-md flex-col gap-4 text-center text-base leading-relaxed">
            <h2 ref="title" id="stop-transition-title" tabindex="-1" class="text-base break-words focus:outline-none">
                <span class="flex items-center justify-center gap-2 font-semibold"><font-awesome-icon :icon="['fas', outcome.skipped ? 'forward' : 'check']" class="h-5 w-5 shrink-0" :class="outcome.skipped ? 'text-warning' : 'text-success'" aria-hidden="true" />Tappa</span>
                <span class="block font-normal">{{ outcome.label }}</span>
                <span class="block font-semibold">{{ outcome.skipped ? 'SALTATA' : 'CONCLUSA' }}</span>
            </h2>
            <p id="stop-transition-next" class="break-words">
                <template v-if="outcome.nextLabel"><strong class="flex items-center justify-center gap-2 font-semibold"><font-awesome-icon :icon="['fas','route']" class="h-5 w-5 shrink-0 text-success" aria-hidden="true" />PROSSIMA TAPPA</strong><span class="block font-normal">{{ outcome.nextLabel }}</span></template>
                <template v-else>Tappe terminate. Contatta la Logistica per concordare lo scarico.</template>
            </p>
            <p v-if="outcome.nextLabel" class="font-normal">Buon viaggio!</p>
            <div class="mt-2"><AppAction variant="confirm" class="w-full sm:w-auto sm:min-w-32" @click="acknowledge">OK</AppAction></div>
        </div>
    </dialog>
</template>
<script setup>
import {onMounted,onUnmounted,ref} from 'vue'
import AppAction from '@/Components/UI/AppAction.vue'
defineProps({outcome:{type:Object,required:true}})
const emit=defineEmits(['finished'])
const dialog=ref(null)
const title=ref(null)
const acknowledge=()=>{dialog.value?.close();emit('finished')}
onMounted(()=>{dialog.value.showModal();title.value?.focus({preventScroll:true})})
onUnmounted(()=>{dialog.value?.close()})
</script>
<style scoped>
.stop-transition[open] { animation: stop-enter 180ms ease-out both; }
.stop-transition::backdrop { @apply bg-base-300/60 backdrop-blur-sm; }
@keyframes stop-enter { from { opacity: 0; } to { opacity: 1; } }
@media (prefers-reduced-motion: reduce) { .stop-transition[open] { animation: none; } }
</style>

Gerarchia finale: TAPPA CONCLUSA/SALTATA con icona su una riga, stesso
peso semibold di PROSSIMA TAPPA; ragioni sociali normali sotto ciascuna etichetta.

«Buon viaggio!» semibold con icona truck success inline, stessa dimensione
delle altre icone; presente solo quando esiste una prossima tappa.

### Termina viaggio — 2026-11-20
Il comando finish dell’autista notifica tutti gli utenti Logistica con tipo
journey-execution-update, titolo «Viaggio terminato», delta aggregato rispetto
al piano e riferimenti a tutti gli ordini esecutivi. Caricato/split e modifiche
intermedie restano silenziosi. Test: 19 passati, 120 assertion, inclusi rifiuto
se restano tappe e retry idempotente senza notifiche duplicate.
