<template>
<LoadingOverlay />
  

<div id="messageQueue"  class="notification-container">

<div class="mt-8 space-y-4 container w-full mx-auto">

  <div v-for="(message, index) in messageQueue" 
    :key="index" role="alert" 
    :class="{
      'alert-success': message.type === 'success',
      'alert-error': message.type === 'error',
      'alert-warning': message.type === 'warning',
      'alert-info': message.type === 'info'
    }" 
    class="alert"
  >
  <font-awesome-icon v-if="message.type === 'success'"      :icon="['fas', 'circle-check']" />
  <font-awesome-icon v-else-if="message.type === 'error'"   :icon="['fas', 'circle-xmark']" />
  <font-awesome-icon v-else-if="message.type === 'warning'" :icon="['fas', 'triangle-exclamation']" />
  <font-awesome-icon v-else                                 :icon="['fas', 'circle-info']" />

    <div>{{ message.text }}</div>
    <button @click="removeMessage(index)" class="btn btn-sm btn-circle btn-ghost">
      <font-awesome-icon :icon="['fas', 'xmark']" />
    </button>

  </div>

</div>

</div>

  <div class="drawer">
    <input id="my-drawer" type="checkbox" class="drawer-toggle" v-model="isDrawerOpen" />
    <div class="drawer-content">
      <!-- Page content here -->

      <AppHeader
        :user="currentUser"
        :notifications-count="notificationsCount"
        :dark-theme="isDarkTheme"
        :menu-open="isDrawerOpen"
        @toggle-menu="isDrawerOpen = !isDrawerOpen"
        @toggle-theme="toggleTheme"
      />

      <!-- MAIN -->
      <main :class="{
        'container': !isMapPage,
        'full-width': isMapPage,
        'absolute': isMapPage,
        'pt-24':  !isMapPage,
        'mx-auto':  !isMapPage,
        'p-4':  !isMapPage,
        'w-full': true,
      }">



        <!-- PAGE SLOT -->
        <slot>Default</slot>
      </main>


    </div>
    <div id="application-navigation" class="drawer-side z-40 pt-12">
      <label for="my-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
      <ul class="menu bg-base-200 text-base-content min-h-full w-80 p-2 pt-12">
        <!-- Sidebar content here -->
        <li>
          <Link @click="closeDrawer" :href="route('dashboard')">La mia Dashboard</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('customer.index')">Clienti</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('order.index')">Ordini</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('journey.index')">Viaggi</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('map.site.index')">Map View</Link>
        </li>
<!--
        <li>
          <Link @click="closeDrawer" :href="route('driver.order.index')">Dashboard Autista - Ordini [DEBUG]</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('driver.home')">Dashboard Autista - Viaggi</Link>
        </li>
   
        <li>
          <Link @click="closeDrawer" :href="route('warehouse-manager.orders.index')">Dashboard Ordini Capo Magazziniere</Link>
        </li>
-->
        
        <li>
          <details close>
            <summary>Anagrafiche</summary>
            <ul>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('user.index')">Utenti</Link>
              </li>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('vehicle.index')">Automezzi</Link>
              </li>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('trailer.index')">Rimorchi</Link>
              </li>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('cargo.index')">Cassoni</Link>
              </li>
              <li>
                <Link @click="closeDrawer" :href="route('holder.index')">Contenitori</Link>
              </li>
              <li>
                <Link @click="closeDrawer" :href="route('customer.index')">Clienti</Link>
              </li>
              <li v-if="canManageBusinessTypes">
                <Link @click="closeDrawer" :href="route('business-type.index')">Tipologie attività</Link>
              </li>
              <li>
                <details close>
                  <summary>Materiali e Ricette</summary>
                  <ul>
                    <li>
                      <Link @click="closeDrawer" :href="route('catalog-items.index')">Materiali/Componenti</Link>
                    </li>
                    <li>
                      <Link @click="closeDrawer" :href="route('recipes.index')">Ricette</Link>
                    </li>
                  </ul>
                </details>
              </li>
            </ul>
          </details>
        </li>
      </ul>
    </div>
  </div>

