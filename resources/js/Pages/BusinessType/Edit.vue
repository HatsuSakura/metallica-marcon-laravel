<template>
    <div class="mb-4 flex items-center gap-2">
        <Link :href="route('business-type.index')" class="btn btn-ghost">
            <font-awesome-icon :icon="['fas', 'arrow-left']" />
            Tipologie attività
        </Link>
    </div>

    <div class="max-w-lg">
        <form @submit.prevent="submit">
            <div class="flex flex-col gap-4">

                <div>
                    <label class="label">Nome <span class="text-error">*</span></label>
                    <input v-model="form.name" type="text" class="input input-bordered w-full" />
                    <div class="text-error text-sm mt-1" v-if="form.errors.name">{{ form.errors.name }}</div>
                </div>

                <div>
                    <label class="label">Descrizione</label>
                    <textarea v-model="form.description" class="textarea textarea-bordered w-full" rows="3" />
                    <div class="text-error text-sm mt-1" v-if="form.errors.description">{{ form.errors.description }}</div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">Salva</button>
                    <Link :href="route('business-type.index')" class="btn btn-ghost">Annulla</Link>
                </div>

            </div>
        </form>
    </div>
</template>

<script setup>
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
