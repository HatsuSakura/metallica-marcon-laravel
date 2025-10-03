<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const props = defineProps({ items: Object })
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between mb-4">
      <h1 class="text-xl font-bold">Catalogo Materiali / Componenti</h1>
      <Link class="btn btn-primary" :href="route('catalog-items.create')">+ Nuovo</Link>
    </div>

    <table class="table w-full">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Tipo</th>
          <th>Ricetta</th>
          <th class="text-right">Azioni</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items.data" :key="item.id">
          <td>{{ item.name }}</td>
          <td>
            <span class="badge" :class="item.type === 'material' ? 'badge-success' : 'badge-info'">
              {{ item.type }}
            </span>
          </td>
          <td class="text-right">
              <Link v-if="item.type==='component'" class="btn btn-xs" :href="route('catalog-items.recipe.edit', { item: item.id, from: 'catalog' })">Gestisci ricetta</Link>
          </td>
          <td class="text-right">
            <Link class="btn btn-xs btn-outline" :href="route('catalog-items.edit', item.id)">Modifica</Link>
            <Link class="btn btn-xs btn-error ml-2" :href="route('catalog-items.destroy', item.id)" method="delete" as="button" preserve-scroll>Elimina</Link>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
