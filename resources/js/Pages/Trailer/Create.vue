<template>
<RegistryPage title="Nuovo rimorchio">

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <Link
            class="btn btn-outline"
            :href="route('trailer.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Rimorchi
        </Link>

    </div>

    <RegistryForm :form="form" v-slot="{canSubmit, busy}" @submit="create" >
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
        <!-- Name -->
        <div class="col-span-1 xl:col-span-2">
          <label class="label" for="registry-trailer-form-name">Nome</label>
          <input v-model="form.name" type="text" class="input"  id="registry-trailer-form-name" :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'registry-trailer-form-name-error' : undefined" name="name"/>
          <div class="input-error" v-if="form.errors.name" id="registry-trailer-form-name-error" role="alert">
            {{ form.errors.name }}
          </div>
        </div>

        <!-- Description -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-4">
          <label class="label" for="registry-trailer-form-description">Descrizione</label>
          <textarea v-model="form.description" class="textarea textarea-bordered" id="registry-trailer-form-description" :aria-invalid="!!form.errors.description" :aria-describedby="form.errors.description ? 'registry-trailer-form-description-error' : undefined" name="description"></textarea>
          <div class="input-error" v-if="form.errors.description" id="registry-trailer-form-description-error" role="alert">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Plate -->
        <div class="col-span-1 xl:col-span-2">
          <label class="label" for="registry-trailer-form-plate">Targa</label>
          <input v-model="form.plate" type="text" class="input"  id="registry-trailer-form-plate" :aria-invalid="!!form.errors.plate" :aria-describedby="form.errors.plate ? 'registry-trailer-form-plate-error' : undefined" name="plate"/>
          <div class="input-error" v-if="form.errors.plate" id="registry-trailer-form-plate-error" role="alert">
            {{ form.errors.plate }}
          </div>
        </div>

        <!-- Is Front Cargo -->
        <div class="col-span-1 xl:col-span-2">
          <label class="label" for="is_front_cargo">Carica dal lato Motrice</label>
          <input v-model="form.is_front_cargo" id="is_front_cargo" type="checkbox" class="toggle"  :aria-invalid="!!form.errors.is_front_cargo" :aria-describedby="form.errors.is_front_cargo ? 'is_front_cargo-error' : undefined" name="is_front_cargo"/>
          <div class="input-error" v-if="form.errors.is_front_cargo" id="is_front_cargo-error" role="alert">
            {{ form.errors.is_front_cargo }}
          </div>
        </div>

        <!-- Load Capacity -->
        <div class="col-span-1 xl:col-span-2">
          <label class="label" for="registry-trailer-form-load_capacity">Capacità di Carico (kg)</label>
          <input v-model.number="form.load_capacity" type="number" class="input"  id="registry-trailer-form-load_capacity" :aria-invalid="!!form.errors.load_capacity" :aria-describedby="form.errors.load_capacity ? 'registry-trailer-form-load_capacity-error' : undefined" name="load_capacity"/>
          <div class="input-error" v-if="form.errors.load_capacity" id="registry-trailer-form-load_capacity-error" role="alert">
            {{ form.errors.load_capacity }}
          </div>
        </div>

        <!-- Submit Button -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-6">
          <Link :href="route('trailer.index')" class="btn btn-error btn-outline mr-2">Annulla</Link>
<button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined"><span v-if="busy" class="loading loading-spinner loading-sm" aria-hidden="true"></span>Aggiungi Rimorchio</button>
        </div>
      </div>
    </RegistryForm>

</RegistryPage>
</template>

  <script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

  import { Link, useForm } from '@inertiajs/vue3'

  // Form state
  const form = useForm({
    name: null,
    description: null,
    plate: null,
    is_front_cargo: 1, // Default to true (yes)
    load_capacity: 11000, // Default value
  })

  // Form submission
  const create = () => form.post(route('trailer.store'))
  </script>

