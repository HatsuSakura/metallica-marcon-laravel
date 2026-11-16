<template>
<RegistryPage title="Contenitori">
<template #actions><AppAction :as="Link" variant="function" :href="route('holder.create')"><font-awesome-icon :icon="['fas', 'plus']" aria-hidden="true" />Nuovo contenitore</AppAction></template>

<section class="flex flex-wrap justify-between items-center gap-3 mb-4">
    <div class="flex min-w-0 flex-wrap items-center gap-2">
        <label for="holder-search" class="font-medium">Cerca contenitori</label>
        <input
            id="holder-search" type="search"
            class="input"
            placeholder="Cerca per nome"
            v-model.trim="search"
        />
        <button type="button" class="btn btn-outline" @click="search = ''">Azzera ricerca</button>
    </div>

</section>

<section>
    <div class="grid grid-cols-1 gap-2 lg:grid-cols-2">
        <Box padding="px-3 py-2 sm:px-4" v-for="holder in filteredHolders" :key="holder.id" class="min-w-0 break-words">
            <div class="flex flex-row justify-between mb-2">
                <div>
                    <div class="font-medium text-lg">
                        {{ holder.name }}
                    </div>
                    <div class="text-base-content/75">
                        {{ holder.description }}
                    </div>
                    <div>
                        Volume: {{ holder.volume ?? '-' }} (m3)
                    </div>
                    <div
                        v-if="holder.equivalent_holder_id && holder.equivalent_holder_id !== holder.id && holder.equivalent_holder"
                    >
                        Equivalenza: {{ holder.equivalent_units ?? 1 }} x {{ holder.equivalent_holder.name }}
                    </div>
                </div>
            </div>

            <div class="flex flex-row justify-end gap-2">
                <Link
                    :href="route('holder.edit', { holder: holder.id })"
                    method="get"
                    as="button"
                    class="btn btn-circle btn-primary btn-outline"
                 aria-label="Modifica contenitore">
                    <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
                </Link>

                <RegistryDeleteAction :label="holder.name" :busy="deleting === route('holder.destroy', { holder: holder.id })" @confirm="removeRecord(route('holder.destroy', { holder: holder.id }))" />
            </div>
        </Box>
    </div>
</section>

<p v-if="!filteredHolders.length" class="py-4">Nessun elemento da visualizzare.</p>
<Pagination v-if="props.holders.links?.length" :links="props.holders.links" />
</RegistryPage>
</template>

<script setup>
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()

import AppAction from '@/Components/UI/AppAction.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import { computed, ref } from 'vue'
import Box from '@/Components/UI/Box.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    holders: Object,
})

const search = ref('')

const filteredHolders = computed(() => {
    const list = props.holders?.data ?? []
    const term = search.value.toLowerCase()
    if (!term) return list
    return list.filter((holder) => {
        const name = (holder.name ?? '').toLowerCase()
        const description = (holder.description ?? '').toLowerCase()
        return name.includes(term) || description.includes(term)
    })
})
</script>
