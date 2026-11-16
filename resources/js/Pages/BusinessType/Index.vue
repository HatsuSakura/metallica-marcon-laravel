<template>
<RegistryPage title="Tipologie attività">

    <template #actions><AppAction :as="Link" variant="function" :href="route('business-type.create')"><font-awesome-icon :icon="['fas','plus']" aria-hidden="true" />Nuova tipologia</AppAction></template>
<div class="overflow-x-auto">
        <table class="table w-full">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Descrizione</th>
                    <th class="text-right">Clienti</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="bt in businessTypes" :key="bt.id" class="hover">
                    <td class="font-medium">{{ bt.name }}</td>
                    <td class="text-base-content/70">{{ bt.description ?? '—' }}</td>
                    <td class="text-right">{{ bt.customers_count }}</td>
                    <td class="text-right space-x-2">
                        <Link :href="route('business-type.edit', bt.id)" class="btn btn-primary btn-outline">
                            <font-awesome-icon :icon="['fas', 'pen']" />
                        </Link>
<RegistryDeleteAction :label="bt.name" :disabled="bt.customers_count > 0" disabled-reason="Usata da clienti: non eliminabile" :busy="deleting === route('business-type.destroy', bt.id)" @confirm="removeRecord(route('business-type.destroy', bt.id))" />
                    </td>
                </tr>
                <tr v-if="!businessTypes.length">
                    <td colspan="4" class="text-center opacity-60">Nessuna tipologia registrata.</td>
                </tr>
            </tbody>
        </table>
    </div>



</RegistryPage>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import RegistryDeleteAction from '@/Components/Registry/RegistryDeleteAction.vue'
import {useRegistryDelete} from '@/Composables/useRegistryDelete'
const {deleting, removeRecord} = useRegistryDelete()

import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';

const props = defineProps({
    businessTypes: { type: Array, default: () => [] },
});

</script>
