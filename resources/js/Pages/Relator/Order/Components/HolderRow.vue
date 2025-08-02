<template>
    <div class="flex flex-row items-start gap-2 mb-2">
      <select v-model="holder.holder_id" id="holder" class="select select-bordered">
        <option disabled value="">Seleziona un contenitore</option>
        <option v-for="h in holders" :key="h.id" :value="h.id">
          {{ h.name }} <!-- Assuming user model has a 'name' field -->
        </option>
      </select>
  
      <label class="label" for="holder">Pieni da ritirare:</label>
      <input 
        v-model.number="holder.holder_piene" 
        type="number" 
        step="1"
        class="input input-bordered w-32"
        placeholder="Quantità"
        min="0"
      />
      
      <label class="label" for="holder">Vuoti richieste:</label>
      <input 
        v-model.number="holder.holder_vuote" 
        type="number" 
        step="1"
        class="input input-bordered w-32"
        placeholder="+/- quantità"
      />
  
      <label class="label" for="holder">Totale da consegnare:</label>
      <input 
        v-model.number="holder.holder_totale" 
        type="number" 
        class="input input-bordered w-32 font-medium"
        placeholder="Totale"
        disabled
      />
  
      <button @click="$emit('remove')" class="btn btn-error btn-circle">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
    </div>
  </template>
  
  <script setup>
  import { watch, computed } from 'vue';
  
  const props = defineProps({ 
    holder: Object, // Ensure the parent is passing a reactive object
    holders: Array, // List of Holder Types taken from DB
  });
  
  watch(() => [props.holder.holder_piene, props.holder.holder_vuote], () => {
    props.holder.holder_totale = Number(props.holder.holder_piene || 0) + Number(props.holder.holder_vuote || 0);
  }, { immediate: true });
  </script>
  