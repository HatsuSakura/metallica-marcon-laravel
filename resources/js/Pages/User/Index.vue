<template>
<RegistryPage title="Utenti">

    <template #actions><AppAction :as="Link" variant="function" :href="route('user.create')"><font-awesome-icon :icon="['fas','plus']" aria-hidden="true" />Nuovo utente</AppAction></template>
<div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-2 mb-2">
        <span class="text-sm opacity-70">Ordina per:</span>
        <Link
            :href="route('user.index', sortParams('name'))"
            class="btn btn-outline"
            :class="isSortedBy('name') ? 'btn-primary' : 'btn-outline'"
        >
            Nome
            <span v-if="isSortedBy('name')">({{ sortDirectionLabel }})</span>
        </Link>
        <Link
            :href="route('user.index', sortParams('surname'))"
            class="btn btn-outline"
            :class="isSortedBy('surname') ? 'btn-primary' : 'btn-outline'"
        >
            Cognome
            <span v-if="isSortedBy('surname')">({{ sortDirectionLabel }})</span>
        </Link>
        <Link
            :href="route('user.index', sortParams('role'))"
            class="btn btn-outline"
            :class="isSortedBy('role') ? 'btn-primary' : 'btn-outline'"
        >
            Ruolo
            <span v-if="isSortedBy('role')">({{ sortDirectionLabel }})</span>
        </Link>
    </div>

    <Box padding="px-3 py-2 sm:px-4" v-for="user in props.users.data" :key="user.id" :class="{'border-dashed' : user.deleted_at}">
    <div class="flex flex-col md:flex-row gap-2 md:items-center justify-between">
        <div :class="{'opacity-75' : user.deleted_at}">
            <div v-if="user.deleted_at != null" class="badge badge-neutral badge-outline mb-2">cancellato</div>
            <div class="flex flex-wrap items-center justify-start gap-2">
                <font-awesome-icon v-if="user.is_admin" :icon="['fas', 'user-tie']" class="text-xl" />
                <span class="text-lg font-bold">{{ user.name }} {{ user.surname }}</span>
                &nbsp;|&nbsp;<span class="text-lg">{{ user.role }}</span>
            </div>

        </div>

        <section>
            <div class="flex flex-wrap items-center gap-2">
                <button v-if="user.role != 'warehouse_worker'" type="button" :disabled="!!pendingMail[user.id]" :aria-busy="pendingMail[user.id] === 'verification' || undefined" @click="resendVerification(user.id)" class="btn btn-primary btn-outline">
                    Reinvia verifica email
                </button>

                <button v-if="user.role != 'warehouse_worker'" type="button" :disabled="!!pendingMail[user.id]" :aria-busy="pendingMail[user.id] === 'password' || undefined" @click="sendPasswordReset(user.id)" class="btn btn-primary btn-outline">
                    Invia reset password
                </button>
                <a
                    class="btn btn-circle btn-outline btn-primary"
                    :href="route('user.show', {user: user.id})"
                 aria-label="Apri scheda utente">
                    <font-awesome-icon :icon="['fas', 'file-lines']" class="text-xl stroke-current" />
                </a>
                 <!-- EDIT  -->
                <Link
                    class="btn btn-circle btn-primary btn-outline"
                    :href="route('user.edit', {user: user.id})"
                 aria-label="Modifica utente">
                    <font-awesome-icon :icon="['fas', 'pencil']" class="text-xl stroke-current" />
                </Link>
                 <!-- DELETE -->
                <RegistryDeleteAction v-if="!user.deleted_at" :label="[user.name, user.surname].filter(Boolean).join(' ')" :busy="deleting === route('user.destroy', {user: user.id})" @confirm="removeRecord(route('user.destroy', {user: user.id}))" />
                <Link
                    v-else
                    class="btn btn-circle btn-primary btn-outline"
                    :href="route('user.restore', {user: user.id})"
                    as="button" method="put"
                 aria-label="Ripristina utente">
                    <font-awesome-icon :icon="['fas', 'trash-can-arrow-up']" class="text-xl stroke-current"  />
                </Link>

            </div>

        </section>
    </div>
</Box>
</div>



<section v-if="props.users.data.length" class="w-full flex justify-center mt-4 mb-4">
    <Pagination :links="users.links" />
</section>


<p v-if="!props.users.data.length" class="py-4">Nessun utente da visualizzare.</p>
</RegistryPage>
</template>

<script setup>
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()

import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import AppAction from '@/Components/UI/AppAction.vue'
import { computed, reactive } from 'vue';
import Box from '@/Components/UI/Box.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Link } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import axios from 'axios';

const props = defineProps({
    users: Object,
    filters: Object,
})

const store = useStore();
const currentSortBy = computed(() => props.filters?.sort_by ?? 'surname');
const currentSortDir = computed(() => props.filters?.sort_dir ?? 'asc');
const sortDirectionLabel = computed(() => (currentSortDir.value === 'desc' ? 'desc' : 'asc'));

const isSortedBy = (key) => currentSortBy.value === key;

const sortParams = (key) => {
    const nextDir = currentSortBy.value === key && currentSortDir.value === 'asc' ? 'desc' : 'asc';
    return {
        ...props.filters,
        sort_by: key,
        sort_dir: nextDir,
    };
};

const pendingMail = reactive({})
const sendMail = async (userId, kind) => {
    if (pendingMail[userId]) return
    pendingMail[userId] = kind
    try {
        const endpoint = kind === 'verification' ? 'resend-verification' : 'send-password-reset'
        const response = await axios.post(`/api/user/${endpoint}/${userId}`)
        store.dispatch('flash/queueMessage', {type:response.data.type, text:response.data.message})
    } catch {
        store.dispatch('flash/queueMessage', {type:'error', text:'Invio email non riuscito. Riprova.'})
    } finally { delete pendingMail[userId] }
}
const resendVerification = id => sendMail(id, 'verification')
const sendPasswordReset = id => sendMail(id, 'password')

</script>
