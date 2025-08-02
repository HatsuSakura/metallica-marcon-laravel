<template>
    <div class="flex flex-row items-center justify-between mb-2">
        <div class="font-medium text-lg">
            Gestione UTENTI
        </div>
        <div>
            <Link
                class="btn btn-circle btn-primary" 
                :href="route('relator.user.create')"
            >
                <font-awesome-icon :icon="['fas', 'plus']" class="text-xl" />
            </Link>
        </div>
    </div>
<div class="flex flex-col gap-2">
    <Box v-for="user in props.users.data" :key="user.id" :class="{'border-dashed' : user.deleted_at}">
    <div class="flex flex-col md:flex-row gap-2 md:items-center justify-between">
        <div :class="{'opacity-25' : user.deleted_at}">
            <div v-if="user.deleted_at != null" class="text-xs font-bold uppercase border border-dashed p-1 border-green-300 text-green-500 dark:border-green-600 dark:text-green-600 inline-block rounded-md mb-2">cancellato</div>
            <div class="flex flex-row items-center justify-start gap-2"> 
                <font-awesome-icon v-if="user.is_admin" :icon="['fas', 'user-tie']" class="text-xl" />
                <span class="text-lg font-bold">{{ user.name }} {{ user.surname }}</span>
                &nbsp;|&nbsp;<span class="text-lg">{{ user.role }}</span>
            </div>
          
        </div>

        <section>
            <div class="flex items-center gap-1">
                <button v-if="user.role != 'warehouse_worker'" @click="resendVerification(user.id)" class="btn btn-ghost">
                    Resend Verification Email
                </button>

                <button v-if="user.role != 'warehouse_worker'" @click="sendPasswordReset(user.id)" class="btn btn-ghost">
                    Reset Password
                </button>
                <a 
                    class="btn btn-circle btn-outline btn-primary" 
                    :href="route('relator.user.show', {user: user.id})"
                >
                    <font-awesome-icon :icon="['fas', 'file-lines']" class="text-xl stroke-current" />
                </a>
                 <!-- EDIT  -->
                <Link
                    class="btn btn-circle btn-outline" 
                    :href="route('relator.user.edit', {user: user.id})"
                >
                    <font-awesome-icon :icon="['fas', 'pencil']" class="text-xl stroke-current" />
                </Link>
                 <!-- DELETE -->
                <Link 
                    v-if="!user.deleted_at"
                    class="btn btn-circle btn-outline btn-error" 
                    :href="route('relator.user.destroy', {user: user.id})"
                    as="button" method="delete"
                >
                <font-awesome-icon :icon="['fas', 'trash-can']" class="text-xl stroke-current" />
                </Link>
                <Link 
                    v-else
                    class="btn btn-circle btn-outline btn-success" 
                    :href="route('relator.user.restore', {user: user.id})"
                    as="button" method="put"
                >
                    <font-awesome-icon :icon="['fas', 'trash-can-arrow-up']" class="text-xl stroke-current"  />
                </Link>

            </div>

        </section>
    </div>
</Box>
</div>



<section v-if="props.users.data.length" class="w-full flex justify-center mt-4 mb-4">
    <Pagination :links="users.links" />
</section>

</template>

<script setup>
import Box from '@/Components/UI/Box.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Link } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import axios from 'axios';

const props = defineProps({
    users: Object,
})

const store = useStore();

const resendVerification = (userId) => {
    axios.post(`/api/user/resend-verification/${userId}`)
        .then(response => {
          console.log(response.data);
          store.dispatch('flash/queueMessage', { type: response.data.type, text: response.data.message });
        })
        .catch(error => {
          console.error(error);
        });
};

const sendPasswordReset  = (userId) => {
    axios.post(`/api/user/send-password-reset/${userId}`)
        .then(response => {
          console.log(response.data);
          store.dispatch('flash/queueMessage', { type: response.data.type, text: response.data.message });
        })
        .catch(error => {
          console.error(error);
        });
};


</script>