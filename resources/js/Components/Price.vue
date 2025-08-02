<template>
    <span>{{ formattedPrice }}</span>
</template>

<script setup>
import {computed} from 'vue'
const props = defineProps({
    price : [Number, String]
})

const formattedPrice = computed(
    () => Number(props.price).toLocaleString('it-IT', {
     style: 'currency',
     currency: 'EUR',
     maximumFractionDigits: 2
    })
)

</script>
