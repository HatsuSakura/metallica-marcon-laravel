<template>
    <div class="full-width-map">
        <div class="my-container">
            <GoogleMap ref="googleMapRef"
                mapId="DEMO_MAP_ID"
                style="width: 100%; height: 100%"
                :api-key="props.mapApiKey"
                :center="props.mapCenter"
                :zoom="props.zoomLevel"
                :fullscreenControl="false"
            >
                <GoogleMapMarker v-for="site in sites"
                    :key="site.id"
                    :site="site"
                    :withInfoWindow="true"
                />
            </GoogleMap>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { GoogleMap } from 'vue3-google-map';
import GoogleMapMarker from './GoogleMapMarker.vue';

const MAX_ZOOM_AFTER_FIT = 13;
const FIT_PADDING = 60;

const props = defineProps({
    markers: Array,
    mapCenter: Object,
    zoomLevel: Number,
    mapApiKey: String,
    fitBounds: { type: Object, default: null },
});

const googleMapRef = ref(null);

const sites = computed(() => props.markers);

watch(
    () => props.fitBounds,
    async (bbox) => {
        if (!bbox) return;
        await nextTick();
        const mapInstance = googleMapRef.value?.map;
        if (!mapInstance) return;

        const bounds = new google.maps.LatLngBounds(
            { lat: bbox.south, lng: bbox.west },
            { lat: bbox.north, lng: bbox.east },
        );
        mapInstance.fitBounds(bounds, FIT_PADDING);

        const listener = mapInstance.addListener('idle', () => {
            if (mapInstance.getZoom() > MAX_ZOOM_AFTER_FIT) {
                mapInstance.setZoom(MAX_ZOOM_AFTER_FIT);
            }
            google.maps.event.removeListener(listener);
        });
    },
);

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
