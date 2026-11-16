<template>
    <RegistryPage title="Scheda utente">
        <template #actions><AppAction :as="Link" :href="route('user.index')">Torna agli utenti</AppAction><AppAction :as="Link" variant="function" :href="route('user.edit', {user:props.user.id})">Modifica utente</AppAction></template>
        <div class="flex flex-col gap-4 lg:flex-row">
            <dl class="grid min-w-0 flex-1 grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in details" :key="field.label" class="min-w-0"><dt class="text-sm font-semibold">{{ field.label }}</dt><dd class="mt-1 break-words">{{ field.value ?? '—' }}</dd></div>
                <div v-if="showWarehouseSelector" class="sm:col-span-2"><dt class="text-sm font-semibold">Operativo in</dt><dd>{{ props.user.warehouses?.map(w => w.name).join(', ') || 'Nessun magazzino assegnato' }}</dd></div>
            </dl>
            <img v-if="props.user.avatar" :src="props.user.avatar" class="h-24 w-24 rounded-full object-cover" :alt="`Avatar di ${props.user.name}`" />
        </div>
    </RegistryPage>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import { computed } from 'vue';
import Box from '@/Components/UI/Box.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';


const props = defineProps({
    user: Object,
    warehouses: Array,
})


    const showCraneOperator = computed(() =>
        ['warehouse_worker', 'warehouse_manager'].includes(props.user.role)
    )

    // A computed property to decide if the warehouse checkbox list should be shown.
    // You can adjust the roles based on your business logic.
    const showWarehouseSelector = computed(() =>
        ['warehouse_worker', 'warehouse_manager', 'warehouse_chief'].includes(props.user.role)
    )

    const showEmailAndPassword = computed(() =>
        !['warehouse_worker'].includes(props.user.role)
    )

    // Compute a simple array of warehouse IDs already assigned to the user.
    const details = computed(() => [
        {label:'Nome',value:props.user.name}, {label:'Cognome',value:props.user.surname},
        ...(showEmailAndPassword.value ? [{label:'Email',value:props.user.email}] : []),
        {label:'Ruolo',value:props.user.role}, {label:'Codice breve',value:props.user.user_code},
        {label:'Amministratore',value:props.user.is_admin ? 'Sì' : 'No'},
        ...(showCraneOperator.value ? [{label:'Operatore ragno',value:props.user.is_crane_operator ? 'Sì' : 'No'}] : []),
        ...(props.user.role === 'customer' ? [{label:'ID cliente collegato',value:props.user.customer_id}] : []),
        {label:'ID utente',value:props.user.id},
        {label:'Creato',value:props.user.created_at ? dayjs(props.user.created_at).format('DD/MM/YYYY HH:mm') : '—'},
        {label:'Ultima modifica',value:props.user.updated_at ? dayjs(props.user.updated_at).format('DD/MM/YYYY HH:mm') : '—'},
    ]);
</script>

