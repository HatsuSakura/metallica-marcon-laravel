<template>
<RegistryPage title="Modifica utente">
<template #back><Link
        :href="route('user.index')"
        method="get"
        as="button"
        class="btn btn-outline"
        >
        <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-2xl"/>
        Torna all'elenco utenti
    </Link></template>




    <RegistryForm :form="form" editing v-slot="{canSubmit, busy}" @submit="update" >
        <div class="flex flex-col gap-1 w-full max-w-4xl mx-auto">
            <div>
                <label for="name" class="label">
                    Nome
                    <span class="input-error" v-if="form.errors.name" id="name-error" role="alert">{{ form.errors.name }}</span>
                </label>
                <input id="name" v-model="form.name" type="text" class="input" @change="setUserCode" :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'name-error' : undefined" name="name">
            </div>

            <div>
                <label for="surname" class="label flex-1">
                    Cognome
                    <span class="input-error" v-if="form.errors.surname" id="surname-error" role="alert">{{ form.errors.surname }}</span>
                </label>
                <input id="surname" v-model="form.surname" type="text" class="input" @change="setUserCode" :aria-invalid="!!form.errors.surname" :aria-describedby="form.errors.surname ? 'surname-error' : undefined" name="surname">
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="label" for="role">
                        Ruolo
                    </label>
                    <select v-model="form.role" class="select select-bordered" id="role" required  @change="setUserCode" :aria-invalid="!!form.errors.role" name="role">
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
                        <label class="inline-flex min-h-12 items-center">
                        <input
                            type="checkbox"
                            v-model="form.warehouse_ids"
                            :value="warehouse.id"
                            class="checkbox mr-2"
                         :id="`registry-user-warehouse-${warehouse.id}`" :aria-invalid="!!form.errors.warehouse_ids" name="warehouse_ids"/>
                        <span>{{ warehouse.name }}</span>
                        </label>
                    </div>
                    </div>
                </div>

                <div v-if="showCraneOperator">
                    <label class="label" for="is_crane_operator">
                        Operatore ragno
                    </label>
                    <input v-model="form.is_crane_operator" id="is_crane_operator" type="checkbox" class="toggle"  :aria-invalid="!!form.errors.is_crane_operator" name="is_crane_operator"/>
                </div>

                <div>
                    <label for="user_code" class="label flex-1">
                        Codice Breve
                        <span class="input-error" v-if="form.errors.user_code" id="user_code-error" role="alert">{{ form.errors.user_code }}</span>
                    </label>
                    <input id="user_code" v-model="form.user_code" type="text" class="input" disabled :aria-invalid="!!form.errors.user_code" :aria-describedby="form.errors.user_code ? 'user_code-error' : undefined" name="user_code">
                </div>

                <div v-if="currentUser.is_admin && showEmailAndPassword">
                    <label class="label" for="is_admin">
                        Admin
                    </label>
                    <input v-model="form.is_admin" id="is_admin" type="checkbox" class="toggle"  :aria-invalid="!!form.errors.is_admin" name="is_admin"/>
                </div>
            </div>

            <div v-if="showEmailAndPassword">
                <label for="email" class="label whitespace-nowrap">
                    E-mail
                    <span class="input-error" v-if="form.errors.email" id="email-error" role="alert">{{ form.errors.email }}</span>
                </label>
                <input id="email" v-model="form.email" type="text" class="input" :aria-invalid="!!form.errors.email" :aria-describedby="form.errors.email ? 'email-error' : undefined" name="email">

            </div>
<!--
            <div>
                <label for="password" class="label whitespace-nowrap">
                    Password
                    <span class="input-error" v-if="form.errors.password" id="password-error" role="alert">{{ form.errors.password }}</span>
                </label>
                <input id="password" v-model="form.password" type="password" class="input" :aria-invalid="!!form.errors.password" :aria-describedby="form.errors.password ? 'password-error' : undefined" name="password">
            </div>

            <div>
                <label for="password_confirmation" class="label whitespace-nowrap">Conferma Password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="input" :aria-invalid="!!form.errors.password_confirmation" name="password_confirmation">
            </div>
-->
            <div>
                <Link :href="route('user.index')" class="btn btn-error btn-outline mr-2">Annulla</Link>
<button class="btn w-full mt-4 text-lg btn-success" type="submit" :disabled="!canSubmit" :aria-busy="busy || undefined"><span v-if="busy" class="loading loading-spinner loading-sm" aria-hidden="true"></span>
                    <font-awesome-icon :icon="['fas', 'user-pen']" />
                    Modifica Utente
                </button>
            </div>
        </div>
    </RegistryForm>


</RegistryPage>
</template>

    <script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

    import { computed, ref, watch, useAttrs, onMounted, onUnmounted } from 'vue'
    import {useForm, usePage, Link} from '@inertiajs/vue3'
    const props = defineProps({
        user: Object,
        roles: Array,
        warehouses: Array,
    })

    const page = usePage()
    const currentUser = computed(
        () => page.props.auth.user
    )

    const showCraneOperator = computed(() =>
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
        name: props.user?.name,
        surname: props.user?.surname,
        email: props.user?.email,
        password:null,
        password_confirmation:null,
        role: props.user?.role,
        warehouse_ids: props.user?.warehouses ? props.user.warehouses.map(w => w.id) : [],
        is_crane_operator: Boolean(props.user?.is_crane_operator),
        user_code: props.user?.user_code,
        is_admin: Boolean(props.user?.is_admin),
    })

    const setUserCode = () => {
        if (form.name && form.surname && form.role){
            form.user_code = (form.name.substring(0, 1) + form.surname.substring(0, 1) + form.role.substring(0, 1)).toUpperCase()
        }
    }

    const update = () => {
        if (!showCraneOperator.value) {
            form.is_crane_operator = false;
        }
        if (!showWarehouseSelector.value) {
            form.warehouse_ids = [];
        }
        if (!showEmailAndPassword.value) {
            delete form.email;
            delete form.password;
            delete form.password_confirmation;
        }
        form.put(route('user.update', {user:props.user.id}));
    }

    </script>


