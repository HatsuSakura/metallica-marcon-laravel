<template>
<RegistryPage title="Rimorchi">
<template #actions><AppAction :as="Link" variant="function" :href="route('trailer.create')"><font-awesome-icon :icon="['fas', 'plus']" aria-hidden="true" />Nuovo rimorchio</AppAction></template>




<section>

    <div class="grid grid-cols-1 gap-2 lg:grid-cols-2">

    <Box padding="px-3 py-2 sm:px-4" v-for="trailer in props.trailers.data" :key="trailer.id" class="min-w-0 break-words">
        <div class="flex flex-row justify-between mb-2">
            <div>
                <div class="font-medium text-lg">
                    {{ trailer.name }} | {{ trailer.plate }}
                </div>
                <div class="text-base-content/75">
                    {{ trailer.description }}
                </div>

                <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl"/>
                Capacità di carico:
                    <span class="font-medium">{{ trailer.load_capacity }} Kg</span> <span> [= {{ trailer.load_capacity / 1000 }} ton.]</span>
                <br/>

                <font-awesome-icon :icon="['fas', 'arrows-left-right']" class="text-2xl"/>
                Verso del caricamento:
                    <span v-if="trailer.is_front_cargo" class="font-medium">Lato Motrice </span>
                    <span v-else  class="font-medium">Lato posteriore</span>
                <br/>

            </div>
        </div>

        <div class="flex flex-row justify-end gap-2">
            <Link
            :href="route('trailer.edit', {trailer: trailer.id} )"
            method="get"
            as="button"
            class="btn btn-circle btn-primary btn-outline"
             aria-label="Modifica rimorchio">
                <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
            </Link>

            <RegistryDeleteAction :label="trailer.name" :busy="deleting === route('trailer.destroy', {trailer: trailer.id} )" @confirm="removeRecord(route('trailer.destroy', {trailer: trailer.id} ))" />
        </div>
    </Box>

    </div>

</section>




<p v-if="!props.trailers.data.length" class="py-4">Nessun elemento da visualizzare.</p>
<Pagination v-if="props.trailers.links?.length" :links="props.trailers.links" />
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
    trailers : Object,
})

</script>
