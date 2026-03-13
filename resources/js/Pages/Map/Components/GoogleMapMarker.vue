<template>
  <AdvancedMarker :key="props.site.id" :id="props.site.id" :options="options">
    <GoogleMapsInfoWindow v-if="props.withInfoWindow" :site="props.site" :backgroundColor="backgroundColor"/>
  </AdvancedMarker>
</template>

<script setup>
import { AdvancedMarker } from 'vue3-google-map';
import { computed, ref, watchEffect } from 'vue';
import GoogleMapsInfoWindow from './GoogleMapsInfoWindow.vue';
import {CUSTOM_MARKER_ELEMENTS} from "@/googleMapsConfig"
import { getIconForSite } from '@/Composables/getIconForSite';


const props = defineProps({
  site: Object,
  withInfoWindow: Boolean,
});

const siteRef = computed(() => props.site);
const {buildingType, backgroundColor, borderColor} = getIconForSite(siteRef);

// Reference to hold the generated SVG content
const pinSvg = ref(null);

// Generating SVG content
watchEffect(() => {
  const pinSvgContent =
    `<svg xmlns="http://www.w3.org/2000/svg"  height="${CUSTOM_MARKER_ELEMENTS.defaultPinSize}" viewBox="${CUSTOM_MARKER_ELEMENTS.PinSvgViewBox}">
      <path fill="${backgroundColor.value}" stroke="${borderColor.value}" stroke-width="2" d="${CUSTOM_MARKER_ELEMENTS.PinSvgPath}"/>
      <g transform="translate(7, 8) scale(0.03)">
        <path fill="white" d="${buildingType.value}"/>
      </g>
    </svg>`;

  pinSvg.value = new DOMParser().parseFromString(pinSvgContent, "image/svg+xml").documentElement;
});


const options = computed(() => ({
  id: props.site.id,
  position: { lat: props.site.latitude, lng: props.site.longitude },
  content: pinSvg.value,
  title: props.site.customer ? `${props.site.customer.company_name} - ${props.site.name}` : props.site.name,
  gmpClickable: true, // Ensures the marker is clickable
}));

</script>
