<template>
    <form @submit.prevent>
        <div class="mx-4 mb-4 flex flex-wrap gap-0">
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
                        class="grow"
                        placeholder="Ricerca..." 
                    />
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="text-2xl"/>
                </label>
                <button @click="resetChiave" class="btn btn-ghost btn-circle ml-1">
                    <font-awesome-icon :icon="['fas', 'arrows-rotate']" class="text-2xl"/>
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
import {reactive, watch, computed} from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

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

// in this case we don't need to know the value, it's important to know that something has changed
watch(
    filterForm, debounce( () => router.get(
        route('map.site.index'),
        filterForm,
        {
            preserveState: true, 
            preserveScroll: false, 
            onSuccess: () => window.scrollTo(0, 0)  // Scrolls back to the top on navigation
        },
    ), 1000 ),  
)

const resetChiave = () => {
    filterForm.chiave = null
}
</script>