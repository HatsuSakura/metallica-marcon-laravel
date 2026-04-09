<template>
    <form @submit.prevent="applyFilters">
        <div class="mb-4 mt-4 flex flex-wrap gap-4">
            <div class="flex flex-nowrap items-center gap-1">
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
                <button type="submit" class="btn btn-primary btn-circle" title="Cerca">
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="h-5 w-5" />
                </button>
            </div>
            <div class="flex flex-nowrap items-center gap-1">
                <span class="text-2xl mr-0">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" />
                </span>
                <span class="text-lg font-medium mr-4">
                    Rischio
                </span> 
                <input 
                    id="rischioBasso" 
                    v-model="filterForm.rischioBasso"
                    type="checkbox" 
                    class="toggle"
                />
                <label for="rischioBasso">Basso</label>

                <input 
                    id="rischioMedio" 
                    v-model="filterForm.rischioMedio"
                    type="checkbox" 
                    class="toggle ml-4"
                />
                <label for="rischioMedio">Medio</label>

                <input 
                    id="rischioAlto" 
                    v-model="filterForm.rischioAlto"
                    type="checkbox" 
                    class="toggle ml-4"
                />
                <label for="rischioAlto">Alto</label>

                <input 
                    id="rischioCritico" 
                    v-model="filterForm.rischioCritico"
                    type="checkbox" 
                    class="toggle ml-4"
                />
                <label for="rischioCritico">Critico</label>
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
    continuativo: props.filters.continuativo ?? true,
    occasionale: props.filters.occasionale ?? false,
    deleted: props.filters.deleted ?? false,
    chiave: props.filters.chiave ?? null,
    rischioBasso: props.filters.rischioBasso ?? false,
    rischioMedio: props.filters.rischioMedio ?? false,
    rischioAlto: props.filters.rischioAlto ?? true,
    rischioCritico: props.filters.rischioCritico ?? true,
})

const applyFilters = () => {
    router.get(
        route('map.site.index'),
        filterForm,
        {
            preserveState: true, 
            preserveScroll: false, 
            onSuccess: () => window.scrollTo(0, 0)  // Scrolls back to the top on navigation
        },
    )
}

const resetChiave = () => {
    filterForm.chiave = null
    applyFilters()
}
</script>
