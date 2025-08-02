<template>
  <div>
    <v-select
      v-model="model"
      :options="props.options"
      label="code"
      :reduce="c => c.id"
      :filterable="true" 
      :searchable="true"
      @update:modelValue="onSelect"
      placeholder="Cod. CER"
      class="custom-style-chooser min-w-max"
      :class="
        isDangerous ? 'cer-selected-dangerous' : 'cer-selected-normal',
        props.size
      "
    >
      <template #option="{ code, description, is_dangerous }">
        <span :class="is_dangerous ? 'cer-list-dangerous' : 'cer-list-normal'">
          {{ code }}
        </span><br>
        <span class="text-xs"><cite>{{ description }}</cite></span>
      </template>
    </v-select>
  </div>
</template>

<script setup>
import { computed, toRefs, ref } from 'vue'
import vSelect from "vue-select";
const model = defineModel() // gestisce automaticamente props.modelValue e update:modelValue
const props = defineProps({
  modelValue: [String, Number],
  options: { type: Array, default: () => [] },
  size: { type: String, default: 'w-32' },
})
const emit = defineEmits(['update:modelValue'])

const isDangerous = computed(() => {
  const cer = props.options.find(c => c.id === props.modelValue)
  return Boolean(cer?.is_dangerous)
})


function onSelect(val) {
  emit('update:modelValue', val)
}
</script>

<style scoped>
.cer-list-dangerous {
  color:red;
  font-weight:bold;
}

.cer-selected-dangerous {
  background-color: #ffe6e6; /* Light red background for dangerous items */
}

.accordion-custom-header{
  background-color: #f8f8f8;
}

.accordion-custom-body{
  background-color: #fff;
}
</style>

<style>

.custom-style-chooser .vs__dropdown-toggle,
.custom-style-chooser .vs__dropdown-menu {
  padding: 8px 2px;
}

.custom-style-chooser .vs__search::placeholder {
  color: rgb(107 114 128);
  padding: 8px 2px;
}

.custom-style-chooser .vs__clear,
.custom-style-chooser .vs__open-indicator {
  fill: #394066;
}
</style>