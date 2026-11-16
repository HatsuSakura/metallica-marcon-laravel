<template>
<RegistryPage title="Automezzi">
<template #actions><AppAction :as="Link" variant="function" :href="route('vehicle.create')"><font-awesome-icon :icon="['fas', 'plus']" aria-hidden="true" />Nuovo automezzo</AppAction></template>




<section>

    <div class="grid grid-cols-1 gap-2 lg:grid-cols-2">

    <Box padding="px-3 py-2 sm:px-4" v-for="vehicle in props.vehicles.data" :key="vehicle.id" class="min-w-0 break-words">
        <div class="flex flex-row justify-between mb-2">
            <div>
                <div class="font-medium text-lg">
                    {{ vehicle.name }} | {{ vehicle.plate }}
                </div>
                <div class="text-base-content/75">
                    {{ vehicle.description }}
                </div>

                <font-awesome-icon :icon="['fas', 'user']" class="text-2xl"/>
                Autista abituale:
                    <span v-if="vehicle.usual_driver">{{ vehicle.usual_driver.name }} {{ vehicle.usual_driver.surname }}</span>
                    <span v-else>Non assegnato</span>
                    <br/>

                <font-awesome-icon :icon="['fas', 'trailer']" class="text-2xl"/>
                Rimorchio abituale:
                    <span v-if="vehicle.preferred_trailer">{{ vehicle.preferred_trailer.plate }}</span>
                    <span v-else>Non assegnato</span>
                    <br/>

                <font-awesome-icon :icon="['fas', 'screwdriver-wrench']" class="text-2xl"/>
                Tipologia: <span class="font-bold"> {{ vehicle.type }} </span>
                    <span v-if="!vehicle.has_trailer" class="text-error"> non può </span>
                    <span v-else class="text-success"> può </span>
                    trainare rimorchi <br/>
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2">
            <Link
            :href="route('vehicle.edit', {vehicle: vehicle.id} )"
            method="get"
            as="button"
            class="btn btn-circle btn-primary btn-outline"
             aria-label="Modifica automezzo">
                <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
            </Link>

            <RegistryDeleteAction :label="vehicle.name" :busy="deleting === route('vehicle.destroy', {vehicle: vehicle.id} )" @confirm="removeRecord(route('vehicle.destroy', {vehicle: vehicle.id} ))" />
        </div>
    </Box>

    </div>
</section>




<p v-if="!props.vehicles.data.length" class="py-4">Nessun elemento da visualizzare.</p>
<Pagination v-if="props.vehicles.links?.length" :links="props.vehicles.links" />
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
    vehicles : Object,
})

</script>
