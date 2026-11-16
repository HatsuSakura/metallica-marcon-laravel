<template>
    <div class="space-y-2">
        <div class="h-64 w-full overflow-hidden rounded-xl border border-base-300" role="region" aria-label="Posizione della sede sulla mappa">
            <GoogleMap map-id="DEMO_MAP_ID" :api-key="apiKey" style="width:100%;height:100%" :center="center" :zoom="hasPosition ? 17 : 10" :fullscreen-control="false">
                <AdvancedMarker v-if="hasPosition" :options="{position:center, title:address || 'Posizione della sede', gmpDraggable:!disabled}" @dragend="move" />
            </GoogleMap>
        </div>
        <p class="text-sm">Trascina il segnaposto sull’ingresso o sul punto utile. L’indirizzo non cambia.</p>
    </div>
</template>
<script setup>
import {computed} from 'vue'
import {GoogleMap, AdvancedMarker} from 'vue3-google-map'
const props = defineProps({latitude:[Number,String], longitude:[Number,String], address:String, disabled:Boolean, apiKey:{type:String,default:import.meta.env.VITE_MAPS_API_KEY}})
const emit = defineEmits(['position-change'])
const coordinate = (value, limit) => value !== null && value !== undefined && String(value).trim() !== '' && Number.isFinite(Number(value)) && Math.abs(Number(value)) <= limit
const hasPosition = computed(() => coordinate(props.latitude,90) && coordinate(props.longitude,180))
const center = computed(() => hasPosition.value ? {lat:Number(props.latitude),lng:Number(props.longitude)} : {lat:45.7095677,lng:12.2230432})
const move = event => {
    if (props.disabled || !event.latLng) return
    const latitude = event.latLng.lat(), longitude = event.latLng.lng()
    if (coordinate(latitude,90) && coordinate(longitude,180)) emit('position-change', {latitude,longitude})
}
</script>
