<template>

<section>
<Link @click="closeDrawer" :href="route('relator.trailer.create')" class="btn btn-primary mb-4">
    Nuovo Rimorchio
</Link>
</section>

<section>
    
    <div class="grid grid-cols-12 gap-2">

    <Box v-for="trailer in props.trailers.data" class="col-span-6">
        <div class="flex flex-row justify-between mb-2">
            <div>
                <div class="font-medium text-lg">
                    {{ trailer.name }} | {{ trailer.plate }}
                </div>
                <div class="text-gray-500">
                    {{ trailer.description }}
                </div>

                <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl"/>
                Capacità di carico:
                    <span class="font-medium">{{ trailer.load_capacity }} Kg</span> <span> [= {{ trailer.load_capacity / 1000 }} ton.]</span>
                <br/>

                <font-awesome-icon :icon="['fas', 'arrows-left-right']" class="text-2xl"/>
                Verso del caricamento:
                    <span v-if="trailer.is_front_cargo" class="font-medium">Lato Motrice </span>
                    <span v-else  class="font-medium">Lato posteriore</span>
                <br/>
            
            </div>
            <div>
                IMMAGINE
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2">
            <Link
            :href="route('relator.trailer.edit', {trailer: trailer.id} )"
            method="get"
            as="button"
            class="btn btn-primary btn-circle"
            >
                <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
            </Link>

            <Link
                :href="route('relator.trailer.destroy', {trailer: trailer.id} )"
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
import Box from '@/Components/UI/Box.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    trailers : Object,
})

</script>