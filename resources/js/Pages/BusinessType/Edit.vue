<template>
<RegistryPage title="Modifica tipologia attività">

    <div class="mb-4 flex items-center gap-2">
        <Link :href="route('business-type.index')" class="btn btn-outline">
            <font-awesome-icon :icon="['fas', 'arrow-left']" />
            Tipologie attività
        </Link>
    </div>

    <div class="max-w-lg">
        <RegistryForm :form="form" editing v-slot="{canSubmit, busy}" @submit="submit" >
            <div class="flex flex-col gap-4">

                <div>
                    <label class="label" for="registry-businesstype-form-name">Nome <span class="text-error">*</span></label>
                    <input v-model="form.name" type="text" class="input input-bordered w-full"  id="registry-businesstype-form-name" :aria-invalid="!!form.errors.name" :aria-describedby="form.errors.name ? 'registry-businesstype-form-name-error' : undefined" name="name"/>
                    <div class="text-error text-sm mt-1" v-if="form.errors.name" id="registry-businesstype-form-name-error" role="alert">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="label" for="registry-businesstype-form-description">Descrizione</label>
                    <textarea v-model="form.description" class="textarea textarea-bordered w-full" rows="3"  id="registry-businesstype-form-description" :aria-invalid="!!form.errors.description" :aria-describedby="form.errors.description ? 'registry-businesstype-form-description-error' : undefined" name="description"/>
                    <div class="text-error text-sm mt-1" v-if="form.errors.description" id="registry-businesstype-form-description-error" role="alert">{{ form.errors.description }}</div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-success" :disabled="!canSubmit" :aria-busy="busy || undefined">Salva</button>
                    <Link :href="route('business-type.index')" class="btn btn-error btn-outline">Annulla</Link>
                </div>

            </div>
        </RegistryForm>
    </div>

</RegistryPage>
</template>

<script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    businessType: Object,
});

const form = useForm({
    name:        props.businessType.name,
    description: props.businessType.description ?? '',
});

const submit = () => form.put(route('business-type.update', props.businessType.id));
</script>
