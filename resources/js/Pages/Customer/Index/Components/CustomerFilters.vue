<template>
    <form @submit.prevent="applyFilters">
        <div class="mb-4 mt-4 flex flex-wrap gap-4">
            <div class="flex flex-nowrap items-center gap-1">
<!--
                    <input 
                    id="localTable" 
                    v-model="filterForm.localTable"
                    type="checkbox" 
                    class="toggle"
                />
                <label for="localTable">Tabella Locale FULL</label>
            -->
                <input 
                    id="continuativo" 
                    v-model="filterForm.continuativo"
                    type="checkbox" 
                    class="toggle"
                />
                <label for="continuativo">Continuativi</label>

                <input 
                    id="occasionale" 
                    v-model="filterForm.occasionale"
                    type="checkbox" 
                    class="toggle ml-4"
                />
                <label for="occasionale">Occasionali</label>

                <input 
                    id="deleted" 
                    v-model="filterForm.deleted"
                    type="checkbox" 
                    class="toggle ml-4"
                />
                <label for="deleted">Cancellati</label>

                <label for="chiave" class="input input-bordered flex items-center gap-2 ml-2">
                    <input 
                        id="chiave" 
                        v-model="filterForm.chiave"
                        type="text" 
                        class="grow filter-search-input"
                        placeholder="Ricerca..." 
                    />
                    <font-awesome-icon :icon="['fas', 'user-tie']" class="text-2xl"/>
                </label>
                <button type="button" @click="resetChiave" class="btn btn-ghost btn-circle ml-1" title="Resetta">
                    <font-awesome-icon :icon="['fas', 'arrows-rotate']" class="text-2xl"/>
                </button>
                <button type="submit" class="btn btn-ghost btn-circle" title="Cerca">
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="h-5 w-5" />
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>
import {reactive} from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
    filters: Object,
})

const filterForm = reactive({
    localTable: props.filters.localTable ?? false,
    continuativo: props.filters.continuativo ?? true,
    occasionale: props.filters.occasionale ?? false,
    deleted: props.filters.deleted ?? false,
    chiave: props.filters.chiave ?? null,
})

const applyFilters = () => {
    router.get(
        route('customer.index'),
        filterForm,
        {preserveState: true, preserveScroll: true},
    )
}

const resetChiave = () => {
    filterForm.chiave = null
    applyFilters()
}
</script>
