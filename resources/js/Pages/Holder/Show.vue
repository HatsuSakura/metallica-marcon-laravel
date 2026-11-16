<template>
<RegistryPage title="Scheda contenitore">
<template #back><Link
            class="btn btn-outline"
            :href="route('holder.index')"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna ad elenco Contenitori
        </Link></template>

    <div class="mb-4 flex flex-row items-center gap-4">

        <div class="text-lg font-medium">
            Dettaglio contenitore
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div class="col-span-1 md:col-span-6">
            <div class="font-medium text-lg">{{ props.holder.name }}</div>
            <div class="text-base-content/75">{{ props.holder.description }}</div>
        </div>
        <div class="col-span-1 md:col-span-3">
            <div class="label">Volume</div>
            <div>{{ props.holder.volume ?? '-' }} (m3)</div>
        </div>
        <div
            class="col-span-1 md:col-span-3"
            v-if="props.holder.equivalent_holder_id && props.holder.equivalent_holder_id !== props.holder.id && props.holder.equivalent_holder"
        >
            <div class="label">Equivalenza</div>
            <div>{{ props.holder.equivalent_units ?? 1 }} x {{ props.holder.equivalent_holder.name }}</div>
        </div>
    </div>

</RegistryPage>
</template>

<script setup>
import RegistryPage from '@/Components/Registry/RegistryPage.vue'

import { Link } from '@inertiajs/vue3'

const props = defineProps({
    holder: Object,
})
</script>
