<template>

<section>
<Link @click="closeDrawer" :href="route('relator.vehicle.create')" class="btn btn-primary mb-4">
    Nuovo Automezzo
</Link>
</section>

<section>

    <div class="grid grid-cols-12 gap-2">

    <Box v-for="vehicle in props.vehicles.data" class="col-span-6">
        <div class="flex flex-row justify-between mb-2">
            <div>
                <div class="font-medium text-lg">
                    {{ vehicle.name }} | {{ vehicle.plate }}
                </div>
                <div class="text-gray-500">
                    {{ vehicle.description }}
                </div>
            
                <font-awesome-icon :icon="['fas', 'user']" class="text-2xl"/>
                Autista abituale: 
                    <span v-if="vehicle.usual_driver">{{ vehicle.usual_driver.name }} {{ vehicle.usual_driver.surname }}</span>
                    <span v-else>Non assegnato</span>
                    <br/>
                
                <font-awesome-icon :icon="['fas', 'trailer']" class="text-2xl"/>
                Rimorchio abituale:
                    <span v-if="vehicle.preferred_trailer">{{ vehicle.preferred_trailer.plate }}</span>
                    <span v-else>Non assegnato</span>
                    <br/>
                
                <font-awesome-icon :icon="['fas', 'screwdriver-wrench']" class="text-2xl"/>
                Tipologia: <span class="font-bold"> {{ vehicle.type }} </span> 
                    <span v-if="!vehicle.has_trailer" class="text-error"> non può </span>
                    <span v-else class="text-success"> può </span>
                    trainare rimorchi <br/>
            </div>
            <div>
                IMMAGINE
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2">
            <Link
            :href="route('relator.vehicle.edit', {vehicle: vehicle.id} )"
            method="get"
            as="button"
            class="btn btn-primary btn-circle"
            >
                <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
            </Link>

            <Link
                :href="route('relator.vehicle.destroy', {vehicle: vehicle.id} )"
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
    vehicles : Object,
})

</script>