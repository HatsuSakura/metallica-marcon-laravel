<template>


<div class="drawer drawer-end">
  <input id="mapInfoDrawer" type="checkbox" class="drawer-toggle" v-model="isMapInfoDrawerOpen" />
  <div class="drawer-content">

    <div class="custom-map-floating-filters drop-shadow-xl">
        <div class="collapse bg-base-200">
            <input type="checkbox" />
            <div class="collapse-title text-xl font-medium">
                <span class="text-2xl mr-4">
                    <font-awesome-icon :icon="['fas', 'filter']" />
                </span>
                Filtri dinamici
            </div>
            <div class="collapse-content">

                <MapSiteFilters :filters="filters">

                </MapSiteFilters>
            </div>
        </div>
    </div>

    <div class="custom-map-info-panel drop-shadow-xl">
            <!-- Page content here -->
    <label for="mapInfoDrawer" class="drawer-button btn btn-circle btn-primary">
        <font-awesome-icon :icon="['fas', 'circle-info']" class="text-2xl" />
    </label>
   </div>




    <GoogleMapLoader :mapConfig="mapConfig" :mapCenter="mapCenter" :zoomLevel="zoomLevel" :mapApiKey="mapApiKey"
        apiKey="yourApiKey" :markers="selectedSites">
    </GoogleMapLoader>


  </div>
  <div class="drawer-side z-50 pt-0">
    
    <label for="mapInfoDrawer" aria-label="close sidebar" class="drawer-overlay"></label>
    <div class="menu bg-base-200 text-base-content min-h-full min-w-80 w-1/2 p-4">
        <MapInfoPanel />
    <ul >
      <!-- Sidebar content here -->
      <li><a>Sidebar Item 1</a></li>
      <li><a>Sidebar Item 2</a></li>
    </ul>

    </div>

  </div>
</div>


</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import GoogleMapLoader from './Components/GoogleMapLoader.vue';
import MapSiteFilters from './Index/Components/MapSiteFilters.vue';
import MapInfoPanel from './Index/Components/MapInfoPanel.vue';
import eventBus from '@/eventBus';

const mapConfig = null;
const props = defineProps({
    sites: Array,
    filters: Object,
})


// Make selectedSites computed based on props.sites directly
const selectedSites = computed(() => props.sites);

watch(selectedSites, (sites) => {
    console.log(sites)    
}
, { immediate: true }); // Add `immediate: true` to run on initial load if flash has data


// Reactive state to manage whether the drawer is open or closed
const isMapInfoDrawerOpen = ref(false);

const openMapInfoDrawer = () => {
    isMapInfoDrawerOpen.value = true;
};

const closeMapInfoDrawer = () => {
    isMapInfoDrawerOpen.value = false;  // Close the drawer when a link is clicked
};

onMounted(() => {
  eventBus.on('openMapInfoDrawer', openMapInfoDrawer);
});

onUnmounted(() => {
  eventBus.off('openMapInfoDrawer', closeMapInfoDrawer);
});


// Set the map center
const mapCenter = { lat: 45.0440723, lng: 10.8413916 };
const zoomLevel = 6;
const mapApiKey = "AIzaSyB_Q0-uG59EtZ6VpSc77FVuSBvIgpg_79Q"

</script>

<style scoped>
.custom-map-floating-filters {
    z-index: 10;
    position: fixed;
    width: auto;
    top: 64px;
    left: 50%;
    transform: translateX(-50%);
}

.custom-map-info-panel {
    z-index: 10;
    position: fixed;
    width: auto;
    top: 64px;
    right: 0px ;
    padding: 1em;
}
</style>

<style>
.gm-style-iw-d {
    padding: 0em 2em 2em 1em !important;
    overflow: hidden !important;
}
</style>