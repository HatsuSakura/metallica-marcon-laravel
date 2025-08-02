<template>

    <!-- HEAD and BACK BUTTON -->
    <div class="mb-4 flex flex-row items-center gap-4 w-full">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.dashboard')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna a  Dashboard
        </Link>
        <div class="flex flex-row items-center justify-between w-full">
            <div class="text-lg font-medium">
                Contenuto cassone
            </div>
            <div>
                <JourneyCargoHead :journeyCargo="props.journeyCargo" :rows="2" class="mb-4"/>
            </div>
        </div>

    </div>

    
    
    <div v-for="item in props.journeyCargo.items">
        <Box class="mb-2">
            <ItemForItemList :item="item"/>
        </Box>
        
    </div>

</template>

<script setup>
import Box from '@/Components/UI/Box.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ItemForItemList from '@/Components/ItemForItemLists.vue'
import { Link } from '@inertiajs/vue3';
import ZeroPaddingId from '@/Components/UI/ZeroPaddingId.vue';
import JourneyCargoHead from '@/Components/JourneyCargoHead.vue';

const props = defineProps({
    journeyCargo: Object,
})
</script>