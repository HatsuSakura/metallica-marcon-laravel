<template>
Viaggio #{{ String(props.journey.id).padStart('9', '0') + ' del ' + dayjs(props.journey.dt_start).format('DD-MM-YYYY') }}
<span class="font-bold"> | </span>
{{ props.journey.orders_count }} tappa/e 
<span class="font-bold"> | </span>
<span v-if="props.journey.state == 'creato'">PRONTI A PARTIRE</span>
<span v-else-if="props.journey.state == 'attivo'">IN VIAGGIO</span>
<br/>
Autista: <span class="font-medium"> {{ props.journey.driver.name }}</span>
<span class="font-bold"> | </span>
Mezzo: <span class="font-medium"> {{ props.journey.vehicle.name }}</span> - 
<span class="font-medium"> {{ props.journey.vehicle.plate }} </span>
<br/>
</template>

<script setup>
    import dayjs from 'dayjs';
    const props = defineProps({
        journey : Object,
    })
</script>