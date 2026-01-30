<template>
<section class="flex flex-row justify-between items-center gap-4 mb-4">
    <div class="flex flex-row items-center gap-2">
        <label class="font-medium">Filtro</label>
        <input
            type="text"
            class="input"
            placeholder="Cerca per nome"
            v-model.trim="search"
        />
        <button type="button" class="btn btn-ghost" @click="search = ''">Cancella Filtro</button>
    </div>
    <Link @click="closeDrawer" :href="route('holder.create')" class="btn btn-primary">
        <font-awesome-icon :icon="['fas', 'plus']" class="mr-2"/>
        Nuovo Contenitore
    </Link>
</section>

<section>
    <div class="grid grid-cols-12 gap-2">
        <Box v-for="holder in filteredHolders" :key="holder.id" class="col-span-3">
            <div class="flex flex-row justify-between mb-2">
                <div>
                    <div class="font-medium text-lg">
                        {{ holder.name }}
                    </div>
                    <div class="text-gray-500">
                        {{ holder.description }}
                    </div>
                    <div>
                        Volume: {{ holder.volume ?? '-' }} (m3)
                    </div>
                    <div
                        v-if="holder.equivalent_holder_id && holder.equivalent_holder_id !== holder.id && holder.equivalent_holder"
                    >
                        Equivalenza: {{ holder.equivalent_units ?? 1 }} x {{ holder.equivalent_holder.name }}
                    </div>
                </div>
                <div>
                    IMMAGINE
                </div>
            </div>

            <div class="flex flex-row justify-end gap-2">
                <Link
                    :href="route('holder.edit', { holder: holder.id })"
                    method="get"
                    as="button"
                    class="btn btn-primary btn-circle"
                >
                    <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
                </Link>

                <Link
                    :href="route('holder.destroy', { holder: holder.id })"
                    method="delete"
                    as="button"
                    class="btn btn-error btn-circle"
                >
                    <font-awesome-icon :icon="['fas', 'trash-can']" class="text-xl"/>
                </Link>
            </div>
        </Box>
    </div>
</section>
</template>

<script setup>
import { computed, ref } from 'vue'
import Box from '@/Components/UI/Box.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    holders: Object,
})

const search = ref('')

const filteredHolders = computed(() => {
    const list = props.holders?.data ?? []
    const term = search.value.toLowerCase()
    if (!term) return list
    return list.filter((holder) => {
        const name = (holder.name ?? '').toLowerCase()
        const description = (holder.description ?? '').toLowerCase()
        return name.includes(term) || description.includes(term)
    })
})
</script>
