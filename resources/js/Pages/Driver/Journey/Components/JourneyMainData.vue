<template>
<div class="flex flex-col">

    <div>
        Viaggio #{{ String(props.journey.id).padStart('9', '0') + ' del ' + dayjs(props.journey.planned_start_at).format('DD-MM-YYYY') }}
        <span class="font-bold"> | </span>
        {{ props.journey.stops_count ?? props.journey.orders_count ?? 0 }} tappa/e 
        <span class="font-bold"> | </span>
        <span v-if="props.journey.status == 'creato'">PRONTI A PARTIRE</span>
        <span v-else-if="props.journey.status == 'attivo'">IN VIAGGIO</span>
    </div>
    <div>
        Autista: <span class="font-medium"> {{ props.journey.driver?.name ?? '-' }}</span>
        <span class="font-bold"> | </span>
        Mezzo: <span class="font-medium"> {{ props.journey.vehicle?.name ?? '-' }}</span> - 
        <span class="font-medium"> {{ props.journey.vehicle?.plate ?? '-' }} </span>
    </div>

</div>



</template>

<script setup>
    import dayjs from 'dayjs';
    const props = defineProps({
        journey : Object,
    })
</script>

