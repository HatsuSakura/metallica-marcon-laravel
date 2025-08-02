<template>
{{ String(props.id).padStart('9', '0') }}
</template>

<script setup>
const props = defineProps({
    id: Number
})
</script>