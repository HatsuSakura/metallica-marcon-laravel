<template>
    <GoogleMap 
        mapId="DEMO_MAP_ID" 
        :api-key=mapApiKey 
        style="width: 100%; height: 100%" 
        :center="mapCenter" 
        :zoom="zoomLevel"
        :fullscreenControl = false
    >
        
    <GoogleMapMarker :site="props.site" />

    </GoogleMap>
</template>

<script setup>
import { computed, watchEffect, ref } from 'vue';
import { GoogleMap } from 'vue3-google-map';
import GoogleMapMarker from './GoogleMapMarker.vue';


const props = defineProps({
    site: Object,
})

watchEffect(() => {
  if (props.site && props.site.lat && props.site.lng) {
    console.log('Valid site data:', props.site);
  } else {
    console.warn('Invalid site data:', props.site);
  }
});


// Set the map center
// Computed property per aggiornare automaticamente il centro della mappa
const mapCenter = computed(() => ({
  lat: props.site.lat,
  lng: props.site.lng,
}));

//const theSite = computed(() => props.site);

const zoomLevel = 16;
const mapApiKey = import.meta.env.VITE_MAPS_API_KEY;


</script>
  