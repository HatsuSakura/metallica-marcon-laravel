<template>
    <div class="flex-1">
      <Box>
        <template #header>{{ title }}</template>
        <font-awesome-icon 
          :icon="['fas', props.iconString]" 
          class="text-4xl" 
          :class="localValue ? 'text-success' : 'text-error'"
        />
        <input v-if="props.isEditable"
          v-model="localValue" 
          :id="props.title" 
          type="checkbox" 
          class="toggle ml-4" 
          @change="updateValue"
        />
      </Box>
    </div>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue';
  import Box from '@/Components/UI/Box.vue';
  
  const props = defineProps({
    title: String,
    booleanValue: Boolean,
    iconString: String,
    isEditable: Boolean,
  });
  
  const emit = defineEmits(['update:booleanValue']);
  
  // Local value for the toggle
  const localValue = ref(props.booleanValue);
  
  // Watch the prop to update the local value when the prop changes
  watch(() => props.booleanValue, (newValue) => {
    if (newValue !== localValue.value) {
      localValue.value = newValue;
    }
  });
  
  // Emit the updated value to the parent
  const updateValue = () => {
    emit('update:booleanValue', localValue.value);
  };
  </script>
  