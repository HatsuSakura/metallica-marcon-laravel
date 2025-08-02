<template>
    <form>
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
                        class="grow"
                        placeholder="Ricerca..." 
                    />
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="text-2xl"/>
                </label>
                <button @click="resetChiave" class="btn btn-ghost btn-circle ml-1">
                    <font-awesome-icon :icon="['fas', 'arrows-rotate']" class="text-2xl"/>
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>
import {reactive, watch, computed} from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

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

// in this case we don't need to know the value, it's important to know that something has changed
watch(
    filterForm, debounce( () => router.get(
        route('relator.customer.index'),
        filterForm,
        {preserveState: true, preserveScroll: true},
    ), 1000 ),  
)

const resetChiave = () => {
    filterForm.chiave = null
}
</script>