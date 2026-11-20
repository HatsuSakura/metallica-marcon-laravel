<template>
    <AppPage title="Le tue notifiche">
        <template #back><AppAction :as="Link" :href="route('dashboard')"><font-awesome-icon :icon="['fas','arrow-left']" aria-hidden="true" />Torna alla dashboard</AppAction></template>
        <template #actions><AppAction :disabled="busy" @click="applyFilters"><font-awesome-icon :icon="['fas','rotate']" aria-hidden="true" />Aggiorna</AppAction></template>
        <form class="rounded-xl border border-base-300 p-3" aria-label="Filtri notifiche" @submit.prevent="applyFilters">
            <fieldset :disabled="busy" class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <label class="form-control"><span class="label">Dal</span><input v-model="form.from" type="date" class="input input-bordered" /></label>
                <label class="form-control"><span class="label">Al</span><input v-model="form.to" type="date" class="input input-bordered" :min="form.from || undefined" /></label>
                <label class="form-control"><span class="label">Stato</span><select v-model="form.read" class="select select-bordered"><option value="all">Tutte</option><option value="unread">Non lette</option><option value="read">Lette</option></select></label>
                <label class="form-control"><span class="label">Tipologia</span><select v-model="form.type" class="select select-bordered"><option value="all">Tutte le notifiche</option><option value="journey-execution-update">Esecuzione viaggi</option></select></label>
                <div class="flex flex-wrap items-end gap-2"><AppAction type="submit" variant="success" class="btn-outline">Filtra</AppAction><AppAction variant="warning" class="btn-outline" :disabled="busy" @click="resetFilters">Azzera</AppAction></div>
            </fieldset>
            <p v-for="(message,field) in page.props.errors" :key="field" role="alert" class="mt-2 text-sm text-error">{{ message }}</p>
        </form>
        <div v-if="notifications.data.length" class="space-y-3"><p class="text-sm">{{ notifications.total }} notifiche<template v-if="notifications.last_page > 1"> · Pagina {{ notifications.current_page }} di {{ notifications.last_page }}</template></p><NotificationCard v-for="notification in notifications.data" :key="notification.id" :notification="notification" /><Pagination :links="notifications.links" /></div>
        <EmptyState v-else>Nessuna notifica corrisponde ai filtri selezionati.</EmptyState>
    </AppPage>
</template>
<script setup>
import {reactive,ref,watch} from 'vue'
import {Link,router,usePage} from '@inertiajs/vue3'
import AppPage from '@/Components/UI/AppPage.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import EmptyState from '@/Components/UI/EmptyState.vue'
import Pagination from '@/Components/UI/Pagination.vue'
import NotificationCard from './Components/NotificationCard.vue'
const props=defineProps({notifications:Object,filters:{type:Object,default:()=>({})}})
const page=usePage()
const defaults=()=>({from:props.filters.from || '',to:props.filters.to || '',read:props.filters.read || 'all',type:props.filters.type || 'all'})
const form=reactive(defaults())
watch(()=>props.filters,()=>Object.assign(form,defaults()))
const busy=ref(false)
const applyFilters=()=>{if(busy.value)return;busy.value=true;router.get(route('notification.index'),form,{preserveState:true,preserveScroll:true,onFinish:()=>{busy.value=false}})}
const resetFilters=()=>{Object.assign(form,{from:'',to:'',read:'all',type:'all'});applyFilters()}
</script>
