<!-- components/OrderItemsList.vue -->
<template>
  <div class="grid grid-cols-1 gap-4">
    <Box
      v-for="item in items"
      :key="item.id"
      :item="item"
    >
    {{ item }}
    
    </Box>
  </div>
</template>

<script setup>
const props = defineProps({
  items: { type: Array, required: true }
})
</script>
