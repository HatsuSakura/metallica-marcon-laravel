<template>
    <div>
        <span class="font-bold">{{listing.beds}}</span> bds <span class="tex-gay-400">|</span>
        &nbsp;<span class="font-bold">{{listing.baths}}</span> baths <span class="tex-gay-400">|</span>
        &nbsp;<span class="font-bold">{{listing.area}}</span> ㎡
    </div>
</template>

<script setup>
const props = defineProps({
    listing: Object,
})
</script>
