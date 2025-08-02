<template>

<!-- TESTATA -->
    <div class="mb-4">
        <Link
            class="btn btn-ghost" 
            :href="route('warehouse-manager.orders.edit', {order: props.order.id})"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="text-xl"/>
            Torna all'ordine
        </Link>
    </div>

    <div class="font-medium mb-4">
            Aggiungi elemento all'ordine #{{ order.legacy_code }} del {{ dayjs(order.created_at).format('DD-MM-YYYY') }}
    </div>


    <form @submit.prevent="submit">
        <div class="grid grid-cols-1 md:grid-cols-8 lg:grid-cols-12 gap-4">

            <div class="form-control md:col-span-2 lg:col-span-5">
                <label class="label">
                    <span class="label-text">Cer Code</span>
                </label>
                <CerCodeSelector v-model="form.cer_code_id" :options="cerCodes" :size="w-full"/>
            </div>       

            <div class="form-control md:col-span-2 lg:col-span-1">
                <label class="label">
                    <span class="label-text">Quantità</span>
                </label>
                <input 
                    v-model.number="form.holder_quantity" 
                    type="number" 
                    class="input input-bordered w-16"
                    placeholder="Q.tà"
                    min="1"
                />
            </div> 

            <div class="form-control md:col-span-2 lg:col-span-2">
                <label class="label">
                    <span class="label-text">Contenitore</span>
                </label>
                <!-- Holder Select/Option -->
                <select v-model="form.holder_id" id="holder" class="select select-bordered">
                    <option disabled value="">Seleziona un contenitore</option>
                    <option v-for="holder in holders" :key="holder.id" :value="holder.id">
                    {{ holder.name }}
                    </option>
                </select>
            </div>

            <div class="form-control md:col-span-2 lg:col-span-2">
                <label class="label">
                    <span class="label-text">Magazzino</span>
                </label>
                <!-- Holder Select/Option -->
                <select v-model="form.warehouse_id" id="warehouse" class="select select-bordered">
                    <option disabled value="">Seleziona un magazzino</option>
                    <option v-for="warehouse in props.warehouses" :key="warehouse.id" :value="warehouse.id">
                    {{ warehouse.denominazione }}
                    </option>
                </select>
            </div>

            <div class="form-control md:col-span-2 lg:col-span-2">
                <label class="label">
                    <span class="label-text">Cassone Ricevuto</span>
                </label>
                <!-- Holder Select/Option -->
                <select v-model="form.journey_cargo_id" id="journey_cargo" class="select select-bordered">
                    <option disabled value="">Seleziona cassone scaricato</option>
                    <option v-for="journey_cargo in props.order.journey_cargos" :key="journey_cargo.id" :value="journey_cargo.id">
                        {{journey_cargo.cargo.name}} su {{ journey_cargo.carrier.is_vehicle? 'motrice' : 'rimorchio' }} {{ journey_cargo.carrier.carrier_data.plate }}
                    </option>
                </select>
            </div>

            <div class="form-control md:col-span-2 lg:col-span-12">
                <label class="label">
                    <span class="label-text">Descrizione</span>
                </label>
                <input 
                    v-model="form.description" 
                    type="text" 
                    class="input input-bordered flex-grow"
                    placeholder="Descrizione"
                />
            </div> 

            <button type="submit" class="btn btn-success my-4  md:col-span-2 lg:col-span-2">
                <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-2xl" />
                Salva Elemento
            </button>
            
        </div>       
    </form>

</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3';
import dayjs from 'dayjs';
import CerCodeSelector from '@/Components/UI/CerCodeSelector.vue';

const props = defineProps({ 
    order: Object, 
    cerCodes: Array, 
    holders: Array,
    warehouses: Object,
});

const page = usePage();
const user = computed(
    () => page.props.user
)

const form = useForm({
  cer_code_id: '',
  holder_id: '',
  holder_quantity: 1,
  description: '',
  warehouse_id:  user.warehouses && user.warehouses.length > 0 ? user.warehouses[0].id : '', // Imposta il primo magazzino dell'utente come predefinito
  journey_cargo_id: '', // Assicurati che questo campo sia presente nell'oggetto order
  // altri campi...
});

function submit() {
  form.post(route('warehouse.orders.items.store', {order: props.order.id}), {
    onSuccess: () => {
      Inertia.visit(route('warehouse-manager.orders.edit', {order: props.order.id}));
    }
  });
}

</script>
