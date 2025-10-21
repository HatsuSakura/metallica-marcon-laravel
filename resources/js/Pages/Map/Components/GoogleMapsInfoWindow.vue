<template>
  <InfoWindow :options="options" v-model="infowindow">


<div>
    <!-- CUSTOMER -->
    <div class="name flex justify-between space-x-4">
      <div class="text-lg flex items-center space-x-2">
        <font-awesome-icon :icon="['fas', 'address-card']" class="text-2xl"/>
        <span>{{props.site.owner.ragione_sociale}}</span>
      </div>
      <div class="action">
        <div class="tooltip tooltip-left" data-tip="Modifica Cliente">
          <button class="btn btn-circle">
            <font-awesome-icon :icon="['fas', 'pencil']" class="text-2xl"/>
          </button>
        </div>
      </div>
    </div>

    <!-- SEDE -->
    <div class="name flex justify-between space-x-4">
      <div class="text-lg space-x-2 flex items-center">
        <font-awesome-icon :icon="siteIcon" class="text-2xl"/>
        <span v-if="props.site.tipologia === 'fully_operative'" class="text-2xl">+</span>
        <font-awesome-icon v-if="props.site.tipologia === 'fully_operative'" :icon="['fas', 'warehouse']"  class="text-2xl"/> 

        <span>{{props.site.denominazione}}</span>   

      
      </div>
      <div class="action">
        <div class="tooltip tooltip-left" data-tip="Info Sede">
          <button class="btn btn-circle"
          @click="openInfoSite(props.site) , openDrawer"
          >
            <font-awesome-icon :icon="['fas', 'circle-info']" class="text-2xl"/>
          </button>
        </div>
      </div>
    </div>
    
    <!-- RIGA INDIRIZZO -->
    <div class="address my-2">{{props.site.indirizzo}}</div>

    <!-- INFO BOTTOM -->
    <div class="features flex justify-between">
      <div class="tooltip" data-tip="Fat. Rischio">
        <div class="flex items-center justify-between space-x-2 ">
          <font-awesome-icon :icon="['fas', 'warning']" class="text-2xl" :style="{ color: props.backgroundColor }"/>
          <span>{{props.site.fattore_rischio_calcolato}}</span>
        </div>
      </div>

      <div class="tooltip" data-tip="Giorni al prossimo ritiro">
        <div class="flex items-center justify-between space-x-2 ">
          <font-awesome-icon :icon="['fas', 'calendar-day']" class="text-2xl"/>
          <span>{{props.site.giorni_prossimo_ritiro}}</span>
        </div>
      </div>

      <div class="tooltip" data-tip="Commerciale">
        <div class="flex items-center justify-between space-x-2 ">
          <font-awesome-icon :icon="['fas', 'briefcase']" class="text-2xl"/>
          <span>{{props.site.owner.seller.name}}</span>
        </div>
      </div>

    </div>

</div>

  </InfoWindow>
</template>

<script setup>
import { InfoWindow } from 'vue3-google-map';
import { computed } from 'vue';
import SiteSpace from '@/Components/SiteSpace.vue';
import { useStore } from 'vuex';
import eventBus from '@/eventBus';

const props = defineProps({
  site: Object,
  backgroundColor: String,
})

const options = computed(() => ({
  position: { lat: props.site.lat, lng: props.site.lng },
  title: props.site.denominazione,
}));


const siteIcon = computed(() => {
  return props.site.tipologia === 'fully_operative'
    ? ['fas', 'building-user']
    : props.site.tipologia === 'only_legal'
    ? ['fas', 'building-user']
    : props.site.tipologia === 'only_stock'
    ? ['fas', 'warehouse']
    : ['fas', 'building-user']; // Default icon if none match
});

// Access the Vuex store
const store = useStore();

// Method to select the current site
const openInfoSite = (site) => {
    store.dispatch('setCurrentSite', site); // Dispatch action to set current site
    eventBus.emit('openMapInfoDrawer', true); // or false to close

};

</script>
