<template>
    <article class="min-w-0 space-y-2 rounded-xl border border-base-300 bg-base-100 p-3 sm:p-4" :aria-label="notification.data?.message || 'Notifica'">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="min-w-0 flex-1"><h2 class="break-words font-semibold">{{ notification.data?.message || 'Nuova notifica' }}</h2><div class="mt-1 flex min-w-0 flex-col gap-y-1 text-sm md:flex-row md:flex-wrap md:items-baseline md:gap-x-2 md:gap-y-0">
                <span>{{ notificationDate(notification.created_at) }}<template v-if="notification.data?.actor?.name"> · {{ notification.data.actor.name }}</template></span>
                <span v-if="notification.type === 'journey-execution-update' && notification.data.stop?.label" class="break-words font-medium"><span class="hidden md:inline" aria-hidden="true">· </span>{{ notification.data.stop.label }}</span>
            </div></div>
            <span v-if="!notification.read_at" class="badge badge-info">Non letta</span><span v-else class="badge badge-neutral badge-outline">Letta</span>
        </div>
        <template v-if="notification.type === 'journey-execution-update'">
            <p v-if="notification.data.stop?.values?.reason_code" class="text-sm">Motivo: {{ notificationValue(notification.data.stop.values.reason_code) }}<template v-if="notification.data.stop.values.driver_notes"> · {{ notification.data.stop.values.driver_notes }}</template></p>
            <details v-if="changes.length" :open="!notification.read_at">
                <summary class="cursor-pointer py-2 text-sm font-semibold md:py-1">{{ notification.data.summary_basis === 'plan' ? 'Variazioni rispetto al pianificato' : 'Variazioni dell’aggiornamento' }} ({{ Math.min(changes.length,4) }})</summary>
                <NotificationChanges :changes="changes.slice(0,4)" :before-label="notification.data.summary_basis === 'plan' ? 'Pianificato' : 'Prima'" />
            </details>
            <p v-else-if="notification.data.summary_basis !== 'plan'" class="text-sm">Stato del viaggio aggiornato.</p>
            <details v-if="changes.length > 4"><summary class="cursor-pointer py-2 text-sm font-semibold md:py-1">Altre variazioni ({{ changes.length - 4 }})</summary><NotificationChanges :changes="changes.slice(4)" :before-label="notification.data.summary_basis === 'plan' ? 'Pianificato' : 'Prima'" /></details>
            <details v-if="notification.data.baseline_changes?.length && notification.data.summary_basis !== 'plan'"><summary class="cursor-pointer py-2 text-sm font-semibold md:py-1">Confronto con il piano iniziale</summary><NotificationChanges :changes="notification.data.baseline_changes" before-label="Pianificato" /></details>
            <p v-else-if="!notification.data.baseline_changes?.length && ['complete','skip','reopen','finish'].includes(notification.data.event)" class="text-sm">Nessuna variazione rispetto al pianificato.</p>
            <details v-if="notification.data.previous_outcome_changes?.length"><summary class="cursor-pointer py-2 text-sm font-semibold md:py-1">Cambiamenti dalla precedente conclusione</summary><NotificationChanges :changes="notification.data.previous_outcome_changes" before-label="Precedente esito" /></details>
            <p v-else-if="notification.data.previous_outcome_changes" class="text-sm">Quantità e dati invariati rispetto alla precedente conclusione.</p>
            <div class="flex flex-wrap items-center gap-2">
                <AppAction v-if="notification.journey_url" :as="Link" :href="notification.journey_url" :aria-label="`Viaggio #${notification.data.journey_id}`"><font-awesome-icon :icon="['fas','route']" aria-hidden="true" />#{{ notification.data.journey_id }}</AppAction>
                <AppAction v-for="order in notification.orders" :key="order.url" :as="Link" :href="order.url" :aria-label="`Ordine ${order.label}`"><font-awesome-icon :icon="['fas','file-lines']" aria-hidden="true" />{{ order.label }}</AppAction>
                <AppAction v-if="!notification.read_at" class="ml-auto" :as="Link" :href="route('notification.seen',{notification:notification.id})" method="put" preserve-scroll variant="function"><font-awesome-icon :icon="['fas','check']" aria-hidden="true" />Segna come letta</AppAction>
            </div>
            <p v-if="!notification.journey_url" class="text-sm">Il viaggio non è più disponibile per questo account.</p>
        </template>
        <AppAction v-if="!notification.read_at && notification.type !== 'journey-execution-update'" :as="Link" :href="route('notification.seen',{notification:notification.id})" method="put" preserve-scroll variant="function"><font-awesome-icon :icon="['fas','check']" aria-hidden="true" />Segna come letta</AppAction>
    </article>
</template>
<script setup>
import {computed} from 'vue'
import {Link} from '@inertiajs/vue3'
import AppAction from '@/Components/UI/AppAction.vue'
import NotificationChanges from './NotificationChanges.vue'
import {notificationDate,notificationValue,notificationChanges} from '@/utils/executionNotification'
const props = defineProps({notification:{type:Object,required:true}})
const changes = computed(() => notificationChanges(props.notification.data?.changes))
</script>
