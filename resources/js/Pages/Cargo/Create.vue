<template>
<RegistryPage title="Nuovo cassone">
<template #back><Link
            class="btn btn-outline"
            :href="route('cargo.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Cassoni
        </Link></template>



    <RegistryForm :form="form" v-slot="{canSubmit, busy}" @submit="create" >
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-4">
        <!-- Name -->
        <div class="col-span-1 xl:col-span-3">
          <label class="label" for="registry-cargo-form-name">Nome</label>
          <input v-model="form.name" type="text" class="input"  id="registry-cargo-form-name" :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'registry-cargo-form-name-error' : undefined" name="name"/>
          <div class="input-error" v-if="form.errors.name" id="registry-cargo-form-name-error" role="alert">
            {{ form.errors.name }}
          </div>
        </div>

        <!-- Description -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-9">
          <label class="label" for="registry-cargo-form-description">Descrizione</label>
          <textarea v-model="form.description" class="textarea textarea-bordered" id="registry-cargo-form-description" :aria-invalid="!!form.errors.description" :aria-describedby="form.errors.description ? 'registry-cargo-form-description-error' : undefined" name="description"></textarea>
          <div class="input-error" v-if="form.errors.description" id="registry-cargo-form-description-error" role="alert">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Is Cargo -->
        <div class="col-span-1 xl:col-span-3">
          <label class="label" for="is_cargo">Vero tipo cassone (o proprietà motrice?)</label>
          <input v-model="form.is_cargo" id="is_cargo" type="checkbox" class="toggle"  :aria-invalid="!!form.errors.is_cargo" :aria-describedby="form.errors.is_cargo ? 'is_cargo-error' : undefined" name="is_cargo"/>
          <div class="input-error" v-if="form.errors.is_cargo" id="is_cargo-error" role="alert">
            {{ form.errors.is_cargo }}
          </div>
        </div>

        <!-- Is Long -->
        <div class="col-span-1 xl:col-span-3">
          <label class="label" for="is_long">Cassone Lungo</label>
          <input v-model="form.is_long" id="is_long" type="checkbox" class="toggle"  :aria-invalid="!!form.errors.is_long" :aria-describedby="form.errors.is_long ? 'is_long-error' : undefined" name="is_long"/>
          <div class="input-error" v-if="form.errors.is_long" id="is_long-error" role="alert">
            {{ form.errors.is_long }}
          </div>
        </div>

        <!-- Total Count -->
        <div class="col-span-1 xl:col-span-3">
          <label class="label" for="registry-cargo-form-total_count">Quantità disponibile</label>
          <input v-model.number="form.total_count" type="number" class="input"  id="registry-cargo-form-total_count" :aria-invalid="!!form.errors.total_count" :aria-describedby="form.errors.total_count ? 'registry-cargo-form-total_count-error' : undefined" name="total_count"/>
          <div class="input-error" v-if="form.errors.total_count" id="registry-cargo-form-total_count-error" role="alert">
            {{ form.errors.total_count }}
          </div>
        </div>

        <!-- Length -->
        <div class="col-span-1 xl:col-span-3">
          <label class="label" for="registry-cargo-form-length">Lunghezza (m)</label>
          <input v-model.number="form.length" type="number" step="0.01" class="input"  id="registry-cargo-form-length" :aria-invalid="!!form.errors.length" :aria-describedby="form.errors.length ? 'registry-cargo-form-length-error' : undefined" name="length"/>
          <div class="input-error" v-if="form.errors.length" id="registry-cargo-form-length-error" role="alert">
            {{ form.errors.length }}
          </div>
        </div>

        <!-- Casse -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-4">
          <label class="label" for="registry-cargo-form-casse">Quantità Casse</label>
          <input v-model.number="form.casse" type="number" class="input"  id="registry-cargo-form-casse" :aria-invalid="!!form.errors.casse" :aria-describedby="form.errors.casse ? 'registry-cargo-form-casse-error' : undefined" name="casse"/>
          <div class="input-error" v-if="form.errors.casse" id="registry-cargo-form-casse-error" role="alert">
            {{ form.errors.casse }}
          </div>
        </div>

        <!-- Spazi Casse -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-4">
          <label class="label" for="registry-cargo-form-spazi_casse">Spazi a terra Casse</label>
          <input v-model.number="form.spazi_casse" type="number" class="input"  id="registry-cargo-form-spazi_casse" :aria-invalid="!!form.errors.spazi_casse" :aria-describedby="form.errors.spazi_casse ? 'registry-cargo-form-spazi_casse-error' : undefined" name="spazi_casse"/>
          <div class="input-error" v-if="form.errors.spazi_casse" id="registry-cargo-form-spazi_casse-error" role="alert">
            {{ form.errors.spazi_casse }}
          </div>
        </div>

        <!-- Spazi Bancale -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-4">
          <label class="label" for="registry-cargo-form-spazi_bancale">Spazi a terra Bancale</label>
          <input v-model.number="form.spazi_bancale" type="number" class="input"  id="registry-cargo-form-spazi_bancale" :aria-invalid="!!form.errors.spazi_bancale" :aria-describedby="form.errors.spazi_bancale ? 'registry-cargo-form-spazi_bancale-error' : undefined" name="spazi_bancale"/>
          <div class="input-error" v-if="form.errors.spazi_bancale" id="registry-cargo-form-spazi_bancale-error" role="alert">
            {{ form.errors.spazi_bancale }}
          </div>
        </div>

        <!-- Submit Button -->
        <div class="col-span-1 sm:col-span-2 xl:col-span-12">
          <Link :href="route('cargo.index')" class="btn btn-error btn-outline mr-2">Annulla</Link>
<button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined"><span v-if="busy" class="loading loading-spinner loading-sm" aria-hidden="true"></span>Aggiungi Tipo Cassone</button>
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
    is_cargo: true, // Default to Yes
    is_long: false, // Default to No
    total_count: '',
    length: null,
    casse: '',
    spazi_casse: '',
    spazi_bancale: '',
  })

  // Form submission
  const create = () => form.post(route('cargo.store'))
  </script>

