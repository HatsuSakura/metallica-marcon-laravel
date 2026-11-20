<template>
    <RegistryPage title="Codici CER">
        <template #actions><AppAction :as="Link" :href="route('cer-code.create')" variant="function"><font-awesome-icon :icon="['fas', 'plus']" aria-hidden="true" />Nuovo codice CER</AppAction></template>
        <form class="flex flex-wrap items-end gap-3" role="search" @submit.prevent="filter">
            <div class="min-w-0 flex-1 basis-64"><label class="label" for="cer-search">Codice o descrizione</label><input id="cer-search" v-model="search" type="search" class="input input-bordered" placeholder="Es. 170405 oppure ferro" maxlength="255" /></div>
            <div class="w-full sm:w-44"><label class="label" for="cer-status">Stato</label><select id="cer-status" v-model="status" class="select select-bordered" @change="filter"><option value="active">Attivi</option><option value="inactive">Disattivati</option><option value="all">Tutti</option></select></div>
            <AppAction type="submit">Filtra</AppAction>
        </form>
        <p class="text-sm" aria-live="polite">{{ codes.total }} codici CER trovati</p>
        <ul class="divide-y divide-base-300 border-y border-base-300">
            <li v-for="code in codes.data" :key="code.id" class="flex flex-wrap items-center gap-3 py-2">
                <div class="min-w-0 flex-1 basis-48">
                    <div class="flex flex-wrap items-center gap-2"><CerCodeBadge :code="code.code" :dangerous="code.is_dangerous" /><span v-if="!code.is_active" class="badge badge-neutral badge-outline">Disattivato</span></div>
                    <p class="mt-1 break-words text-sm leading-5">{{ code.description }}</p>
                </div>
                <AppAction :as="Link" :href="route('cer-code.edit', code.id)" variant="function" shape="circle" :aria-label="`Modifica codice CER ${code.code}`"><font-awesome-icon :icon="['fas', 'pencil']" aria-hidden="true" /></AppAction>
            </li>
        </ul>
        <p v-if="!codes.data.length" class="py-4 text-sm">Nessun codice CER corrisponde ai filtri. Prova un altro codice o seleziona «Tutti».</p>
        <Pagination v-if="codes.last_page > 1" :links="codes.links" />
    </RegistryPage>
</template>
<script setup>
import {ref} from 'vue'
import {Link, router} from '@inertiajs/vue3'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import CerCodeBadge from '@/Components/UI/CerCodeBadge.vue'
import Pagination from '@/Components/UI/Pagination.vue'
const props = defineProps({codes: {type: Object, required: true}, filters: {type: Object, required: true}})
const search = ref(props.filters.search)
const status = ref(props.filters.status)
const filter = () => router.get(route('cer-code.index'), {search: search.value, status: status.value}, {preserveState: true, replace: true})
</script>
