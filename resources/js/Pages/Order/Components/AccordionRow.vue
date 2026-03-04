<template>


    <div class="accordion-section accordion-custom-item">
      <!-- Accordion Header -->
      <button
        @click="toggleOpen"
        class="accordion-header p-4 w-full"
        :aria-expanded="isOpen"
        type="button"
      >
        <div class="flex flex-row justify-between items-center w-full">
            <div class="flex font-medium">
                {{ title }}
            </div>
            <div class="flex">
                <font-awesome-icon
                :icon="isOpen ? ['fas', 'chevron-up'] : ['fas', 'chevron-down']"
                />
            </div>

        </div>
      </button>
  
      <!-- Accordion Content -->
      <div
        v-if="isOpen"
        class="accordion-content p-4"
      >
        <slot>
            Default
        </slot>

      </div>
    </div>
  </template>

<script setup>
import { ref, onMounted } from "vue";

// Props
const props = defineProps({
  id: {
    type: [String, Number],
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  initialOpen: {
    type: Boolean,
    default: false,
  },
});

// Reactive state
const isOpen = ref(props.initialOpen);

// Methods
const toggleOpen = () => {
  isOpen.value = !isOpen.value;
};

const closeSection = () => {
  isOpen.value = false;
};

const openSection = () => {
  isOpen.value = true;
};

// Emit self to parent
const emit = defineEmits(["register"]);
onMounted(() => {
  emit("register", { closeSection, openSection });
});
</script>
