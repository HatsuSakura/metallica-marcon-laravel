<!-- src/Components/LoadingOverlay.vue -->
<template>
  <div
    v-if="isLoading"
    class="fixed inset-0 bg-black bg-opacity-50 z-[9999] flex items-center justify-center"
  >
    <!-- simple spinner: -->
    <div class="w-16 h-16 border-4 border-white border-t-transparent rounded-full animate-spin"></div>
  </div>
</template>

<script setup>
import { isLoading } from '@/Composables/useLoadingOverlay'
</script>
