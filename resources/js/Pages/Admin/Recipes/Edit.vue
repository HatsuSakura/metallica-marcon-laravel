<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import RegistryForm from '@/Components/Registry/RegistryForm.vue'

import { useForm, Link, router } from '@inertiajs/vue3'
import RecipeNodeEditor from './RecipeNodeEditor.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const props = defineProps({
  recipe: Object,
  catalog: { type: Array, default: () => [] },
  nodes:   { type: Array, default: () => [] }, // root + children
  backUrl:   { type: String, default: '' },
  backLabel: { type: String, default: '' }
})

const form = useForm({
  name: props.recipe?.name || '',
  version: props.recipe?.version || 1
})

function submit() {
  if (props.recipe?.id) form.put(route('recipes.update', props.recipe.id))
  else form.post(route('recipes.store'))
}

// fallback “indietro” quando non arriva backUrl dal controller
function goBack() {
  // se c'è history del browser, torna indietro
  if (window.history.length > 1) {
    window.history.back()
    return
  }
  // altrimenti vai a una route sicura (es. elenco ricette)
  router.visit(route('recipes.index'))
}
</script>

<template>
    <RegistryPage :title="props.recipe ? 'Modifica ricetta' : 'Nuova ricetta'">
<template #back><AppAction v-if="props.backUrl" :as="Link" :href="props.backUrl">Torna a {{ props.backLabel || 'elenco' }}</AppAction><AppAction v-else @click="goBack">Indietro</AppAction></template>
        <RegistryForm :form="form" :editing="!!props.recipe" v-slot="{canSubmit, busy}" @submit="submit" class="max-w-3xl">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <label class="form-control"><span class="label">Nome</span><input id="recipe-name" v-model="form.name" name="name" class="input input-bordered" :aria-invalid="!!form.errors.name" aria-describedby="recipe-name-error" /><span v-if="form.errors.name" id="recipe-name-error" class="text-sm text-error">{{ form.errors.name }}</span></label>
                <label class="form-control"><span class="label">Versione</span><input id="recipe-version" v-model.number="form.version" name="version" type="number" min="1" class="input input-bordered" :aria-invalid="!!form.errors.version" aria-describedby="recipe-version-error" /><span v-if="form.errors.version" id="recipe-version-error" class="text-sm text-error">{{ form.errors.version }}</span></label>
                <div class="flex flex-wrap justify-end gap-2 sm:col-span-2"><AppAction variant="cancel" :disabled="busy" @click="form.reset()">Annulla modifiche</AppAction><AppAction variant="confirm" type="submit" :busy="busy" :disabled="!canSubmit">Salva intestazione ricetta</AppAction></div>
            </div>
        </RegistryForm>
        <section v-if="props.recipe" class="space-y-3 border-t border-base-300 pt-4"><h2 class="text-lg font-semibold">Elementi della ricetta</h2><RecipeNodeEditor :key="props.recipe.id" :recipe-id="props.recipe.id" :catalog="props.catalog" :nodes="props.nodes" /></section>
    </RegistryPage>
</template>
