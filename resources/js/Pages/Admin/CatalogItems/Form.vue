<script setup>
import { useForm } from '@inertiajs/vue3'

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
  <div class="p-6 max-w-xl">
    <h1 class="text-xl font-bold mb-4">{{ props.item ? 'Modifica' : 'Nuovo' }} Catalog Item</h1>
    <form @submit.prevent="submit" class="flex flex-col gap-4">
      <input v-model="form.name" class="input input-bordered" placeholder="Nome" />
      <select v-model="form.type" class="select select-bordered">
        <option disabled value="">Seleziona tipo</option>
        <option value="material">Material</option>
        <option value="component">Component</option>
      </select>
      <button class="btn btn-primary" type="submit">Salva</button>
    </form>
  </div>
</template>
