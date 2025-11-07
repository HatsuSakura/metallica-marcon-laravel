<template>
    <div class="mb-4">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.customer.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna a Clienti
        </Link>

    </div>

    <section class="flex flex-col md:grid md:grid-cols-12 gap-4 mb-4">

        <!-- TESTATA SCHEDA CLIENTE -->
        <div class="md:col-span-12">
            <Box >
                <template #header>Scheda cliente</template>

                <div class="text-gray-500"> 
                    <span class="text-gray-900 text-lg font-bold">{{ reactiveCustomer.ragione_sociale }}</span> 
                    <CustomerSpace :customer="reactiveCustomer" class="text-gray-500" />
                </div>
                <CustomerAddress :customer="reactiveCustomer" class="text-gray-500" />
            </Box>
        </div>


<!--
        <Box v-if="!hasSites" class="flex md:col-span-3 items-center">
            <div class="w-full text-center font-medium text-gray-500">
                Nessuna Sede associata
            </div>
        </Box>
        <div v-else class="md:col-span-3 items-center">
            <Box v-for="site in customer.sites" :key="site.id" class="mb-4">
                <template #header>Sede {{ site.denominazione }}</template>
                <SiteSpace :site="site"/>
            </Box>
        </div>
    -->

    </section>

    <!-- SEZIONE TAB PER LE DIVERSE SEDI -->
    <section>
        <div role="tablist" class="tabs tabs-bordered">
            <TabSite 
                v-for="(site, index) in reactiveCustomer.sites" 
                :site="site"
                :areas="props.areas"
                :orders="(orders_by_site[site.id] ?? [])"
                @siteUpdated="updateSite"
                :counter="index" 
                :key="site.id"
            />
        </div>
    </section>

</template>

<script setup>

import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';
import { computed, ref, reactive, toRaw } from 'vue';
import Offer from '@/Pages/Relator/Show/Components/Offer.vue';
import CustomerSpace from '@/Components/CustomerSpace.vue';
import CustomerAddress from '@/Components/CustomerAddress.vue';
import SiteSpace from '@/Components/SiteSpace.vue';
import TabSite from './Components/TabSite.vue';


const props = defineProps({
    customer:Object,
    areas: Array,
    orders_by_site: { type: Object, default: () => ({}) }
})

const reactiveCustomer = reactive(props.customer);

const hasSites = computed(
    () => reactiveCustomer.sites.length,
)

const customerOccasionale = computed(
    () => reactiveCustomer.customerOccasionale_at,
)


// Update function
const updateSite = (updatedSite) => {
    console.log('Updating site:', updatedSite);

    // Find the index of the site to update
    const siteIndex = reactiveCustomer.sites.findIndex((site) => site.id === updatedSite.id);
    if (siteIndex !== -1) {
        // update the array merging modified values to original
        reactiveCustomer.sites[siteIndex] = { 
            // E' importantissimo il toRaw per evitare ridondanze cicliche che portano all'errore di INERTIA
            // Uncaught DataCloneError: Failed to execute 'replaceState' on 'History': #<Object> could not be cloned.
            ...toRaw(reactiveCustomer.sites[siteIndex]), 
            ...toRaw(updatedSite),
        };
    } else {
        console.warn('Site not found in customer.sites:', updatedSite.id);
    }
};


</script>