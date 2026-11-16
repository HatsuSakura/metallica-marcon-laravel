<template>
<RegistryPage title="Modifica automezzo">

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <Link
            class="btn btn-outline"
            :href="route('vehicle.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Automezzi
        </Link>

    </div>

    <RegistryForm :form="form" editing v-slot="{canSubmit, busy}" @submit="update" >
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
        <!-- Name -->
        <div class="col-span-1 xl:col-span-2">
          <label class="label" for="registry-vehicle-form-name">Nome</label>
          <input v-model="form.name" type="text" class="input"  id="registry-vehicle-form-name" :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'registry-vehicle-form-name-error' : undefined" name="name"/>
          <div class="input-error" v-if="form.errors.name" id="registry-vehicle-form-name-error" role="alert">
            {{ form.errors.name }}
          </div>
        </div>

        <!-- Description -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-4">
          <label class="label" for="registry-vehicle-form-description">Descrizione</label>
          <textarea v-model="form.description" class="textarea textarea-bordered" id="registry-vehicle-form-description" :aria-invalid="!!form.errors.description" :aria-describedby="form.errors.description ? 'registry-vehicle-form-description-error' : undefined" name="description"></textarea>
          <div class="input-error" v-if="form.errors.description" id="registry-vehicle-form-description-error" role="alert">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Plate -->
        <div class="col-span-1 xl:col-span-2">
          <label class="label" for="registry-vehicle-form-plate">Targa</label>
          <input v-model="form.plate" type="text" class="input"  id="registry-vehicle-form-plate" :aria-invalid="!!form.errors.plate" :aria-describedby="form.errors.plate ? 'registry-vehicle-form-plate-error' : undefined" name="plate"/>
          <div class="input-error" v-if="form.errors.plate" id="registry-vehicle-form-plate-error" role="alert">
            {{ form.errors.plate }}
          </div>
        </div>

        <!-- Type -->
        <div class="col-span-1 xl:col-span-2">
        <label class="label" for="registry-vehicle-form-type">Tipologia</label>
        <select v-model="form.type" class="input select" id="registry-vehicle-form-type" :aria-invalid="!!form.errors.type" :aria-describedby="form.errors.type ? 'registry-vehicle-form-type-error' : undefined" name="type">
          <option value="" disabled>Seleziona la tipologia</option>
          <option v-for="type in props.types" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
        <div class="input-error" v-if="form.errors.type" id="registry-vehicle-form-type-error" role="alert">
          {{ form.errors.type }}
        </div>
      </div>

            <!-- Load Capacity -->
        <div class="col-span-1 xl:col-span-1">
          <label class="label" for="registry-vehicle-form-load_capacity">Capacità di Carico (kg)</label>
          <input v-model.number="form.load_capacity" type="number" step="0.01" class="input"  id="registry-vehicle-form-load_capacity" :aria-invalid="!!form.errors.load_capacity" :aria-describedby="form.errors.load_capacity ? 'registry-vehicle-form-load_capacity-error' : undefined" name="load_capacity"/>
          <div class="input-error" v-if="form.errors.load_capacity" id="registry-vehicle-form-load_capacity-error" role="alert">
            {{ form.errors.load_capacity }}
          </div>
        </div>

        <!-- Has Trailer Toggle -->
        <div class="col-span-1 xl:col-span-1">
          <label class="label flex items-center" for="has_trailer">
            <span class="ml-2">Può trainare rimorchi</span>
          </label>
          <input
            v-model="form.has_trailer"
            :disabled="isTrailerDisabled"
            id="has_trailer"
            type="checkbox"
            class="toggle"
           :aria-invalid="!!form.errors.has_trailer" name="has_trailer"/>
        </div>

        <!-- Driver -->
        <div class="col-span-1 xl:col-span-3">
          <label class="label" for="registry-vehicle-form-driver_id">Autista associato</label>
          <select v-model="form.driver_id" class="input select" id="registry-vehicle-form-driver_id" :aria-invalid="!!form.errors.driver_id" :aria-describedby="form.errors.driver_id ? 'registry-vehicle-form-driver_id-error' : undefined" name="driver_id">
            <option value="">Nessun autista di default</option>
            <option v-for="driver in props.drivers" :key="driver.id" :value="driver.id">
              {{ driver.name }}
            </option>
          </select>
          <div class="input-error" v-if="form.errors.driver_id" id="registry-vehicle-form-driver_id-error" role="alert">
            {{ form.errors.driver_id }}
          </div>
        </div>

        <!-- Trailer -->
        <div class="col-span-1 xl:col-span-3" v-if="form.has_trailer">
          <label class="label" for="registry-vehicle-form-trailer_id">Rimorchio associato</label>
          <select v-model="form.trailer_id" class="input select" id="registry-vehicle-form-trailer_id" :aria-invalid="!!form.errors.trailer_id" :aria-describedby="form.errors.trailer_id ? 'registry-vehicle-form-trailer_id-error' : undefined" name="trailer_id">
            <option value="">Nessun rimorchio di default</option>
            <option v-for="trailer in props.trailers" :key="trailer.id" :value="trailer.id">
              {{ trailer.name }} - {{ trailer.plate }}
            </option>
          </select>
          <div class="input-error" v-if="form.errors.trailer_id" id="registry-vehicle-form-trailer_id-error" role="alert">
            {{ form.errors.trailer_id }}
          </div>
        </div>

        <!-- Submit Button -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-6">
          <Link :href="route('vehicle.index')" class="btn btn-error btn-outline mr-2">Annulla</Link>
<button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined"><span v-if="busy" class="loading loading-spinner loading-sm" aria-hidden="true"></span>
            <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-lg"/>
            Salva modifiche
          </button>
        </div>
      </div>
    </RegistryForm>

</RegistryPage>
</template>

  <script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

  import { Link, useForm } from '@inertiajs/vue3'
  import { computed, watch  } from 'vue'

  // Props
  const props = defineProps({
    vehicle: Object,
    drivers: Object,
    trailers: Object,
    types: Object,
  })

  // Form state
  const form = useForm({
    name: props.vehicle.name,
    description: props.vehicle.description,
    plate: props.vehicle.plate,
    type: props.vehicle.type,
    driver_id: props.vehicle.driver_id,
    trailer_id: props.vehicle.trailer_id,
    has_trailer: Boolean(props.vehicle.has_trailer),
    load_capacity: props.vehicle.load_capacity,
  })

  // Computed property for disabled state
  const isTrailerDisabled = computed(() => {
    return form.type === 'sponda' || form.type === 'furgone';
  });

  watch(
  () => form.type,
  (new_type) => {
      if (new_type === 'sponda' || new_type === 'furgone' ) {
      form.has_trailer = false;
      form.trailer_id = null;
    }
    }
  );

  // Form submission
  const update = () => form.put(route('vehicle.update', {vehicle: props.vehicle.id}))
  </script>

