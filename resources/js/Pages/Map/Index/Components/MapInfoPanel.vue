<template>
    <div class="w-full pb-16">
        <div v-if="currentSite">
            <div class="text-lg"> 
                {{ currentSite.customer.ragioneSociale }} - {{ currentSite.denominazione }} <br/>
                <CustomerAddress :customer="currentSite.customer"   />
            </div>
            <EditableTimetable :site="currentSite" class="mt-4"/>
            {{ currentSite }}
        </div>
        <EmptyState v-else>Nessuna Info recente</EmptyState>
    </div>
</template>

<script setup>

import EmptyState from '@/Components/UI/EmptyState.vue';
import Box from '@/Components/UI/Box.vue';
import { computed, reactive, ref } from 'vue';
import { useStore } from 'vuex';
import EditableTimetable from '@/Pages/Relator/Customer/Components/EditableTimetable.vue';
import CustomerAddress from '@/Components/CustomerAddress.vue';

const store = useStore();
const currentSite = computed(() => store.state.currentSite);

</script>