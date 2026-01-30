<template>
    <AdvancedMarker :key="props.order.site.id" :id="props.order.site.id" :options="options">
      <OrderMapInfoWindow v-if="props.withInfoWindow" :order="props.order" :backgroundColor="backgroundColor" :list="props.list"/>
    </AdvancedMarker>
  </template>
  
  <script setup>
  import { AdvancedMarker } from 'vue3-google-map';
  import { computed, ref, watchEffect } from 'vue';
  import OrderMapInfoWindow from './OrderMapInfoWindow.vue';
  import { CUSTOM_MARKER_ELEMENTS } from "@/googleMapsConfig"
  import { getIconForOrder } from '@/Composables/getIconForOrder';


  
  
  const props = defineProps({
    order: Object,
    withInfoWindow: Boolean,
    list: String,
  });
  
  const {buildingType, backgroundColor, borderColor} = getIconForOrder(props.order.site, props.list);
  
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
    id: props.order.site.id,
    position: { lat: props.order.site.lat, lng: props.order.site.lng },
    content: pinSvg.value,
    title: props.order.site.customer ? `${props.order.site.customer.ragione_sociale} - ${props.order.site.denominazione}` : props.order.site.denominazione,
    gmpClickable: true, // Ensures the marker is clickable
  }));
  
  </script>