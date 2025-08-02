<template>

<!--
{{ props.customers }}
-->

<Box v-for="customer in props.customers" :key="customer.id" :class="{'border-dashed' : customer.deleted_at}">
    <div class="flex flex-col md:flex-row gap-2 md:items-center justify-between">
        <div :class="{'opacity-25' : customer.deleted_at}">
            <div v-if="customer.deleted_at != null" class="text-xs font-bold uppercase border border-dashed p-1 border-green-300 text-green-500 dark:border-green-600 dark:text-green-600 inline-block rounded-md mb-2">cancellato</div>
            <div class=""> 
                <span class="text-lg font-bold">{{ customer.ragione_sociale }}</span> 
                <CustomerSpace :customer="customer"/>
            </div>
            <CustomerAddress :customer="customer" class="text-gray-500" />
            
        </div>

        <section>
            <div class="flex items-center gap-1 text-gray-600">
                <!-- SCHEDA -->
                <!--
                <a 
                    class="btn btn-circle btn-outline btn-primary" 
                    :href="route('relator.customer.show', {customer: customer.id})"
                    target="_blank"
                >
                -->
                <a 
                    class="btn btn-circle btn-outline btn-primary" 
                    :href="route('relator.customer.show', {customer: customer.id})"
                    @click="selectSite(props.site)" 
                >
                    <font-awesome-icon :icon="['fas', 'file-lines']" class="h-5 w-5 stroke-current" />
                </a>
                 <!-- EDIT  -->
                <Link 
                    class="btn btn-circle btn-outline" 
                    :href="route('relator.customer.edit', {customer: customer.id})"
                >
                    <font-awesome-icon :icon="['fas', 'pencil']" class="h-5 w-5 stroke-current" />
                </Link>
                 <!-- DELETE -->
                <Link 
                    v-if="!customer.deleted_at"
                    class="btn btn-circle btn-outline btn-error" 
                    :href="route('relator.customer.destroy', {customer: customer.id})"
                    as="button" method="delete"
                >
                <font-awesome-icon :icon="['fas', 'trash-can']" class="h-5 w-5 stroke-current" />
                </Link>
                <Link 
                    v-else
                    class="btn btn-circle btn-outline btn-success" 
                    :href="route('relator.customer.restore', {customer: customer.id})"
                    as="button" method="put"
                >
                    <font-awesome-icon :icon="['fas', 'trash-can-arrow-up']" />
                </Link>
            </div>

        </section>
    </div>
</Box>

</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import Box from '@/Components/UI/Box.vue';
import CustomerSpace from '@/Components/CustomerSpace.vue';
import CustomerAddress from '@/Components/CustomerAddress.vue';
import { useStore } from 'vuex';

const props = defineProps({ 
    customers: Object,
})



// Access the Vuex store
const store = useStore();

// Method to select the current site
const selectSite = (site) => {
    store.dispatch('setCurrentSite', site); // Dispatch action to set current site
};

</script>




