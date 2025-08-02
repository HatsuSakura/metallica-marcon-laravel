<template>
    <Filters :filters="filters" />
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
        <Listing v-for="listing in listings.data" v-bind:key="listing.id" :listing="listing"/>
    </div>

    <div v-if="listings.data.length" class="w-ful flex justify-center mt-4 mb-4">
        <Pagination :links="listings.links" />
    </div>
</template>
    
    <script setup>
    import Listing from '@/Pages/Listing/Index/Components/Listing.vue';
    import Pagination from '@/Components/UI/Pagination.vue';
    import Filters from '@/Pages/Listing/Index/Components/Filters.vue';

    defineProps({
        listings: Object,
        filters: Object,
    })
    </script>