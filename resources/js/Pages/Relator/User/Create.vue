<template>

    <div class="flex mb-4">
        <Link
            :href="route('relator.user.index')"
            method="get"
            as="button"
            class="btn btn-ghost"
            >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-2xl"/>
            Annulla e torna all'elenco utenti
        </Link>
    </div>

    <form @submit.prevent="register">
        <div class="flex flex-col gap-1 w-2/3 mx-auto">
            <div>
                <label for="name" class="label">
                    Nome
                    <span class="input-error" v-if="form.errors.name">{{ form.errors.name }}</span>
                </label>
                <input id="name" v-model="form.name" type="text" class="input" @change="setUserCode">
            </div>

            <div>
                <label for="surname" class="label flex-1">
                    Cognome
                    <span class="input-error" v-if="form.errors.surname">{{ form.errors.surname }}</span>
                </label>
                <input id="surname" v-model="form.surname" type="text" class="input" @change="setUserCode">
            </div>

            <div class="flex flex-row justify-between items-stretch">
                <div>
                    <label class="label" for="role">
                        Ruolo
                    </label>
                    <select v-model="form.role" class="select select-bordered" id="role" required  @change="setUserCode">
                        <option v-for="role in props.roles" :key="role.key" :value="role.value">
                        {{ role.key }}
                        </option>
                    </select>
                </div>

                <!-- Warehouses (Multiple Choice) -->
                <div v-if="showWarehouseSelector" for="warehouse_ids">
                    <label class="label">Operativo in</label>
                    <div class="flex flex-col gap-1">
                    <div v-for="warehouse in props.warehouses" :key="warehouse.id">
                        <label class="inline-flex items-center">
                        <input
                            type="checkbox"
                            v-model="form.warehouse_ids"
                            :value="warehouse.id"
                            class="checkbox mr-2"
                        />
                        <span>{{ warehouse.denominazione }}</span>
                        </label>
                    </div>
                    </div>
                </div>

                <div v-if="showRagnista">
                    <label class="label" for="is_ragnista">
                        Ragnista
                    </label>
                    <input v-model="form.is_ragnista" id="is_ragnista" type="checkbox" class="toggle" />
                </div>

                <div>
                    <label for="user_code" class="label flex-1">
                        Codice Breve
                        <span class="input-error" v-if="form.errors.user_code">{{ form.errors.user_code }}</span>
                    </label>
                    <input id="user_code" v-model="form.user_code" type="text" class="input" disabled>
                </div>

                <div v-if="user.is_admin && showEmailAndPassword">
                    <label class="label" for="role">
                        Admin
                    </label>
                    <input v-model="form.is_admin" id="is_admin" type="checkbox" class="toggle" />
                </div>
            </div>
            
            <div v-if="showEmailAndPassword">
                <label for="email" class="label whitespace-nowrap">
                    E-mail
                    <span class="input-error" v-if="form.errors.email">{{ form.errors.email }}</span>
                </label>
                <input id="email" v-model="form.email" type="text" class="input">
                
            </div>
            
            <div v-if="showEmailAndPassword">
                <label for="password" class="label whitespace-nowrap">
                    Password
                    <span class="input-error" v-if="form.errors.password">{{ form.errors.password }}</span>
                </label>
                <input id="password" v-model="form.password" type="password" class="input">
            </div>

            <div v-if="showEmailAndPassword">
                <label for="password_confirmation" class="label whitespace-nowrap">Conferma Password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="input">
            </div>

            <div>
                <button class="btn btn-primary w-full mt-4 text-lg" type="submit">
                    <font-awesome-icon :icon="['fas', 'user-plus']" />
                    Crea Utente
                </button>
            </div>
        </div>
    </form>
    
    </template>
    
    <script setup>
    import { computed, ref, watch, useAttrs, onMounted, onUnmounted } from 'vue'
    import {useForm, usePage, Link} from '@inertiajs/vue3'
    const props = defineProps({
        roles: Array,
        warehouses: Array,
    })

    const page = usePage()
    const user = computed(
        () => page.props.user
    )

    const showRagnista = computed(() =>
        ['warehouse_worker', 'warehouse_manager'].includes(form.role)
    )

    // A computed property to decide if the warehouse checkbox list should be shown.
    // You can adjust the roles based on your business logic.
    const showWarehouseSelector = computed(() =>
        ['warehouse_worker', 'warehouse_manager', 'warehouse_chief'].includes(form.role)
    )

    const showEmailAndPassword = computed(() =>
        !['warehouse_worker'].includes(form.role)
    )
    

    const form = useForm({
        name: null,
        surname: null,
        email: null,
        password:null,
        password_confirmation:null,
        role: null,
        warehouse_ids: [],
        is_ragnista: false,
        user_code: null,
        is_admin: false,
    })
    
    const setUserCode = () => {
        if (form.name && form.surname && form.role){
            form.user_code = (form.name.substring(0, 1) + form.surname.substring(0, 1) + form.role.substring(0, 1)).toUpperCase()
        }
    }

    const register = () => {
        if (!showRagnista.value) {
            form.is_ragnista = false;
        }
        if (!showWarehouseSelector.value) {
            form.warehouse_ids = [];
        }
        if (!showEmailAndPassword.value) {
            delete form.email;
            delete form.password;
            delete form.password_confirmation;
        }
        form.post(route('relator.user.store'));
    }
    </script>
    