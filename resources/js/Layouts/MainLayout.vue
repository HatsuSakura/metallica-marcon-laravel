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

      <!-- HEADER -->
      <header class="border-b border-gray-500 w-full
text-base-content fixed top-0 z-50 flex h-16 justify-center bg-opacity-90 backdrop-blur transition-shadow duration-100 [transform:translate3d(0,0,0)] 
  shadow-sm">

        <!-- NAVBAR TOP -->
        <div class="navbar bg-base-100">
          <div class="flex-none">

            <button class="btn btn-circle btn-ghost drawer-button">
              <label for="my-drawer" class="">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                  class="inline-block h-5 w-5 stroke-current">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                  </path>
                </svg>
              </label>
            </button>

          </div>
          <div class="flex-1">
            <a class="text-xl ml-4">
              <Link :href="route('relator.customer.index')">Metallica Marcon</Link>&nbsp;
            </a>
          </div>
          <div class="flex-none">
          </div>


          <div class="navbar-end">

            <div class="flex flex-row items-center">
              <div v-if="currentUser" class="flex items-center gap-0">

                <!-- USER -->
                <Link :href="route('user-account.index')" class="btn btn-circle btn-ghost">
                  <font-awesome-icon :icon="['fas', 'user']" class="h-5 w-5 stroke-current" />
                </Link>
                <Link :href="route('user-account.index')" class="text-sm text-gray-500">
                  {{ currentUser.name }} {{ currentUser.surname }}
                </Link>

                <!-- BELL -->
                <Link :href="route('notification.index')" class="relative pr-2 py-2 text-lg btn btn-circle btn-ghost">
                <font-awesome-icon :icon="['fas', 'bell']" class="h-5 w-5 stroke-current" />
                <div v-if="notificationsCount"
                  class="absolute right-0 top-0 w-5 h-5 bg-red-700 dark:bg-red-400 text-white font-medium border border-white dark:border-gray-900 rounded-full text-xs text-center">
                  {{ notificationsCount }}
                </div>
                </Link>


                <!-- IMPOSTAZIONI -->
                <button class="btn btn-circle btn-ghost">
                  <font-awesome-icon :icon="['fas', 'cog']" class="h-5 w-5 stroke-current" />
                </button>

                <!-- LOGOUT -->
                <Link :href="route('logout')" class="btn btn-circle btn-ghost" method="delete" as="button">
                <font-awesome-icon :icon="['fas', 'right-to-bracket']" class="h-5 w-5 stroke-current" />
                </Link>

              </div>
              <div v-else class="flex items-center gap-2">
                <Link :href="route('login')" class="btn btn-primary">
                login
                </Link>
                <!--<Link :href="route('user-account.create')" class="btn-primary">Register</Link>-->
              </div>
              <!-- TEMA -->
              <div class="ml-4">
              <label class="flex cursor-pointer gap-2">
                <font-awesome-icon :icon="['fas', 'sun']" class="text-2xl"/>
                <input 
                  type="checkbox" 
                  value="synthwave" 
                  class="toggle theme-controller"
                  :checked="isDarkTheme"
                  @change="toggleTheme($event.target.checked)"
                />
                <font-awesome-icon :icon="['fas', 'moon']" class="text-2xl"/>
              </label>
              </div>
            </div>

          </div>

        </div>

      </header>

      <!-- MAIN -->
      <main :class="{
        'container': !isMapPage,
        'full-width': isMapPage,
        'absolute': isMapPage,
        'pt-24': true,
        'mx-auto': true,
        'p-4': true,
        'w-full': true,
      }">



        <!-- PAGE SLOT -->
        <slot>Default</slot>
      </main>


    </div>
    <div class="drawer-side z-40 pt-12">
      <label for="my-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
      <ul class="menu bg-base-200 text-base-content min-h-full w-80 p-2 pt-12">
        <!-- Sidebar content here -->
<!--
        <li>
          <Link @click="closeDrawer" :href="route('listing.index')"><font-awesome-icon icon="cart-arrow-down" />
          Listings</Link>
        </li>
-->
        <li>
          <Link @click="closeDrawer" :href="route('dashboard')">La mia Dashboard</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('relator.customer.index')">Clienti</Link>
        </li>
        <li>
          <Link @click="closeDrawer" :href="route('relator.order.index')">Ordini</Link>
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
          <Link @click="closeDrawer" :href="route('driver.journey.index')">Dashboard Autista - Viaggi</Link>
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
                <Link @click="closeDrawer" :href="route('relator.user.index')">Utenti</Link>
              </li>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('relator.vehicle.index')">Automezzi</Link>
              </li>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('relator.trailer.index')">Rimorchi</Link>
              </li>
              <li v-if="currentUser && currentUser.is_admin">
                <Link @click="closeDrawer" :href="route('relator.cargo.index')">Cassoni</Link>
              </li>
              <li>
                <Link @click="closeDrawer" :href="route('holder.index')">Contenitori</Link>
              </li>
              <li>
                <Link @click="closeDrawer" :href="route('relator.customer.index')">Clienti</Link>
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
  return page.url.startsWith('/map/site') || page.url.startsWith('/relator/journey/create');
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
  () => page.props.auth.user
)

const notificationsCount = computed(
  () => Math.min(page.props.user.notificationCount, 9)
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