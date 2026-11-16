<script setup>
import {Link} from '@inertiajs/vue3'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()
defineProps({items:Object})
</script>
<template>
    <RegistryPage title="Materiali e componenti">
        <template #actions><AppAction :as="Link" variant="function" :href="route('catalog-items.create')"><font-awesome-icon :icon="['fas','plus']" aria-hidden="true" />Nuovo elemento</AppAction></template>
        <div class="divide-y divide-base-300">
            <article v-for="item in items.data" :key="item.id" class="flex min-w-0 flex-col justify-between gap-3 py-3 lg:flex-row lg:items-center">
                <div class="min-w-0"><h2 class="break-words font-semibold">{{ item.name }}</h2><span class="badge badge-neutral badge-outline">{{ item.type === 'material' ? 'Materiale' : 'Componente' }}</span></div><div class="flex flex-wrap items-center gap-2"><AppAction v-if="item.type === 'component'" :as="Link" variant="function" :href="route('catalog-items.recipe.edit', {item:item.id,from:'catalog'})">Gestisci ricetta</AppAction><AppAction :as="Link" variant="function" :href="route('catalog-items.edit',item.id)">Modifica</AppAction><RegistryDeleteAction :label="item.name" :busy="deleting === route('catalog-items.destroy',item.id)" @confirm="removeRecord(route('catalog-items.destroy',item.id))" /></div>
            </article>
        </div>
        <p v-if="!items.data.length" class="py-4">Nessun elemento presente. Usa “Nuovo elemento” per aggiungerlo.</p>
        <Pagination v-if="items.links?.length" :links="items.links" />
    </RegistryPage>
</template>
