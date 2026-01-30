<template>
    <h2>Le tue notifiche</h2>
    <section v-if="notifications.data.length" class="text-gray-700 dark:text-gray-400">
        <div v-for="notification in notifications.data" :key="notification.id" class="border-b border-gray-200 dark:border-x-gray-800 py-4 flex justify-between items-center">
            <div>
                <span>
                    {{ notification.data && notification.data.message ? notification.data.message : 'Nuova notifica' }}
                </span>
            </div>
            <div>
                <Link 
                    v-if="!notification.read_at" 
                    :href="route('notification.seen', { notification: notification.id } )"
                    as="button"
                    method="put"
                    class="btn-outline text-xs font-medium uppercase"
                >
                    Mark as read
                </Link>
            </div>
        </div>
    </section>

    <EmptyState v-else>Nessuna notifica al momento</EmptyState>

    <section v-if="notifications.data.length" 
        class="w-full flex justify-center mt-8 mb-8"
    >
        <Pagination :links="notifications.links"/>
    </section>
</template>

<script setup>
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { Link } from '@inertiajs/vue3';

    defineProps({
        notifications: Object,
    })
</script>
