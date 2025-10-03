<script setup>
import { Link } from '@inertiajs/vue3'
const props = defineProps({ recipes: Object })
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">Ricette</h1>
      <Link class="btn btn-primary" :href="route('recipes.create')">+ Nuova</Link>
    </div>

    <table class="table w-full">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Versione</th>
          <th class="text-right">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in recipes.data" :key="r.id">
          <td>{{ r.name }}</td>
          <td>{{ r.version }}</td>
          <td class="text-right">
            <Link :href="route('recipes.edit', { recipe: r.id, from: 'recipes' })" class="btn btn-sm">
              Modifica
            </Link>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
