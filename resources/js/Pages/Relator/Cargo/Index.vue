<template>

<section>
<Link @click="closeDrawer" :href="route('relator.cargo.create')" class="btn btn-primary mb-4">
    Nuovo Cassone
</Link>
</section>

<section>
    <div class="grid grid-cols-12 gap-2">

    <Box v-for="cargo in props.cargos.data" class="col-span-6">
        <div class="flex flex-row justify-between mb-2">
            <div>
                <div class="font-medium text-lg">
                    {{ cargo.name }} <span v-if="!cargo.is_cargo"><font-awesome-icon :icon="['fas', 'truck']" />&nbsp;</span>
                    <span>[{{ cargo.total_count }}]</span>
                </div>
                <div class="text-gray-500">
                    {{ cargo.description }}
                </div>
                <div>
                    Dimensione: {{ cargo.length }} (m) |
                    <span v-if="cargo.is_long">LUNGO</span>
                    <span v-else>CORTO</span>
                </div>
                <div>
                    Casse: {{ cargo.casse }}
                    | Spazi casse: {{ cargo.spazi_casse }}
                    | Spazi bancali: {{ cargo.spazi_bancale }} 
                </div>
            </div>
            <div>
                IMMAGINE
            </div>
        </div>

        <div class="flex flex-row justify-end gap-2">
            <Link
            :href="route('relator.cargo.edit', {cargo: cargo.id} )"
            method="get"
            as="button"
            class="btn btn-primary btn-circle"
            >
                <font-awesome-icon :icon="['fas', 'pen']" class="text-xl"/>
            </Link>

            <Link
                :href="route('relator.cargo.destroy', {cargo: cargo.id} )"
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
    cargos : Object,
})

</script>