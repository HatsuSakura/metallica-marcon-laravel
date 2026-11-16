<template><SiteEquipmentSummary :site="site" :include-adr="false" show-other-machines /></template>
<script setup>
import SiteEquipmentSummary from '@/Components/Site/SiteEquipmentSummary.vue'
defineProps({site:Object})
</script>
