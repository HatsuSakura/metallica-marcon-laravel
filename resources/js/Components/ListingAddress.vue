<template>
    <span>
        {{ listing.street }} {{ listing.street_nr }},{{ listing.city }}
    </span>
</template>
    
    <script setup>
    const props = defineProps({
        listing: Object,
    })
    </script>