<script setup>
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
  <div class="p-6">
    <h1 class="text-xl font-bold mb-4">{{ props.recipe ? 'Modifica' : 'Nuova' }} Ricetta</h1>

    <form @submit.prevent="submit" class="flex flex-col gap-4 max-w-xl">

    <div class="flex flex-row gap-4 max-w-xl items-center">
      <Link v-if="props.backUrl" :href="props.backUrl" class="btn btn-ghost">
        <FontAwesomeIcon icon="fa-solid fa-arrow-left" class="mr-1 text-lg" />
        Torna a {{ props.backLabel || 'indietro' }}
      </Link>
      <button v-else class="btn btn-ghost" @click="goBack">← Indietro</button>

      <button class="btn btn-primary" type="submit">
        <FontAwesomeIcon icon="fa-solid fa-floppy-disk" class="mr-1 text-lg" />
        Salva intestazione ricetta
      </button>
    </div>

      <div class="flex flex-row gap-4 max-w-xl items-center">
        <label>Nome</label>
        <input v-model="form.name" class="input input-bordered w-128" placeholder="Nome ricetta" />
        <label>Versione</label>
        <input v-model="form.version" type="number" min="1" class="input input-bordered w-32" placeholder="Versione" />
      </div>
      
    </form>

    <div v-if="props.recipe" class="mt-8">
      <h2 class="text-lg font-semibold mb-2">Nodi della ricetta</h2>
      <RecipeNodeEditor
        :key="props.recipe.id"
        :recipe-id="props.recipe.id"
        :catalog="props.catalog"
        :nodes="props.nodes"
      />
    </div>
  </div>
</template>
