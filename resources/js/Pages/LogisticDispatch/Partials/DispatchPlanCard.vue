<template>
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <div class="flex items-center justify-between gap-2">
                <div class="font-semibold">
                    Piano scarico
                </div>
                <button
                    class="btn btn-sm btn-primary"
                    :disabled="saving"
                    @click="savePlan"
                >
                    Salva piano
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <label class="label cursor-pointer justify-start gap-3">
                    <input
                        v-model="form.is_double_load"
                        type="checkbox"
                        class="checkbox checkbox-sm"
                    >
                    <span class="label-text">Doppio scarico</span>
                </label>

                <label class="label cursor-pointer justify-start gap-3">
                    <input
                        v-model="form.is_temporary_storage"
                        type="checkbox"
                        class="checkbox checkbox-sm"
                    >
                    <span class="label-text">Stoccaggio temporaneo</span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="label"><span class="label-text">Magazzino primario</span></label>
                    <select
                        v-model="form.primary_warehouse_id"
                        class="select select-bordered w-full"
                    >
                        <option :value="null">
                            -
                        </option>
                        <option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :value="warehouse.id"
                        >
                            {{ warehouse.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="label"><span class="label-text">Magazzino secondario</span></label>
                    <select
                        v-model="form.secondary_warehouse_id"
                        class="select select-bordered w-full"
                        :disabled="!form.is_double_load"
                    >
                        <option :value="null">
                            -
                        </option>
                        <option
                            v-for="warehouse in warehouses"
                            :key="`secondary-${warehouse.id}`"
                            :value="warehouse.id"
                        >
                            {{ warehouse.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div
                v-if="errorMessage"
                class="alert alert-error py-2"
            >
                <span>{{ errorMessage }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    journey: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['updated']);

const form = reactive({
    is_double_load: Boolean(props.journey.is_double_load),
    is_temporary_storage: Boolean(props.journey.is_temporary_storage),
    primary_warehouse_id: props.journey.primary_warehouse_id ?? null,
    secondary_warehouse_id: props.journey.secondary_warehouse_id ?? null,
});

const saving = ref(false);
const errorMessage = ref('');

watch(
    () => form.is_double_load,
    (value) => {
        if (!value) {
            form.secondary_warehouse_id = null;
        }
    }
);

async function savePlan() {
    saving.value = true;
    errorMessage.value = '';

    try {
        const { data } = await axios.put(
            route('api.logistic-dispatch.update-plan', props.journey.id),
            {
                is_double_load: form.is_double_load,
                is_temporary_storage: form.is_temporary_storage,
                primary_warehouse_id: form.primary_warehouse_id,
                secondary_warehouse_id: form.secondary_warehouse_id,
            }
        );

        emit('updated', data.journey);
    } catch (error) {
        errorMessage.value = error?.response?.data?.message ?? 'Salvataggio non riuscito.';
    } finally {
        saving.value = false;
    }
}
</script>
