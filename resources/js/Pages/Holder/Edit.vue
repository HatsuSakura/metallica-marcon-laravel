<template>
<RegistryPage title="Modifica contenitore">

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <Link
            class="btn btn-outline"
            :href="route('holder.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Contenitori
        </Link>

    </div>

    <RegistryForm :form="form" editing v-slot="{canSubmit, busy}" @submit="edit" >
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-12 gap-4">
            <div class="col-span-1 sm:col-span-2 xl:col-span-4">
                <label class="label" for="registry-holder-form-name">Nome</label>
                <input v-model="form.name" type="text" class="input"  id="registry-holder-form-name" :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'registry-holder-form-name-error' : undefined" name="name"/>
                <div class="input-error" v-if="form.errors.name" id="registry-holder-form-name-error" role="alert">
                    {{ form.errors.name }}
                </div>
            </div>

            <div class="col-span-1 sm:col-span-2 xl:col-span-8">
                <label class="label" for="registry-holder-form-description">Descrizione</label>
                <textarea v-model="form.description" class="textarea textarea-bordered" id="registry-holder-form-description" :aria-invalid="!!form.errors.description" :aria-describedby="form.errors.description ? 'registry-holder-form-description-error' : undefined" name="description"></textarea>
                <div class="input-error" v-if="form.errors.description" id="registry-holder-form-description-error" role="alert">
                    {{ form.errors.description }}
                </div>
            </div>

            <div class="col-span-1 xl:col-span-3">
                <label class="label" for="registry-holder-form-volume">Volume (m3)</label>
                <input v-model.number="form.volume" type="number" step="0.01" class="input"  id="registry-holder-form-volume" :aria-invalid="!!form.errors.volume" :aria-describedby="form.errors.volume ? 'registry-holder-form-volume-error' : undefined" name="volume"/>
                <div class="input-error" v-if="form.errors.volume" id="registry-holder-form-volume-error" role="alert">
                    {{ form.errors.volume }}
                </div>
            </div>

            <div class="col-span-1 sm:col-span-2 xl:col-span-6">
                <label class="label" for="registry-holder-form-equivalent_holder_id">Equivalenza con</label>
                <select v-model="form.equivalent_holder_id" class="select select-bordered w-full" id="registry-holder-form-equivalent_holder_id" :aria-invalid="!!form.errors.equivalent_holder_id" :aria-describedby="form.errors.equivalent_holder_id ? 'registry-holder-form-equivalent_holder_id-error' : undefined" name="equivalent_holder_id">
                    <option :value="null">Nessuna equivalenza</option>
                    <option v-for="holder in props.holders" :key="holder.id" :value="holder.id">
                        {{ holder.name }}
                    </option>
                </select>
                <div class="input-error" v-if="form.errors.equivalent_holder_id" id="registry-holder-form-equivalent_holder_id-error" role="alert">
                    {{ form.errors.equivalent_holder_id }}
                </div>
            </div>

            <div class="col-span-1 xl:col-span-3">
                <label class="label" for="registry-holder-form-equivalent_units">Unita equivalenti</label>
                <input
                    v-model.number="form.equivalent_units"
                    type="number"
                    step="1"
                    class="input"
                    :disabled="!form.equivalent_holder_id"
                 id="registry-holder-form-equivalent_units" :aria-invalid="!!form.errors.equivalent_units" :aria-describedby="form.errors.equivalent_units ? 'registry-holder-form-equivalent_units-error' : undefined" name="equivalent_units"/>
                <div class="input-error" v-if="form.errors.equivalent_units" id="registry-holder-form-equivalent_units-error" role="alert">
                    {{ form.errors.equivalent_units }}
                </div>
            </div>

            <div class="col-span-1 sm:col-span-2 xl:col-span-12">
                <Link :href="route('holder.index')" class="btn btn-error btn-outline mr-2">Annulla</Link>
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

const props = defineProps({
    holder: Object,
    holders: Array,
})

const form = useForm({
    name: props.holder.name,
    description: props.holder.description,
    volume: props.holder.volume,
    equivalent_holder_id: props.holder.equivalent_holder_id,
    equivalent_units: props.holder.equivalent_units,
    is_custom: false,
})

const edit = () => form.put(route('holder.update', { holder: props.holder.id }))
</script>
