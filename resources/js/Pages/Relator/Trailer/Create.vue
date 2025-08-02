<template>
    <div class="mb-4 flex flex-row items-center gap-4">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.trailer.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Rimorchi
        </Link>
        <div class="text-lg font-medium">
            Crea nuovo rimorchio
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
  
        <!-- Is Front Cargo -->
        <div class="col-span-2">
          <label class="label">Carica dal lato Motrice</label>
          <input v-model="form.is_front_cargo" id="is_front_cargo" type="checkbox" class="toggle" />
          <div class="input-error" v-if="form.errors.is_front_cargo">
            {{ form.errors.is_front_cargo }}
          </div>
        </div>
  
        <!-- Load Capacity -->
        <div class="col-span-2">
          <label class="label">Capacità di Carico (kg)</label>
          <input v-model.number="form.load_capacity" type="number" class="input" />
          <div class="input-error" v-if="form.errors.load_capacity">
            {{ form.errors.load_capacity }}
          </div>
        </div>
  
        <!-- Submit Button -->
        <div class="col-span-6">
          <button type="submit" class="btn btn-primary">Aggiungi Rimorchio</button>
        </div>
      </div>
    </form>
  </template>
  
  <script setup>
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
  const create = () => form.post(route('relator.trailer.store'))
  </script>
  