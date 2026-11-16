<script setup>
import {Link} from '@inertiajs/vue3'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import Pagination from '@/Components/UI/Pagination.vue'
defineProps({recipes:Object})
</script>
<template>
    <RegistryPage title="Ricette">
        <template #actions><AppAction :as="Link" variant="function" :href="route('recipes.create')"><font-awesome-icon :icon="['fas','plus']" aria-hidden="true" />Nuova ricetta</AppAction></template>
        <div class="divide-y divide-base-300">
            <article v-for="item in recipes.data" :key="item.id" class="flex min-w-0 flex-col justify-between gap-3 py-3 lg:flex-row lg:items-center">
                <div class="min-w-0"><h2 class="break-words font-semibold">{{ item.name }}</h2><p class="text-sm">Versione {{ item.version }}</p></div><div class="flex flex-wrap items-center gap-2"><AppAction :as="Link" variant="function" :href="route('recipes.edit',{recipe:item.id,from:'recipes'})">Modifica</AppAction></div>
            </article>
        </div>
        <p v-if="!recipes.data.length" class="py-4">Nessun elemento presente. Usa “Nuova ricetta” per aggiungerlo.</p>
        <Pagination v-if="recipes.links?.length" :links="recipes.links" />
    </RegistryPage>
</template>
