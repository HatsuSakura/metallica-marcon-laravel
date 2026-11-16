<template>
<RegistryPage title="Cassoni">
<template #actions><AppAction :as="Link" variant="function" :href="route('cargo.create')"><font-awesome-icon :icon="['fas', 'plus']" aria-hidden="true" />Nuovo cassone</AppAction></template>




<section>
    <div class="grid grid-cols-1 gap-2 lg:grid-cols-2">

    <Box padding="px-3 py-2 sm:px-4" v-for="cargo in props.cargos.data" :key="cargo.id" class="min-w-0 break-words">
        <div class="flex flex-row justify-between mb-2">
            <div>
                <div class="font-medium text-lg">
                    {{ cargo.name }} <span v-if="!cargo.is_cargo"><font-awesome-icon :icon="['fas', 'truck']" />&nbsp;</span>
                    <span>[{{ cargo.total_count }}]</span>
                </div>
                <div class="text-base-content/75">
                    {{ cargo.description }}
                </div>
                <div>
                    Dimensione: {{ cargo.length }} (m) |
                    <span v-if="cargo.is_long">LUNGO</span>
                    <span v-else>CORTO</span>
                </div>
                <div>
                    Casse: {{ cargo.casse }}
                    | Spazi casse: {{ cargo.spazi_casse }}
                    | Spazi bancali: {{ cargo.spazi_bancale }}
                </div>
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2">
            <Link
            :href="route('cargo.edit', {cargo: cargo.id} )"
            method="get"
            as="button"
            class="btn btn-circle btn-primary btn-outline"
             aria-label="Modifica cassone">
                <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
            </Link>

            <RegistryDeleteAction :label="cargo.name" :busy="deleting === route('cargo.destroy', {cargo: cargo.id} )" @confirm="removeRecord(route('cargo.destroy', {cargo: cargo.id} ))" />
        </div>
    </Box>
    </div>
</section>




<p v-if="!props.cargos.data.length" class="py-4">Nessun elemento da visualizzare.</p>
<Pagination v-if="props.cargos.links?.length" :links="props.cargos.links" />
</RegistryPage>
</template>

<script setup>
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()

import AppAction from '@/Components/UI/AppAction.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    cargos : Object,
})

</script>
