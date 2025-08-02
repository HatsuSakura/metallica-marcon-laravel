<template>
    <div class="mb-4 flex flex-row items-center gap-4">
        <Link
            class="btn btn-ghost" 
            :href="route('relator.cargo.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Cassoni
        </Link>
        <div class="text-lg font-medium">
            Crea nuova tipologia cassone
        </div>
    </div>

    <form @submit.prevent="create">
      <div class="grid grid-cols-12 gap-4">
        <!-- Name -->
        <div class="col-span-3">
          <label class="label">Nome</label>
          <input v-model="form.name" type="text" class="input" />
          <div class="input-error" v-if="form.errors.name">
            {{ form.errors.name }}
          </div>
        </div>
  
        <!-- Description -->
        <div class="col-span-9">
          <label class="label">Descrizione</label>
          <textarea v-model="form.description" class="input"></textarea>
          <div class="input-error" v-if="form.errors.description">
            {{ form.errors.description }}
          </div>
        </div>
  
        <!-- Is Cargo -->
        <div class="col-span-3">
          <label class="label">Vero tipo cassone (o proprietà motrice?)</label>
          <input v-model="form.is_cargo" id="is_cargo" type="checkbox" class="toggle" />
          <div class="input-error" v-if="form.errors.is_cargo">
            {{ form.errors.is_cargo }}
          </div>
        </div>
  
        <!-- Is Long -->
        <div class="col-span-3">
          <label class="label">Cassone Lungo</label>
          <input v-model="form.is_long" id="is_long" type="checkbox" class="toggle" />
          <div class="input-error" v-if="form.errors.is_long">
            {{ form.errors.is_long }}
          </div>
        </div>
  
        <!-- Total Count -->
        <div class="col-span-3">
          <label class="label">Quantità disponibile</label>
          <input v-model.number="form.total_count" type="number" class="input" />
          <div class="input-error" v-if="form.errors.total_count">
            {{ form.errors.total_count }}
          </div>
        </div>
  
        <!-- Length -->
        <div class="col-span-3">
          <label class="label">Lunghezza (m)</label>
          <input v-model.number="form.length" type="number" step="0.01" class="input" />
          <div class="input-error" v-if="form.errors.length">
            {{ form.errors.length }}
          </div>
        </div>
  
        <!-- Casse -->
        <div class="col-span-4">
          <label class="label">Quantità Casse</label>
          <input v-model.number="form.casse" type="number" class="input" />
          <div class="input-error" v-if="form.errors.casse">
            {{ form.errors.casse }}
          </div>
        </div>
  
        <!-- Spazi Casse -->
        <div class="col-span-4">
          <label class="label">Spazi a terra Casse</label>
          <input v-model.number="form.spazi_casse" type="number" class="input" />
          <div class="input-error" v-if="form.errors.spazi_casse">
            {{ form.errors.spazi_casse }}
          </div>
        </div>
  
        <!-- Spazi Bancale -->
        <div class="col-span-4">
          <label class="label">Spazi a terra Bancale</label>
          <input v-model.number="form.spazi_bancale" type="number" class="input" />
          <div class="input-error" v-if="form.errors.spazi_bancale">
            {{ form.errors.spazi_bancale }}
          </div>
        </div>
  
        <!-- Submit Button -->
        <div class="col-span-12">
          <button type="submit" class="btn btn-primary">Aggiungi Tipo Cassone</button>
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
    is_cargo: true, // Default to Yes
    is_long: false, // Default to No
    total_count: '',
    length: null,
    casse: '',
    spazi_casse: '',
    spazi_bancale: '',
  })
  
  // Form submission
  const create = () => form.post(route('relator.cargo.store'))
  </script>
  