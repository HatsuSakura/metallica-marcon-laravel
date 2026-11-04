<template>
    <header class="fixed top-0 z-50 h-16 w-full border-b border-base-300 bg-base-100 shadow-sm">
        <nav class="mx-auto flex h-full w-full min-w-0 items-center gap-1 px-2 sm:gap-3 sm:px-4" aria-label="Navigazione principale">
            <button type="button" class="btn btn-circle btn-ghost min-h-12 shrink-0" aria-label="Apri menu di navigazione" aria-controls="application-navigation" :aria-expanded="menuOpen" @click="$emit('toggle-menu')">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6 stroke-current"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
            <Link :href="user ? route('dashboard') : route('login')" class="min-w-0 flex-1 truncate text-base font-semibold sm:text-xl">Metallica Marcon</Link>
            <button type="button" class="btn btn-circle btn-ghost min-h-12 shrink-0" :aria-label="darkTheme ? 'Attiva tema chiaro' : 'Attiva tema scuro'" :aria-pressed="darkTheme" @click="$emit('toggle-theme', !darkTheme)">
                <font-awesome-icon :icon="['fas', darkTheme ? 'moon' : 'sun']" class="h-5 w-5" aria-hidden="true" />
            </button>
            <template v-if="user">
                <Link :href="route('notification.index')" class="btn btn-circle btn-ghost relative min-h-12 shrink-0" :aria-label="notificationsCount ? `Notifiche: ${notificationsCount} non lette` : 'Notifiche'">
                    <font-awesome-icon :icon="['fas', 'bell']" class="h-5 w-5" aria-hidden="true" />
                    <span v-if="notificationsCount" class="badge badge-error badge-xs absolute right-0 top-0" aria-hidden="true">{{ notificationsCount }}</span>
                </Link>
                <details ref="accountMenu" class="dropdown dropdown-end shrink-0" @keydown.esc="closeAccountMenu">
                    <summary class="btn btn-ghost min-h-12 list-none px-3" aria-label="Menu account">
                        <font-awesome-icon :icon="['fas', 'user']" class="h-5 w-5" aria-hidden="true" />
                        <span class="hidden max-w-48 truncate md:inline">{{ user.name }} {{ user.surname }}</span>
                    </summary>
                    <ul class="menu dropdown-content z-50 mt-2 w-56 max-w-[calc(100vw-1rem)] rounded-xl border border-base-300 bg-base-100 p-2 shadow-lg">
                        <li class="px-3 py-2 text-sm break-words font-semibold">{{ user.name }} {{ user.surname }}</li>
                        <li><Link :href="route('user-account.index')" class="min-h-12" @click="closeAccountMenu">Il mio account</Link></li>
                        <li><Link :href="route('logout')" method="delete" as="button" class="min-h-12" @click="closeAccountMenu">Esci</Link></li>
                    </ul>
                </details>
            </template>
            <Link v-else :href="route('login')" class="btn btn-primary min-h-12 shrink-0">Accedi</Link>
        </nav>
    </header>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';

defineProps({
    user: { type: Object, default: null },
    notificationsCount: { type: Number, default: 0 },
    darkTheme: Boolean,
    menuOpen: Boolean,
});
defineEmits(['toggle-menu', 'toggle-theme']);
const accountMenu = ref(null);
const closeAccountMenu = () => { if (accountMenu.value) accountMenu.value.open = false; };
const closeOutside = (event) => { if (!accountMenu.value?.contains(event.target)) closeAccountMenu(); };
let removeNavigationListener;
onMounted(() => {
    document.addEventListener('click', closeOutside);
    removeNavigationListener = router.on('navigate', closeAccountMenu);
});
onUnmounted(() => {
    document.removeEventListener('click', closeOutside);
    removeNavigationListener?.();
});
</script>
