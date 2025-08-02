<template>
  <InfoWindow :options="options" v-model="infowindow">

<div>
    <!-- CUSTOMER -->
    <div class="name flex justify-between space-x-4">
      <div class="text-lg flex items-center space-x-2">
        <font-awesome-icon :icon="['fas', 'address-card']" class="text-2xl"/>
        <span>{{props.order.customer.ragione_sociale}}</span>
      </div>
    </div>

    <!-- SEDE -->
    <div class="name flex justify-between space-x-4">
      <div class="text-lg space-x-2 flex items-center">
        <font-awesome-icon :icon="siteIcon" class="text-2xl"/>
        <span v-if="props.order.site.tipologia === 'fully_operative'" class="text-2xl">+</span>
        <font-awesome-icon v-if="props.order.site.tipologia === 'fully_operative'" :icon="['fas', 'warehouse']"  class="text-2xl"/> 
        <span>{{props.order.site.denominazione}}</span>   
      </div>
    </div>
    
    <!-- RIGA INDIRIZZO -->
    <div class="address my-2">{{props.order.site.indirizzo}}</div>

    <!-- INFO BOTTOM -->
    <div class="features flex justify-between">

      <div class="tooltip" data-tip="+ Motrice">
        <button 
          class="btn btn-circle" 
          :class="props.list=='listMotrice' ? 'btn-success' : '' "
          @click="setToTruck(props.order)"
        >
            <font-awesome-icon :icon="['fas', 'truck']" class="text-2xl"/>
        </button>
      </div>

      <div class="tooltip" data-tip="+ Rimorchio">
        <button 
          class="btn btn-circle" 
          :class="props.list=='listRimorchio' ? 'btn-success' : '' "
          @click="setToTrailer(props.order)"
        >
            <font-awesome-icon :icon="['fas', 'trailer']" class="text-2xl"/>
        </button>
      </div>

      <div class="tooltip" data-tip="+ Riempimento">
        <button 
          class="btn btn-circle" 
          :class="props.list=='listRiempimento' ? 'btn-success' : '' "
          @click="setToRiempimento(props.order)"
        >
            <font-awesome-icon :icon="['fas', 'route']" class="text-2xl"/>
        </button>
      </div>

      <div class="tooltip" data-tip="Reset">
        <button 
          class="btn btn-circle" 
          :class="props.list=='orders' ? 'btn-success' : 'btn-error' "
          @click="setToOrders(props.order)"
        >
            <font-awesome-icon :icon="['fas', 'list']" class="text-2xl"/>
        </button>
      </div>

    </div>

</div>

  </InfoWindow>
</template>

<script setup>
import { InfoWindow } from 'vue3-google-map';
import { computed } from 'vue';
import eventBus from '@/eventBus';

const props = defineProps({
  order: Object,
  list: String,
  backgroundColor: String,
})

console.log('ORDER', props.order)

const options = computed(() => ({
  position: { lat: props.order.site.lat, lng: props.order.site.lng },
  title: props.order.site.denominazione,
}));


const siteIcon = computed(() => {
  return props.order.site.tipologia === 'fully_operative'
    ? ['fas', 'building-user']
    : props.order.site.tipologia === 'only_legal'
    ? ['fas', 'building-user']
    : props.order.site.tipologia === 'only_stock'
    ? ['fas', 'warehouse']
    : ['fas', 'building-user']; // Default icon if none match
});

const setToTruck = (order) => {
  eventBus.emit('setOrderToTruckList', order);
}

const setToTrailer = (order) => {
  eventBus.emit('setOrderToTrailerList', order);
}

const setToRiempimento = (order) => {
  eventBus.emit('setOrderToRiempimentoList', order);
}

const setToOrders = (order) => {
  eventBus.emit('setOrderToOrdersList', order);
}

</script>
