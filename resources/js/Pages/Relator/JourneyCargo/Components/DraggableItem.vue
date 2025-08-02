<template>
    <Box padding="p-2" class="bg-base-100">
    <div class="flex flex-row justify-between gap-2">
        <div class="flex flex-col gap-1 w-full">
            <div class="flex flex-row justify-between w-full">
                <div>
                    {{props.element.holder_quantity}} x {{ props.element.holder.name }} = TOT {{ props.element.weight_declared }} Kg
                </div>
                <div class="flex flex-col items-start gap-2">
                    Magazzino: {{ props.element.warehouse? props.element.warehouse.denominazione : 'non specificato' }}
                    <div v-if="Number(props.element.warehouse_id) != Number(props.warehouse_id)" class="flex flex-col items-start gap-1">
                        <div class="flex flex-row items-center gap-2">

                            <div class="flex flex-row items-center gap-2">
                                <input v-model="is_double_load" id="is_double_load" type="checkbox" class="toggle" @change="manageElementDoubleLoad()" />
                                <label class="font-medium" for="is_double_load">Doppio Scarico</label>
                            </div>
                            <div>
                                <!-- MAGAZZINO Select/Option -->
                                <select v-if="is_double_load" v-model="warehouse_download_id" id="warehouse_download_id" class="select select-bordered">
                                    <option v-for="warehouse in props.warehouses.filter(warehouse => warehouse.id != props.warehouse_id)" :key="warehouse.id" :value="warehouse.id">
                                        {{ warehouse.denominazione }}
                                    </option>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div>
                Ord. #<ZeroPaddingId :id=props.element.order.id /> di {{ props.element.order.customer.ragione_sociale }}
            </div>
            <div>
                <span :class="Boolean(props.element.cer_code.is_dangerous)? 'text-error font-medium' : '' ">CER {{props.element.cer_code.code}} </span> 
                <font-awesome-icon :icon="['fas', 'arrow-right-long']" class="mx-2"/>
                {{ props.element.description? props.element.description : 'Nessuna descrizione' }}  
            </div>
        </div>

    </div>
    </Box>
</template>

<script setup>
import { computed, watch, ref, onMounted, onUnmounted } from 'vue';
import Box from '@/Components/UI/Box.vue';
import ZeroPaddingId from '@/Components/UI/ZeroPaddingId.vue';

const props = defineProps({
    element: Object,
    index: Number,
    isDownloadable: Boolean,
    warehouse_id: Number,
    warehouses: Object,
});

const emit = defineEmits(['edit-item', 'view-item', 'view-map', 'double-load-change']);

const emitEditItem = () => {
    emit('edit-item', props.element);
};

const emitViewItem = () => {
    emit('view-item', props.element);
};

const emitViewMap = () => {
    emit('view-map', props.element);
};

const is_double_load = ref();
const warehouse_download_id = ref();
onMounted(() => {
    is_double_load.value = props.element.pivot?.is_double_load === 1 || false;
    warehouse_download_id.value = props.element.pivot?.warehouse_download_id || props.element.warehouse_id;
});

// Watch for changes in parent's warehouse_id and the element's warehouse_id.
// If they become equal, reset the double-load state.
watch(
  () => ({
    parentWarehouse: props.warehouse_id,
    elementWarehouse: props.element.warehouse_id
  }),
  ({ parentWarehouse, elementWarehouse }) => {
    if (Number(parentWarehouse) === Number(elementWarehouse)) {
      is_double_load.value = false;
      warehouse_download_id.value = null;
      emit('double-load-change', props.element.id, false, null);
    }
  }
);

const manageElementDoubleLoad = () => {
  if (!is_double_load.value) {
    // If double load is disabled, reset the select’s bound value to null.
    warehouse_download_id.value = null;
  }
  // Otherwise (when double load is enabled), leave warehouse_download_id.value unchanged so it reflects the select's current value.
  emit('double-load-change', props.element.id, is_double_load.value, warehouse_download_id.value);
};

</script>