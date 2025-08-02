<template>
<div>

<h2>
    Il mio Profilo Utente
</h2>


        <div class="flex flex-row gap-4 w-2/3 mx-auto">
            <div class="flex flex-col gap-1 w-3/4">
                <div class="flex flex-row gap-2">
                    <div class="w-1/2">
                        <label for="name" class="label">
                            Nome
                        </label>
                        <div class="input">
                            {{ props.user.name }}
                        </div>
                    </div>
                    <div class="w-1/2">
                        <label for="surname" class="label">
                            Cognome
                        </label>
                        <div class="input">
                            {{ props.user.surname }}
                        </div>
                    </div>
                </div>

                <div>
                    <label for="email" class="label whitespace-nowrap">
                        E-mail
                    </label>
                    <input id="email" v-model="props.user.email" type="text" class="input">
                </div>

                <div class="flex flex-row justify-between content-stretch">
                    <div>
                        <label class="label" for="role">
                            Ruolo
                        </label>
                        <div class="input">
                            {{ props.user.role }}
                        </div>
                    </div>



                    <!-- Warehouses (Multiple Choice) -->
                    <div v-if="showWarehouseSelector">
                        <label class="label" for="warehouse_ids">Operativo in</label>
                        <div class="flex flex-col gap-1">
                            <div v-for="warehouse in props.warehouses" :key="warehouse.id">
                                <label class="inline-flex items-center">
                                <!-- 
                                    Use :checked to check if the warehouse id exists in the computed assignedWarehouseIds. 
                                    The input is disabled so that it cannot be edited.
                                -->
                                <input
                                    type="checkbox"
                                    :value="warehouse.id"
                                    :checked="assignedWarehouseIds.includes(warehouse.id)"
                                    class="checkbox mr-2"
                                    disabled
                                />
                                <span>{{ warehouse.denominazione }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                
                    <div v-if="showRagnista">
                        <label class="label" for="role">
                            Ragnista
                        </label>
                        <div class="input">
                            {{ (Boolean(props.user.is_ragnista))? 'SI' : 'NO' }}
                        </div>
                    </div>

                    <div v-if="props.user.role === 'customer'">
                        <label class="label" for="role">
                            ID Customer collegato
                        </label>
                        <div class="input">
                            {{ props.user.customer_id }}
                        </div>
                    </div>


                    <div>
                        <label for="user_code" class="label flex-1">
                            Codice Breve
                        </label>
                        <div class="input">
                            {{ props.user.user_code }}
                        </div>
                    </div>

                    <div>
                        <label class="label" for="role">
                            Admin
                        </label>
                        <div class="input">
                            {{ (Boolean(props.user.is_admin))? 'SI' : 'NO' }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-row gap-2">
                    <div class="w-1/3">
                        <label for="name" class="label">
                            System ID
                        </label>
                        <div class="input">
                            {{ props.user.id }}
                        </div>
                    </div>
                    <div class="w-1/3">
                        <label for="surname" class="label">
                            Creato
                        </label>
                        <div class="input">
                            {{ dayjs(props.user.created_at).format('YYYY-MM-DD HH:mm:ss') }}
                        </div>
                    </div>
                    <div class="w-1/3">
                        <label for="surname" class="label">
                            Ultima modifica
                        </label>
                        <div class="input">
                            {{ dayjs(props.user.updated_at).format('YYYY-MM-DD HH:mm:ss') }}
                        </div>
                    </div>
                </div>
                
            </div>

            <div class="flex flex-col gap-1 w-1/4">
                <div v-if="props.user.avatar">
                    <img :src="props.user.avatar" class="rounded-full mx-auto" alt="Avatar non caricato" />
                </div>
                <EmptyState v-else>
                    Nessun avatar disponibile
                </EmptyState>
            </div>

        </div>
        <div class="flex flex-row justify-end">
            <Link
                :href="route('user-account.edit', props.user.id)"
                class="btn btn-primary"
            >
                <font-awesome-icon :icon="['fas', 'pencil']" class="text-2xl"/>
                Modifica
            </Link>
        </div>

</div>

</template>

<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Link } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import { computed } from 'vue';
const props = defineProps({
    user: Object,
    warehouses: Array,
});

    const showRagnista = computed(() =>
        ['warehouse_worker', 'warehouse_manager'].includes(props.user.role)
    )

    // A computed property to decide if the warehouse checkbox list should be shown.
    // You can adjust the roles based on your business logic.
    const showWarehouseSelector = computed(() =>
        ['warehouse_worker', 'warehouse_manager', 'warehouse_chief'].includes(props.user.role)
    )

    // Compute a simple array of warehouse IDs already assigned to the user.
    const assignedWarehouseIds = computed(() => {
        return props.user.warehouses.map(w => w.id);
    });

</script>