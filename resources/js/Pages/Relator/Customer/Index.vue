<template>

<div class="navbar bg-base-100">
            <div class="navbar-start">
                <div class="dropdown">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </div>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                        <li>
                            <Link @click="selectSite(props.site)" :href="route('relator.customer.create')">
                            Nuovo Cliente
                            </Link>
                        </li>
                        <li><a>altro</a></li>
                        <li><a>altro</a></li>
                    </ul>
                </div>
            </div>
            <div class="navbar-center">
                <span class="text-xl font-bold mr-16">Gestione Clienti</span>
                <RelatorCustomerFilters :filters="filters" />
            </div>
            <div class="navbar-end space-x-2">
                <Link @click="selectSite(props.site)" :href="route('relator.customer.create')"
                    class="btn btn-circle btn-primary">
                <font-awesome-icon :icon="['fas', 'plus']" class="h-5 w-5" stroke="currentColor" />
                </Link>
                <button class="btn btn-ghost btn-circle">
                    <font-awesome-icon :icon="['fas', 'cog']" class="h-5 w-5" stroke="currentColor" />
                </button>
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
import RelatorCustomerFilters from './Index/Components/RelatorCustomerFilters.vue';
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
