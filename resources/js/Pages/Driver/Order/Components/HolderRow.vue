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
        v-model.number="holder.filled_holders_count" 
        type="number" 
        step="1"
        class="input input-bordered w-32"
        placeholder="Quantità"
        min="1"
      />
      
      <label class="label" for="holder">Vuoti richieste:</label>
      <input 
        v-model.number="holder.empty_holders_count" 
        type="number" 
        step="1"
        class="input input-bordered w-32"
        placeholder="+/- Quantità"
      />
  
      <label class="label" for="holder">Totale da consegnare:</label>
      <input 
        v-model.number="holder.total_holders_count" 
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
  
  watch(() => [props.holder.filled_holders_count, props.holder.empty_holders_count], () => {
    props.holder.total_holders_count = Number(props.holder.filled_holders_count || 0) + Number(props.holder.empty_holders_count || 0);
  }, { immediate: true });
  </script>
  