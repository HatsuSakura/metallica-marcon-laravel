<template>
<div class="drawer drawer-end">
  <input id="mapInfoDrawer" type="checkbox" class="drawer-toggle" v-model="isMapInfoDrawerOpen" />
  <div class="drawer-content">

    <div class="custom-map-floating-filters drop-shadow-xl">
      <div class="collapse collapse-arrow border border-base-300 bg-base-100 rounded-box">
        <input type="checkbox" />
        <div class="collapse-title text-lg font-medium">
          <span class="text-lg mr-4">
            <font-awesome-icon :icon="['fas', 'filter']" />
          </span>
          Filtri dinamici
        </div>
        <div class="collapse-content">
          <MapSiteFilters :filters="filters" />
        </div>
      </div>
    </div>

    <div class="custom-map-info-panel drop-shadow-xl">
      <label for="mapInfoDrawer" class="drawer-button btn btn-circle btn-primary">
        <font-awesome-icon :icon="['fas', 'circle-info']" class="text-2xl" />
      </label>
    </div>

    <div class="custom-map-nlp-panel drop-shadow-xl">
      <button class="btn btn-circle btn-secondary" @click="toggleNlpPanel">
        <font-awesome-icon :icon="['fas', 'wand-magic-sparkles']" class="text-xl" />
      </button>
    </div>

    <div v-if="isNlpPanelOpen" class="custom-map-nlp-drawer bg-base-100 border border-base-300 rounded-lg p-4">
      <div class="flex justify-between items-center mb-2">
        <div class="text-sm font-semibold">NLP Query</div>
        <button class="btn btn-ghost btn-sm btn-circle" @click="isNlpPanelOpen = false">
          <font-awesome-icon :icon="['fas', 'xmark']" />
        </button>
      </div>

      <NlpAssistantPanel
        :sites="props.sites"
        v-model:respectFilters="respectFilters"
        :filteredCount="nlpFilteredCount"
        @apply-candidates="handleApplyCandidates"
        @clear-candidates="handleClearCandidates"
      />
    </div>

    <GoogleMapLoader
      :mapConfig="mapConfig"
      :mapCenter="mapCenter"
      :zoomLevel="zoomLevel"
      :mapApiKey="mapApiKey"
      :markers="selectedSites"
      :fitBounds="nlpFitBounds"
    />

  </div>
  <div class="drawer-side z-50 pt-0">
    <label for="mapInfoDrawer" aria-label="close sidebar" class="drawer-overlay"></label>
    <div class="menu bg-base-200 text-base-content min-h-full min-w-80 w-1/2 p-4">
      <MapInfoPanel />
      <ul>
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
import NlpAssistantPanel from './Index/Components/NlpAssistantPanel.vue';
import eventBus from '@/eventBus';

const mapConfig = null;
const props = defineProps({
  sites: Array,
  filters: Object,
});

// ── NLP state ────────────────────────────────────────────────────────────────

// Raw sites from the last NLP API response (may include sites outside map filters)
const nlpRawSites = ref(null);

// Toggle: intersect NLP results with current map filter set. Default ON.
const respectFilters = ref(localStorage.getItem('nlp_respect_filters') !== 'false');
watch(respectFilters, (val) => localStorage.setItem('nlp_respect_filters', String(val)));

// Reactive intersection — recomputes instantly on toggle change, no API call
const nlpActiveSites = computed(() => {
  if (!Array.isArray(nlpRawSites.value)) return null;
  if (!respectFilters.value) return nlpRawSites.value;
  const filteredIds = new Set(props.sites.map((s) => Number(s.id)));
  return nlpRawSites.value.filter((s) => filteredIds.has(Number(s.id)));
});

const nlpFilteredCount = computed(() => nlpActiveSites.value?.length ?? 0);

// Bbox follows active sites in real-time
const nlpFitBounds = ref(null);
watch(nlpActiveSites, (sites) => {
  nlpFitBounds.value = sites ? computeBbox(sites) : null;
});

function computeBbox(sites) {
  const valid = sites.filter((s) => s.latitude != null && s.longitude != null);
  if (!valid.length) return null;
  let south = Infinity, north = -Infinity, west = Infinity, east = -Infinity;
  for (const s of valid) {
    south = Math.min(south, s.latitude);
    north = Math.max(north, s.latitude);
    west  = Math.min(west,  s.longitude);
    east  = Math.max(east,  s.longitude);
  }
  return { south, north, west, east };
}

// Markers: NLP active set when present, otherwise all map-filtered sites
const selectedSites = computed(() => {
  if (Array.isArray(nlpActiveSites.value)) return nlpActiveSites.value;
  return Array.isArray(props.sites) ? props.sites : [];
});

const handleApplyCandidates = ({ sites }) => {
  nlpRawSites.value = Array.isArray(sites) ? sites : [];
};

const handleClearCandidates = () => {
  nlpRawSites.value = null;
  nlpFitBounds.value = null;
};

// ── Map UI state ──────────────────────────────────────────────────────────────

const isNlpPanelOpen = ref(false);
const isMapInfoDrawerOpen = ref(false);

const toggleNlpPanel = () => { isNlpPanelOpen.value = !isNlpPanelOpen.value; };
const openMapInfoDrawer = () => { isMapInfoDrawerOpen.value = true; };
const closeMapInfoDrawer = () => { isMapInfoDrawerOpen.value = false; };

onMounted(() => eventBus.on('openMapInfoDrawer', openMapInfoDrawer));
onUnmounted(() => eventBus.off('openMapInfoDrawer', closeMapInfoDrawer));

// ── Map config ────────────────────────────────────────────────────────────────

const mapCenter = { lat: 45.0440723, lng: 10.8413916 };
const zoomLevel = 6;
const mapApiKey = "AIzaSyB_Q0-uG59EtZ6VpSc77FVuSBvIgpg_79Q";
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

.custom-map-floating-filters .collapse > .collapse-title,
.custom-map-floating-filters .collapse > input[type="checkbox"] {
  min-height: 2.5rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.custom-map-floating-filters .collapse-arrow > .collapse-title:after {
  top: 50%;
  --tw-translate-y: -50%;
}

.custom-map-info-panel {
  z-index: 10;
  position: fixed;
  width: auto;
  top: 64px;
  right: 0px;
  padding: 1em;
}

.custom-map-nlp-panel {
  z-index: 10;
  position: fixed;
  width: auto;
  top: 64px;
  right: 72px;
  padding: 1em;
}

.custom-map-nlp-drawer {
  z-index: 30;
  position: fixed;
  top: 124px;
  right: 16px;
  width: min(460px, calc(100vw - 32px));
  max-height: calc(100vh - 140px);
  overflow: auto;
}
</style>

<style>
.gm-style-iw-d {
  padding: 0em 2em 2em 1em !important;
  overflow: hidden !important;
}
</style>
