<template>
    <div class="full-width-map">
        <div class="my-container">
            <GoogleMap mapId="DEMO_MAP_ID" 
                style="width: 100%; height: 100%" 
                :api-key=props.mapApiKey
                :center="props.mapCenter" 
                :zoom="props.zoomLevel"
                :fullscreenControl = false
                >
                    <GoogleMapMarker v-for="site in sites"
                        :site="site"
                        :withInfoWindow="true"
                    />
                    
            </GoogleMap>
        </div>
    </div>
</template>

<script setup>
    import { ref, computed, watch } from 'vue';
    import { GoogleMap  } from 'vue3-google-map';
import GoogleMapMarker from './GoogleMapMarker.vue';

const props = defineProps({
            markers: Array,
            mapCenter: Object,
            zoomLevel: Number,
            mapApiKey:String,
    })


const sites = computed(() => props.markers);

watch(sites, (newSites) => {
    console.log('Updated sites:', newSites);
});

const infoWindowVisible = ref(false);

</script>


<style scoped>
.full-width-map {
    margin: 0;
    padding: 0;
    width: 100vw;
    /* Full viewport width */
    height: 100vh;
    /* Full viewport height */
    padding-top: 64px;
    box-sizing: border-box;
}

.full-width-map .my-container {
    max-width: 100%;
    /* Override container's max-width */
    width: 100%;
    /* Make sure the container takes the full width */
    height: 100%;
    padding: 0;
    /* Remove default padding */
    box-sizing: border-box;
}

.gm-style-iw-d{
    overflow: hidden !important;
    line-height: 1em;
    padding: 1em;
}

</style>