<template>
    <section v-if="draftOffers.length || draftStorageState.error || sessionState.notice" class="mb-4 space-y-3" aria-label="Recupero modifiche non salvate">
        <div v-if="sessionState.notice" class="alert alert-info text-sm" role="status"><span>{{ sessionState.notice }}</span><AppAction aria-label="Chiudi avviso sessione" @click="sessionState.notice=''">Chiudi</AppAction></div>
        <p v-if="draftStorageState.error" role="alert" class="alert alert-warning text-sm">{{ draftStorageState.error }}</p>
        <article v-for="offer in draftOffers" :key="offer.record.id" class="rounded-xl border border-warning bg-base-100 p-3 space-y-2">
            <h2 class="text-base font-semibold">Hai modifiche non salvate · {{ offer.label }}</h2>
            <p class="text-sm">{{ date(offer.record.savedAt) }} · disponibili su questo dispositivo per 24 ore</p>
            <p v-if="offer.status==='blocked'" class="text-sm">Il dato non è più modificabile in questa schermata. La bozza resta consultabile; non verrà applicata.</p>
            <p v-if="offer.status==='conflict'" class="text-sm text-warning">Lo stesso dato è cambiato sul server. Confronta i valori e correggili manualmente: il recupero automatico è bloccato.</p>
            <dl class="space-y-2 text-sm"><div v-for="(value,key) in offer.record.changes" :key="key"><dt class="font-semibold">{{ offer.fields[key] || key }}</dt><dd class="whitespace-pre-wrap break-words">Bozza: {{ display(value) }}</dd><dd v-if="offer.comparison.conflicts.some(c=>c.field===key)" class="whitespace-pre-wrap break-words">Ora sul server: {{ display(offer.comparison.conflicts.find(c=>c.field===key).current) }}</dd></div></dl>
            <div class="flex flex-wrap justify-between gap-2"><AppAction variant="cancel" :disabled="sessionState.mode!=='ready'" @click="discardLocalDraft(offer)">Scarta</AppAction><AppAction variant="function" :disabled="offer.status!=='ready' || sessionState.mode!=='ready'" @click="recoverLocalDraft(offer)">Recupera</AppAction></div>
        </article>
    </section>
</template>
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import {draftOffers,draftStorageState,recoverLocalDraft,discardLocalDraft} from '@/Services/localDrafts'
import {sessionState} from '@/Services/sessionRecovery'
const date=value=>new Date(value).toLocaleString('it-IT')
const display=value=>value===null || value===undefined || value===''?'—':typeof value==='object'?JSON.stringify(value):String(value)
</script>
