<template>
    <div class="flex flex-row items-stretch w-full gap-4 h-32">

      <Box>
        <template #header>Sede Principale</template>
        <font-awesome-icon 
          :icon="['fas', 'star']" 
          class="text-4xl" 
          :class="local_is_main ? 'text-success' : 'text-error'"
        />
        <input
          v-if="props.isEditable && !props.is_main"
          v-model="local_is_main" 
          id="local_is_main" 
          type="checkbox" 
          class="toggle ml-4" 
          @change="emitIsMainUpdate"
        />
      </Box>

      <Box>
        <template #header>Denominazione</template>
        <input v-if="props.isEditable"
            v-model="local_denominazione" 
            id="local_denominazione" 
            type="text" 
            class="input input-bordered" 
            @change="emitDenominazioneUpdate"
            :disabled="!props.isEditable"
          />
          <div v-else >
          {{ local_denominazione }}
        </div>
      </Box>

      <Box class="p-4">
        <template #header>Area Principale</template>
        <select v-if="props.isEditable"
          v-model="local_preferred_area" 
          id="local_preferred_area" 
          class="select select-bordered flex"
          :disabled="!props.isEditable"
          @change="emitPreferredAreaUpdate"
        >
            <option disabled value="">Imposta area principale</option>
            <option v-for="area in props.areas" :key="area.id" :value="area.id">
            {{ area.name }} <!-- Assuming user model has a 'name' field -->
            </option>
        </select>
        <div v-else>
          {{ 
            props.areas.find(area => area.id === local_preferred_area)
            ? props.areas.find(area => area.id === local_preferred_area).name
            : 'area non impostata'
            
          }}

        </div>
      </Box>

      <Box class="p-4">
        <template #header>Areae Secondarie</template>
        <!--
        <select v-if="props.isEditable"
          v-model="props.preferred_area" 
          id="preferred_area" 
          class="select select-bordered flex"
          :disabled="!props.isEditable"
        >
            <option disabled value="">Imposta area principale</option>
            <option v-for="area in props.areas" :key="area.id" :value="area.id">
            {{ area.name }}
            </option>
        </select>
        <div v-else>
          {{ 
            props.areas.find(area => area.id === preferred_area)
            ? props.areas.find(area => area.id === preferred_area).name
            : 'area non impostata'
            
          }}
        </div>
      -->
        <div>DA IMPLEMENTARE</div>
      </Box>


    </div>
  </template>
  
  <script setup>
  import { ref, watch } from 'vue';
  import Box from '@/Components/UI/Box.vue';
  
  const props = defineProps({
    areas: Array,
    preferred_area : Number,
    is_main: Boolean,
    denominazione: String,
    booleanValue: Boolean,
    iconString: String,
    isEditable: Boolean,
  });
  
  const emit = defineEmits(
    ['update:denominazione'],
    ['update:is_main'],
    ['update:preferred_area'],
  );

  // REF to the prop and watch the prop to update the local value. Then function to emit changes
  const local_is_main = ref(props.is_main);
  watch(() => props.is_main, (newValue) => {
    local_is_main.value = newValue;
  });
  const emitIsMainUpdate = () => {
        emit('update:is_main', local_is_main.value);
  };


  // REF to the prop and watch the prop to update the local value. Then function to emit changes
  const local_denominazione = ref(props.denominazione);
  watch(() => props.denominazione, (newValue) => {
    local_denominazione.value = newValue;
  });
  const emitDenominazioneUpdate = () => {
        emit('update:denominazione', local_denominazione.value);
  };


    // REF to the prop and watch the prop to update the local value. Then function to emit changes
  const local_preferred_area = ref(props.preferred_area);
  watch(() => props.preferred_area, (newValue) => {
    local_preferred_area.value = newValue;
  });
  const emitPreferredAreaUpdate = () => {
        emit('update:preferred_area', local_preferred_area.value);
  };




  </script>
  