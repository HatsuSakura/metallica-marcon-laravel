<template>
    <div class="full-width-map">
        <div class="my-container">
            <GoogleMap mapId="DEMO_MAP_ID" 
                style="width: 100%; height: 100%" 
                :api-key=mapApiKey
                :center="mapCenter" 
                :zoom="mapZoom"
                :fullscreenControl = false
                >
                    <OrderMapMarker v-for="order in orders"
                        :order="order"
                        :withInfoWindow="true"
                        list="orders"
                    />

                    <OrderMapMarker v-for="order in listMotrice"
                        :order="order"
                        :withInfoWindow="true"
                        list="listMotrice"
                    />

                    <OrderMapMarker v-for="order in listRimorchio"
                        :order="order"
                        :withInfoWindow="true"
                        list="listRimorchio"
                    />

                    <OrderMapMarker v-for="order in listRiempimento"
                        :order="order"
                        :withInfoWindow="true"
                        list="listRiempimento"
                    />
                    
            </GoogleMap>
        </div>
    </div>
</template>

<script setup>

import { ref, computed, watch, onMounted } from 'vue';
import { GoogleMap  } from 'vue3-google-map';
import OrderMapMarker from './OrderMapMarker.vue';


const props = defineProps({
        orders: Object,
        listMotrice: Object,
        listRimorchio: Object,
        listRiempimento: Object,
        mapCenter: Object,
        zoomLevel: Number,
    })

const sites = computed(() => props.markers);

watch(sites, (newSites) => {
    console.log('Updated sites:', newSites);
});

const infoWindowVisible = ref(false);

const mapApiKey = import.meta.env.VITE_MAPS_API_KEY;
const mapZoom   = Number(import.meta.env.VITE_MAPS_ZOOM_DEFAULT);
// Dinamically define the centroid of the orders cloud in the map
const mapCenter = ref({lng:0, lat:0});
// Function to calculate the centroid
function calculateCentroid() {
    const sumLat = ref(0); 
    const sumLng = ref(0);
    const totVal = ref(0);

  if (props.orders.length) {
    sumLat.value = props.orders.reduce((sum, order) => sum + order.site.lat, 0);
    sumLng.value = props.orders.reduce((sum, order) => sum + order.site.lng, 0);
    totVal.value = props.orders.length;
  }

  if (props.listMotrice.length) {
    sumLat.value = props.listMotrice.reduce((sum, order) => sum + order.site.lat, 0);
    sumLng.value = props.listMotrice.reduce((sum, order) => sum + order.site.lng, 0);
    totVal.value = props.listMotrice.length;
  }

  if (props.listRimorchio.length) {
    sumLat.value = props.listRimorchio.reduce((sum, order) => sum + order.site.lat, 0);
    sumLng.value = props.listRimorchio.reduce((sum, order) => sum + order.site.lng, 0);
    totVal.value = props.listRimorchio.length;
  }

  if (props.listRiempimento.length) {
    sumLat.value = props.listRiempimento.reduce((sum, order) => sum + order.site.lat, 0);
    sumLng.value = props.listRiempimento.reduce((sum, order) => sum + order.site.lng, 0);
    totVal.value = props.listRiempimento.length;
  }


  return {
    lat: sumLat.value / Math.max(totVal.value, 1),
    lng: sumLng.value / Math.max(totVal.value, 1)
  };

}
// Calculate centroid once during initialization
onMounted(() => {
    mapCenter.value = calculateCentroid();
});


</script>


<style scoped>
.full-width-map {
    margin: 0;
    padding: 0;
    width: 100%;
    /* Full viewport width */
    height: 100vh;
    /* Full viewport height */
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


<!--
<template>
ORDERS
<div v-for="order in props.orders">
    ordine {{ order.id }} - {{ order.site.lat }} ; {{ order.site.lng }}
</div>
<br>
MOTRICE
<div v-for="order in props.listMotrice">
    ordine {{ order.id }} - {{ order.site.lat }} ; {{ order.site.lng }}
</div>
<br>
RIMORCHIO
<div v-for="order in props.listRimorchio">
    ordine {{ order.id }} - {{ order.site.lat }} ; {{ order.site.lng }}
</div>
<br>
RIEMPIMENTO
<div v-for="order in props.listRiempimento">
    ordine {{ order.id }} - {{ order.site.lat }} ; {{ order.site.lng }}
</div>


<div>
    <p>ORDERS: {{ orders }}</p>
    <p>MOTRICE: {{ listMotrice }}</p>
    <p>RIMORCHIO: {{ listRimorchio }}</p>
    <p>RIEMPIMENTO: {{ listRiempimento }}</p>
  </div>
-->
