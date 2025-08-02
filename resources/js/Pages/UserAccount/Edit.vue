<template>

<div class="flex mb-4">
    <Link
        :href="route('user-account.index')"
        method="get"
        as="button"
        class="btn btn-ghost"
        >
        <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-2xl"/>
        Indietro senza salvare
    </Link>
</div>

    <form @submit.prevent="update">
        <div class="flex flex-row gap-8 w-2/3 mx-auto">

            <div class="flex flex-col gap-1 w-1/2 mx-auto">
                <div>
                    <label for="name" class="label">
                        Nome
                        <span class="input-error" v-if="form.errors.name">{{ form.errors.name }}</span>
                    </label>
                    <input id="name" name="name" v-model="form.name" type="text" class="input" @change="setUserCode">
                </div>

                <div>
                    <label for="surname" class="label flex-1">
                        Cognome
                        <span class="input-error" v-if="form.errors.surname">{{ form.errors.surname }}</span>
                    </label>
                    <input id="surname" name="surname" v-model="form.surname" type="text" class="input" @change="setUserCode">
                </div>

                <div class="flex flex-row gap-2 justify-between items-stretch">
                    <div>
                        <label for="role" class="label flex-1">
                            Ruolo
                            <span class="input-error" v-if="form.errors.role">{{ form.errors.role }}</span>
                        </label>
                        <input id="role" name="role" v-model="form.role" type="text" class="input" disabled>
                    </div>



                    <!-- Warehouses (Multiple Choice) -->
                    <div v-if="showWarehouseSelector">
                        <label class="label" for="warehouse_ids">Magazzino</label>
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
                        <div class="input" type="text" disabled>
                            {{ (Boolean(props.user.is_ragnista))? 'SI' : 'NO' }}
                        </div>
                    </div>

                    <div>
                        <label for="user_code" class="label flex-1">
                            Codice Breve
                        </label>
                        <input id="user_code" v-model="form.user_code" type="text" class="input" disabled>
                    </div>

                    <div v-if="currentUser.is_admin">
                        <label class="label" for="role">
                            Admin
                        </label>
                        <font-awesome-icon v-if="Boolean(props.user?.is_admin)" :icon="['fas', 'check']" class="text-4xl text-success"/>
                        <font-awesome-icon v-else :icon="['fas', 'xmark']" class="text-4xl text-error "/>
                    </div>
                </div>
                
                <div>
                    <label for="email" class="label whitespace-nowrap">
                        E-mail
                        <span class="input-error" v-if="form.errors.email">{{ form.errors.email }}</span>
                    </label>
                    <input id="email" v-model="form.email" type="text" class="input">
                    
                </div>
            </div>
            <div class="flex flex-col gap-1 w-1/2 mx-auto">
                <!-- If a new preview exists, show it -->
                <img 
                v-if="form.avatarPreview" 
                :src="form.avatarPreview" 
                class="rounded-full mx-auto" 
                alt="Avatar Preview" 
                />
                <div v-else-if="props.user.avatar">
                    <img :src="props.user.avatar" class="rounded-full mx-auto" alt="Avatar non caricato" />
                </div>
                <EmptyState v-else>
                    <span>Nessun avatar disponibile</span>
                </EmptyState>

                <div>
                        <section class="flex flex-col items-start gap-2 my-4">
                            <div>
                                Modifica Avatar
                            </div>
                            <input
                                class="border rounded-md file:px-4 file:py-2 border-gray-200 dark:border-gray-700
                                text-gray-700 dark:text-gray-400 
                                file:border-0 file:bg-gray-100 file:dark:bg-gray-800 file:font-medium file:hover:bg-gray-200 file:dark:hover:bg-gray-700
                                file:hover:cursor-pointer file:mr-4" 
                                type="file" @change="changeAvatar">
                            <div class="flex flex-row justify-between gap-2">
<!--
                                <button type="submit" class="btn btn-outline btn-success disabled:cursor-not-allowed " :disabled="!canUpload">
                                    <font-awesome-icon :icon="['fas', 'upload']" class="text-2xl"/>
                                    Upload
                                </button>
-->
                                <button type="reset" class="btn btn-outline btn-error" @click="resetAvatar">
                                    <font-awesome-icon :icon="['fas', 'arrow-rotate-left']" class="text-2xl"/>
                                    Reset
                                </button>

                            </div>
                        </section>
                        <div v-if="imageErrors.length" class="input-error">
                            <div v-for="(error, index) in imageErrors" :key="index">{{ error }}</div>
                        </div>
                </div>

            </div>

        </div>
        <div class="flex flex-row justify-end">
            <button class="btn btn-primary mt-4" type="submit">
                <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-2xl"/>
                Salva Modifiche
            </button>
        </div>

    </form>
    
    </template>
    
    <script setup>
    import { computed, ref, watch, useAttrs, onMounted, onUnmounted } from 'vue'
    import {useForm, usePage, Link} from '@inertiajs/vue3'
import EmptyState from '@/Components/UI/EmptyState.vue'
import Box from '@/Components/UI/Box.vue'
    const props = defineProps({
        user: Object,
        warehouses: Array,
    })

    const page = usePage()
    const currentUser = computed(
        () => page.props.auth.user
    )
    
    const form = useForm({
        name: props.user?.name,
        surname: props.user?.surname,
        email: props.user?.email,
        role: props.user?.role,
        user_code: props.user?.user_code,
        avatar: props.user?.avatar,
        avatarPreview: null,
    })
    
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


    const setUserCode = () => {
        if (form.name && form.surname && form.role){
            form.user_code = (form.name.substring(0, 1) + form.surname.substring(0, 1) + form.role.substring(0, 1)).toUpperCase()
        }
    }

    const canUpload = computed(() => form.avatar? true : false) // se ho immagini, length != 0

    const changeAvatar = (event) =>{
        const file = (event.target.files[0])
        if (file){
            form.avatar = file
            form.avatarPreview = URL.createObjectURL(file)
        }
    }

    const resetAvatar = () => {
        form.reset('avatar')
        form.reset('avatarPreview')
    }

    const imageErrors = computed(() => Object.values(form.errors)) //convert into array

    const update = () => {
        console.log(form.data())  // Check what the form holds
        form.data()._method = 'PUT'
        form.post(
            route('user-account.update', {user : props.user.id}),
            { forceFormData: true }
        )
    }

    </script>
    