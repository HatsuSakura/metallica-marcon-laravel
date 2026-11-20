<template>
    <RegistryPage title="Agenti e token API">
        <template #actions><AppAction as="a" :href="route('dev.api-docs')">Documentazione API</AppAction></template>
        <p class="text-sm">Ogni agente ha un’identità e permessi propri. I token consentono le sole operazioni abilitate sia per l’agente sia per il token.</p>
        <details class="rounded-xl border border-base-300 p-3">
            <summary class="cursor-pointer font-semibold">Nuovo agente</summary>
            <RegistryForm :form="creation" v-slot="{busy}" class="mt-3 max-w-2xl" @submit="create">
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="form-control"><span class="label">Nome</span><input v-model="creation.name" name="name" required maxlength="120" class="input input-bordered" /></label>
                    <label class="form-control"><span class="label">Identificativo</span><input v-model="creation.slug" name="slug" required pattern="[a-zA-Z0-9_-]+" maxlength="100" placeholder="email-orders-qa" class="input input-bordered" /></label>
                </div>
                <fieldset class="my-3"><legend class="label">Permessi dell’agente</legend><label v-for="(label, key) in availableAbilities" :key="key" class="flex items-center gap-3 py-2 text-sm"><input v-model="creation.abilities" type="checkbox" :value="key" class="checkbox checkbox-primary" />{{ label }}</label></fieldset>
                <AppAction type="submit" variant="confirm" :busy="busy" :disabled="!creation.name.trim() || !creation.slug || !creation.abilities.length">Crea agente</AppAction>
            </RegistryForm>
        </details>
        <section v-if="secret" ref="secretPanel" tabindex="-1" role="region" aria-label="Token generato" class="rounded-xl border border-warning p-4 space-y-3">
            <h2 class="text-lg font-semibold">Token generato: copialo ora</h2>
            <p class="text-sm">Sarà mostrato soltanto qui. Conservalo nel gestore dei segreti del servizio che esegue l’agente.</p>
            <label for="agent-secret" class="label">Bearer token</label><textarea id="agent-secret" :value="secret" readonly class="textarea textarea-bordered w-full" spellcheck="false" autocomplete="off" />
            <div class="flex flex-wrap gap-2"><AppAction @click="copyToken">{{ copied ? 'Copiato' : 'Copia token' }}</AppAction><AppAction @click="closeSecret">Ho salvato il token, chiudi</AppAction></div>
        </section>
        <p v-if="tokenError" role="alert" class="text-error">{{ tokenError }}</p>
        <p v-if="!agents.length" class="py-4 text-sm">Nessun agente configurato. Crea un agente per assegnare i primi permessi.</p>
        <section v-for="agent in agents" :key="agent.id" class="border-t border-base-300 pt-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2"><h2 class="text-lg font-semibold">{{ agent.name }} <span class="text-sm font-normal">({{ agent.slug }})</span></h2><span class="badge" :class="agent.is_active ? 'badge-success' : 'badge-neutral badge-outline'">{{ agent.is_active ? 'Attivo' : 'Disabilitato' }}</span></div>
            <details class="rounded-xl border border-base-300 p-3"><summary class="cursor-pointer font-semibold">Permessi e stato agente</summary><AgentSettings :agent="agent" :available-abilities="availableAbilities" /></details>
            <ul class="divide-y divide-base-300" :aria-label="`Token di ${agent.name}`">
                <li v-for="token in agent.tokens" :key="token.id" class="flex flex-wrap items-start gap-3 py-2">
                    <div class="min-w-0 flex-1 basis-48">
                        <h3 class="break-words text-base font-semibold">{{ token.name }}</h3>
                        <dl class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm"><div><dt class="inline">Scadenza: </dt><dd class="inline">{{ date(token.expires_at) }}</dd></div><div><dt class="inline">Ultimo utilizzo: </dt><dd class="inline">{{ date(token.last_used_at) }}</dd></div></dl>
                        <details class="mt-1 text-sm"><summary class="cursor-pointer">Permessi del token</summary><ul class="mt-1 space-y-1"><li v-for="ability in token.abilities" :key="ability">{{ availableAbilities[ability] || ability }}</li></ul></details>
                    </div>
                    <AppAction variant="remove" :disabled="revoking === token.id" @click="revoke(agent, token)">Revoca</AppAction>
                </li>
            </ul>
            <p v-if="!agent.tokens.length" class="text-sm">Nessun token attivo.</p>
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="issue(agent)">
                <label class="form-control min-w-0 flex-1 basis-48"><span class="label">Nome nuovo token</span><input v-model="tokenDrafts[agent.id].name" class="input input-bordered" required maxlength="120" :disabled="!agent.is_active" placeholder="email-orders-qa" /></label>
                <label class="form-control w-36"><span class="label">Validità (giorni)</span><input v-model="tokenDrafts[agent.id].days" type="number" min="1" max="365" required class="input input-bordered" :disabled="!agent.is_active" /></label>
                <AppAction type="submit" variant="confirm" :disabled="!agent.is_active || !tokenDrafts[agent.id].name.trim() || !!issuing" :busy="issuing === agent.id">Genera token</AppAction>
                <p class="w-full text-sm">Il nuovo token riceve i permessi attuali dell’agente. Per una rotazione, aggiorna il servizio con il nuovo token prima di revocare il precedente.</p>
            </form>
        </section>
    </RegistryPage>
</template>
<script setup>
import {nextTick, onBeforeUnmount, reactive, ref, watch} from 'vue'
import {router, useForm} from '@inertiajs/vue3'
import axios from 'axios'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'
import RegistryForm from '@/Components/Registry/RegistryForm.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import AgentSettings from './Settings.vue'
const props = defineProps({agents: {type: Array, required: true}, availableAbilities: {type: Object, required: true}})
const creation = useForm({name: '', slug: '', abilities: ['orders:read']})
const create = () => creation.post(route('dev.agents.store'), {onSuccess: () => creation.reset()})
const secret = ref(''), copied = ref(false), secretPanel = ref(null), tokenError = ref(''), issuing = ref(null), revoking = ref(null)
const tokenDrafts = reactive({})
watch(() => props.agents, agents => {for (const a of agents) tokenDrafts[a.id] ??= {name:'', days:90}}, {immediate:true})
const closeSecret = () => {secret.value='';copied.value=false}
onBeforeUnmount(closeSecret)
const issue = async agent => {
    issuing.value = agent.id; tokenError.value = ''; closeSecret()
    try {
        const {data} = await axios.post(route('dev.agents.tokens.store', agent.id), {...tokenDrafts[agent.id], abilities:agent.abilities})
        secret.value = data.token
        await nextTick(); secretPanel.value?.focus(); secretPanel.value?.scrollIntoView({block:'nearest'})
        router.reload({only:['agents']})
    } catch (error) {tokenError.value = Object.values(error.response?.data?.errors ?? {}).flat().join(' ') || 'Token non generato. Verifica i permessi e riprova.'}
    finally {issuing.value=null}
}
const copyToken = async () => {try {await navigator.clipboard.writeText(secret.value);copied.value=true} catch {tokenError.value='Seleziona il token e copialo manualmente.'}}
const revoke = (agent, token) => {if (!window.confirm(`Revocare il token ${token.name}? Il servizio che lo usa perderà immediatamente l’accesso.`)) return;revoking.value=token.id;router.delete(route('dev.agents.tokens.destroy', {agent:agent.id,token:token.id}), {preserveScroll:true,onFinish:()=>{revoking.value=null}})}
const date = value => value ? new Date(value).toLocaleString('it-IT') : 'Mai'
</script>
