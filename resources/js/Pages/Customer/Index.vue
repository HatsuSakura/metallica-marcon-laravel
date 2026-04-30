<template>

        <div class="flex items-center justify-between mb-2">
            <div>
                <span class="text-xl font-bold">Gestione Clienti</span>
            </div>
            <div>
                <CustomerFilters :filters="filters" />
            </div>
            <div>
                <Link @click="selectSite(props.site)" :href="route('customer.create')"
                    class="btn btn-circle btn-primary">
                <font-awesome-icon :icon="['fas', 'plus']" class="h-5 w-5" stroke="currentColor" />
                </Link>
                <!--
                <button class="btn btn-ghost btn-circle">
                    <font-awesome-icon :icon="['fas', 'cog']" class="h-5 w-5" stroke="currentColor" />
                </button>
                -->
            </div>
        </div>


  <section v-if="props.customers.length"  class="w-full mb-4">
    <IndexDataTableFullLocal :customers="customers"/>
  </section>


  <section v-if="props.customers.data.length" class="grid grid-cols-1 gap-2"> <!-- lg:grid-cols-2 -->
    <IndexQueryPaginationBackend :customers="customers.data"/>
  </section>
  <section v-if="props.customers.data.length" class="w-full flex justify-center mt-4 mb-4">
    <Pagination :links="customers.links" />
  </section>
  <EmptyState v-else>Nessun cliente da visualizzare</EmptyState>


</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Pagination from '@/Components/UI/Pagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import CustomerFilters from './Index/Components/CustomerFilters.vue';
import IndexDataTableFullLocal from './Index/Components/IndexDataTableFullLocal.vue';
import IndexQueryPaginationBackend from './Index/Components/IndexQueryPaginationBackend.vue';
import { useStore } from 'vuex';

const props = defineProps({ 
    filters: Object,
    customers: Object,
})


// Access the Vuex store
const store = useStore();

// Method to select the current site
const selectSite = (site) => {
    store.dispatch('setCurrentSite', site); // Dispatch action to set current site
};

</script>

