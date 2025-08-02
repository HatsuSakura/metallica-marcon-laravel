<template>
    <div class="flex flex-col md:flex-row gap-2 md:items-center justify-between">
        <div>
            <div><Address :indirizzo="site.indirizzo" /></div>
        </div>
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col items-center justify-between gap-2">
                <div class="text-2xl"><font-awesome-icon icon="triangle-exclamation" /></div>
                <div class="flex">{{ 100 * site.fattore_rischio_calcolato }}%</div>
            </div>
            <div class="flex flex-col items-center justify-between gap-2">
                <div class="text-2xl"><font-awesome-icon icon="calendar-day" /></div>
                <div class="flex">{{ site.giorni_prossimo_ritiro }}</div>
            </div>
        </div>
        
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Address from './Address.vue';
const props = defineProps({
    site: Object,
})


</script>
