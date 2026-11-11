<template>
<LoadingOverlay />
  

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
import { computed, ref } from 'vue'
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


</style>
