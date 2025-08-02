<template>

<div class="flex flex-row justify-between">
    <div class="flex align-middle gap-2">Muletto:
    <font-awesome-icon v-if="props.site.has_muletto":icon="['fas', 'thumbs-up']" class="text-2xl text-success"/>
    <font-awesome-icon v-else :icon="['fas', 'thumbs-down']" class="text-2xl text-error"/>
    </div>
    <div class="flex align-middle gap-2">Transpallet Elettrico:
    <font-awesome-icon v-if="props.site.has_transpallet_el":icon="['fas', 'thumbs-up']" class="text-2xl text-success"/>
    <font-awesome-icon v-else :icon="['fas', 'thumbs-down']" class="text-2xl text-error"/>
    </div>
    <div class="flex align-middle gap-2">Transpallet Manuale:
    <font-awesome-icon v-if="props.site.has_transpallet_ma":icon="['fas', 'thumbs-up']" class="text-2xl text-success"/>
    <font-awesome-icon v-else :icon="['fas', 'thumbs-down']" class="text-2xl text-error"/>
    </div>
</div>
<div>Altro: 
    <span v-if="props.site.other_machines">{{ props.site.other_machines }}</span>
    <span v-else>Nessuna indicazione specifica</span>
</div>


</template>

<script setup>

const props = defineProps({
    site : Object,
})
</script>

