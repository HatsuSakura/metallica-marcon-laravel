<template>
    <RegistryPage title="Clienti">
        <template #actions><AppAction :as="Link" variant="function" :href="route('customer.create')"><font-awesome-icon :icon="['fas', 'plus']" aria-hidden="true" />Nuovo cliente</AppAction></template>
        <CustomerFilters :filters="filters" />
        <IndexDataTableFullLocal v-if="Array.isArray(customers) && customers.length" :customers="customers" />
        <section v-else-if="customers.data?.length" class="grid grid-cols-1 gap-2"><IndexQueryPaginationBackend :customers="customers.data" /></section>
        <EmptyState v-else>Nessun cliente trovato. Modifica i filtri o aggiungi un nuovo cliente.</EmptyState>
        <Pagination v-if="customers.links?.length" :links="customers.links" />
    </RegistryPage>
</template>
<script setup>
import {Link} from '@inertiajs/vue3'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import EmptyState from '@/Components/UI/EmptyState.vue'
import CustomerFilters from './Index/Components/CustomerFilters.vue'
import IndexDataTableFullLocal from './Index/Components/IndexDataTableFullLocal.vue'
import IndexQueryPaginationBackend from './Index/Components/IndexQueryPaginationBackend.vue'
defineProps({filters:Object, customers:[Object,Array]})
</script>
