<template>
    <div class="mb-4 flex flex-row items-center gap-4">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.vehicle.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Automezzi
        </Link>
        <div class="text-lg font-medium">
            Crea nuovo Automezzo
        </div>
    </div>

  <form @submit.prevent="create">
    <div class="grid grid-cols-6 gap-4">
      <!-- Name -->
      <div class="col-span-2">
        <label class="label">Nome</label>
        <input v-model="form.name" type="text" class="input" />
        <div class="input-error" v-if="form.errors.name">
          {{ form.errors.name }}
        </div>
      </div>

      <!-- Description -->
      <div class="col-span-4">
        <label class="label">Descrizione</label>
        <textarea v-model="form.description" class="input"></textarea>
        <div class="input-error" v-if="form.errors.description">
          {{ form.errors.description }}
        </div>
      </div>

      <!-- Plate -->
      <div class="col-span-2">
        <label class="label">Targa</label>
        <input v-model="form.plate" type="text" class="input" />
        <div class="input-error" v-if="form.errors.plate">
          {{ form.errors.plate }}
        </div>
      </div>

      <!-- Type -->
      <div class="col-span-2">
      <label class="label">Type</label>
      <select v-model="form.type" class="input select">
        <option value="" disabled>Seleziona la tipologia</option>
        <option v-for="type in props.types" :key="type.value" :value="type.value">
          {{ type.label }}
        </option>
      </select>
      <div class="input-error" v-if="form.errors.type">
        {{ form.errors.type }}
      </div>
    </div>

          <!-- Load Capacity -->
      <div class="col-span-1">
        <label class="label">Capacità di Carico (kg)</label>
        <input v-model.number="form.load_capacity" type="number" step="0.01" class="input" />
        <div class="input-error" v-if="form.errors.load_capacity">
          {{ form.errors.load_capacity }}
        </div>
      </div>

      <!-- Has Trailer Toggle -->
      <div class="col-span-1">
        <label class="label flex items-center">
          <span class="ml-2">Può trainare rimorchi</span>
        </label>
        <input 
          v-model="form.has_trailer" 
          :disabled="isTrailerDisabled"
          id="has_trailer" 
          type="checkbox" 
          class="toggle"
        />
      </div>

      <!-- Driver -->
      <div class="col-span-3">
        <label class="label">Autista associato</label>
        <select v-model="form.driver_id" class="input select">
          <option value="" disabled>Nessun autista di default</option>
          <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
            {{ driver.name }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.driver_id">
          {{ form.errors.driver_id }}
        </div>
      </div>

      <!-- Trailer -->
      <div class="col-span-3" v-if="form.has_trailer">
        <label class="label">Rimorchio associato</label>
        <select v-model="form.trailer_id" class="input select">
          <option value="" disabled>Nessun rimorchio di default</option>
          <option v-for="trailer in props.trailers" :key="trailer.id" :value="trailer.id">
            {{ trailer.name }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.trailer_id">
          {{ form.errors.trailer_id }}
        </div>
      </div>

      <!-- Submit Button -->
      <div class="col-span-6">
        <button type="submit" class="btn btn-primary">Crea nuovo Automezzo</button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'

// Props
const props = defineProps({
  drivers: Object,
  trailers: Object,
  types: Object,
})

// Form state
const form = useForm({
  name: null,
  description: null,
  plate: null,
  type: '',
  driver_id: '',
  trailer_id: '',
  has_trailer: true, // Default to true
  load_capacity: 8000, // Default value
})

// Computed property for disabled state
const isTrailerDisabled = computed(() => {
  return form.type === 'sponda' || form.type === 'furgone';
});

watch(
  () => form.type,
  (new_type) => {
    if (new_type === 'sponda' || new_type === 'furgone' )
    form.has_trailer = false;

  }
);

// Form submission
const create = () => form.post(route('relator.vehicle.store'))
</script>
