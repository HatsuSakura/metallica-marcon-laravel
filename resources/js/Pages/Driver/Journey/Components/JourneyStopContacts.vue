<template>
    <div v-if="stop && stop.kind !== 'technical'" class="space-y-3">
        <div v-for="(site, index) in sites" :key="site?.id ?? index">
            <p v-if="sites.length > 1" class="mb-2 text-sm font-semibold break-words">{{ site?.name || site?.address || 'Sede' }}</p>
            <SiteContactSummary :contacts="site?.internal_contacts ?? []" />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import SiteContactSummary from '@/Components/SiteContactSummary.vue';
import { stopSites } from './driverStopPresentation';
const props = defineProps({ stop: { type: Object, default: null } });
const sites = computed(() => stopSites(props.stop));
</script>