</template>

<script setup>
import { computed, ref, watch, useAttrs, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy';
import { useStore } from 'vuex';
import { router } from '@inertiajs/vue3'; // Import the Inertia.js router
import { faUsersViewfinder } from '@fortawesome/free-solid-svg-icons';
import LoadingOverlay from '@/Components/LoadingOverlay.vue';
import AppHeader from '@/Components/UI/AppHeader.vue';


const store = useStore();


// Computed property to check the current theme
const isDarkTheme = computed(() => store.state.theme.theme === 'darktheme');
// Method to toggle the theme
const toggleTheme = (isDarkMode) => {
  store.dispatch('theme/toggleTheme', isDarkMode); // Update Vuex store
  const theme = isDarkMode ? 'darktheme' : 'lighttheme';
  document.documentElement.setAttribute('data-theme', theme); // Apply theme to the DOM
};

// Access the messages in the queue
const messageQueue = computed(() => store.state.flash.messageQueue);

// Reactive state to manage whether the drawer is open or closed
const isDrawerOpen = ref(false);

const openDrawer = () => {
  isDrawerOpen.value = true;
};

const closeDrawer = () => {
  isDrawerOpen.value = false;  // Close the drawer when a link is clicked
};


// OLD VUE page.props.value.flash.success
// ACTUAL page.props.flash.success
const page = usePage()
/*
const isMapPage = computed(
  () => page.url.startsWith('/map/site') 
)
*/
const isMapPage = computed(() => {
  const component = page.component ?? '';
  if (component === 'Map/Index' || component.startsWith('Map/')) {
    return true;
  }

  const rawUrl = page.url ?? '';
  const normalized = rawUrl
    .replace(/^https?:\/\/[^/]+/i, '')
    .split('?')[0]
    .split('#')[0]
    .replace(/^\/+/, '');

  return normalized === 'map/site' || normalized.startsWith('map/site/');
});

const removeMessage = (index) => {
  store.dispatch('flash/removeMessageByIndex', index);
};

const attrs = useAttrs()
const flash = computed( () => attrs.flash )

// Watch for changes in `flash` and dispatch messages to Vuex
watch(flash, (newFlash) => {
  if (newFlash.success) {
    store.dispatch('flash/queueMessage', { type: 'success', text: newFlash.success });
  }
  if (newFlash.error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: newFlash.error });
  }
  if (newFlash.warning) {
    store.dispatch('flash/queueMessage', { type: 'warning', text: newFlash.warning });
  }
  if (newFlash.info) {
    store.dispatch('flash/queueMessage', { type: 'info', text: newFlash.info });
  }

}, { immediate: true }); // Add `immediate: true` to run on initial load if flash has data


/*
// Clear the flash message on Inertia.js navigation
const clearFlashOnNavigation = () => {
  if (flash && flash.success) {
    flash.success = null;  // Clear the flash message
  }
};

// Register Inertia.js router events
onMounted(() => {
  router.on('navigate', clearFlashOnNavigation);
});

onUnmounted(() => {
  router.off('navigate', clearFlashOnNavigation);
});

*/
const currentUser = computed(
  () => page.props.auth?.user ?? null
)

const canManageBusinessTypes = computed(() => {
  const user = currentUser.value
  if (!user) {
    return false
  }

  return Boolean(user.is_admin || ['developer', 'manager', 'logistic'].includes(user.role))
})

const notificationsCount = computed(
  () => Math.min(Number(page.props.user?.notificationCount ?? 0), 9)
)

</script>


<style scoped>
.full-width {
  width: 100vw;
  padding: 0;
  margin: 0;
}


.notification-container {
  position: fixed;
  width: 100%;
  z-index: 9999; /* Ensure highest priority */
  pointer-events: none; /* Prevent interference */
}

.notification-container .alert {
  pointer-events: auto; /* Allow interaction with alerts */
}

</style>
