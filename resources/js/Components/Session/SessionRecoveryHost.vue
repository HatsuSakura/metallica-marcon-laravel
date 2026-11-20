<template>
    <dialog ref="dialog" class="modal" aria-labelledby="session-recovery-title" @cancel.prevent>
        <div class="modal-box space-y-4">
            <h2 id="session-recovery-title" class="text-lg font-semibold">{{ sessionState.mode === 'expired' ? 'Accedi nuovamente' : sessionState.mode === 'checking' ? 'Verifica della sessione' : 'Connessione da verificare' }}</h2>
            <p role="status">{{ sessionState.message }}</p>
            <p v-if="draftStorageState.error" class="text-sm text-warning">{{ draftStorageState.error }}</p>
            <p v-else-if="sessionState.mode==='expired'" class="text-sm">Le bozze disponibili su questo dispositivo potranno essere recuperate dopo l’accesso. Le operazioni non verranno ripetute automaticamente.</p>
            <details v-if="draftStorageState.error && unsavedDrafts.length" class="space-y-2"><summary class="cursor-pointer font-semibold">Modifiche da copiare prima dell’accesso</summary><label v-for="(draft,index) in unsavedDrafts" :key="index" class="form-control"><span class="label">{{ draft.label }}</span><textarea readonly class="textarea textarea-bordered w-full" :value="Object.entries(draft.changes).map(([key,value])=>(draft.fields[key] || key)+': '+(typeof value==='object'?JSON.stringify(value):value)).join('\n')" @focus="$event.target.select()" /></label></details>
            <div class="modal-action"><AppAction v-if="sessionState.mode==='expired'" variant="confirm" @click="goToSessionLogin">{{ sessionState.localWarningConfirmed ? 'Accedi senza recupero locale' : 'Accedi nuovamente' }}</AppAction><AppAction v-else-if="sessionState.mode==='unavailable'" @click="verifySession">Riprova</AppAction><span v-else class="loading loading-spinner" aria-label="Verifica in corso" /></div>
        </div>
    </dialog>
</template>
<script setup>
import {onMounted,ref,watch,nextTick} from 'vue'
import {usePage} from '@inertiajs/vue3'
import AppAction from '@/Components/UI/AppAction.vue'
import {sessionState,setSessionIdentity,goToSessionLogin,verifySession} from '@/Services/sessionRecovery'
import {draftStorageState,unsavedDrafts} from '@/Services/localDrafts'
const page=usePage(),dialog=ref(null)
watch(()=>[page.props.auth?.user?.id ?? page.props.user?.id ?? null,page.props.session?.csrf_fingerprint],([id,fingerprint])=>setSessionIdentity(id,fingerprint),{immediate:true})
const sync=async()=>{await nextTick();if(!dialog.value)return;if(sessionState.mode==='ready')dialog.value.close();else if(!dialog.value.open)dialog.value.showModal()}
watch(()=>sessionState.mode,sync)
onMounted(sync)
</script>
