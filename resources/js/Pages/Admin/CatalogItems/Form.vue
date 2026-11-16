<script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

import AppAction from '@/Components/UI/AppAction.vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({ item: Object })

const form = useForm({
  name: props.item?.name || '',
  type: props.item?.type || ''
})

function submit() {
  if (props.item) {
    form.put(route('catalog-items.update', props.item.id))
  } else {
    form.post(route('catalog-items.store'))
  }
}
</script>

<template>
    <RegistryPage :title="props.item ? 'Modifica materiale / componente' : 'Nuovo materiale / componente'">
<template #back><AppAction :as="Link" :href="route('catalog-items.index')">Torna al catalogo</AppAction></template>

        <RegistryForm :form="form" :editing="!!props.item" v-slot="{canSubmit, busy}" @submit="submit" class="max-w-3xl">
            <div class="space-y-4">
                <label class="form-control"><span class="label">Nome</span><input id="catalog-name" v-model="form.name" name="name" class="input input-bordered" :aria-invalid="!!form.errors.name" aria-describedby="catalog-name-error" /><span v-if="form.errors.name" id="catalog-name-error" class="text-sm text-error">{{ form.errors.name }}</span></label>
                <label class="form-control"><span class="label">Tipo</span><select id="catalog-type" v-model="form.type" name="type" class="select select-bordered" :aria-invalid="!!form.errors.type" aria-describedby="catalog-type-error"><option disabled value="">Seleziona tipo</option><option value="material">Materiale</option><option value="component">Componente</option></select><span v-if="form.errors.type" id="catalog-type-error" class="text-sm text-error">{{ form.errors.type }}</span></label>
                <div class="flex flex-wrap justify-end gap-2"><AppAction :as="Link" variant="cancel" :href="route('catalog-items.index')">Annulla</AppAction><AppAction variant="confirm" type="submit" :busy="busy" :disabled="!canSubmit">Salva</AppAction></div>
            </div>
        </RegistryForm>
    </RegistryPage>
</template>
