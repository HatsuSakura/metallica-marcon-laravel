<template>
  <section class="max-w-4xl mx-auto">
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h1 class="card-title">Modifica Ritiro</h1>

        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Sede di carico</span>
            </label>
            <input type="text" class="input input-bordered w-full" :value="siteLabel" readonly />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Cliente</span>
            </label>
            <input type="text" class="input input-bordered w-full" :value="customerLabel" readonly />
          </div>
        </div>

        <div class="grid md:grid-cols-1 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Data ritiro</span>
            </label>
            <VueDatePicker
              v-model="form.withdrawn_at"
              model-type="yyyy-MM-dd"
              locale="it"
              format="dd/MM/yyyy"
              placeholder="Seleziona data"
              :enable-time-picker="false"
              :auto-apply="true"
              :teleport="true"
              :auto-position="true"
              position="left"
              :offset="8"
            />
            <div class="input-error" v-if="form.errors.withdrawn_at">{{ form.errors.withdrawn_at }}</div>
          </div>
        </div>

        <div class="form-control">
          <label class="label" for="residue_percentage">
            <span class="label-text">Percentuale residua ({{ form.residue_percentage }}%)</span>
          </label>
          <input
            id="residue_percentage"
            v-model.number="form.residue_percentage"
            type="range"
            min="0"
            max="100"
            step="5"
            class="range range-primary"
          />
          <div class="w-full flex justify-between text-xs px-1 mt-1">
            <span>0</span>
            <span>100</span>
          </div>
          <div class="input-error" v-if="form.errors.residue_percentage">{{ form.errors.residue_percentage }}</div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label" for="driver_id">
              <span class="label-text">Autista (opzionale)</span>
            </label>
            <select id="driver_id" v-model.number="form.driver_id" class="select select-bordered w-full">
              <option :value="null">Nessuno</option>
              <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                {{ driver.name }} {{ driver.surname ?? '' }}
              </option>
            </select>
            <div class="input-error" v-if="form.errors.driver_id">{{ form.errors.driver_id }}</div>
          </div>

          <div class="form-control">
            <label class="label" for="vehicle_id">
              <span class="label-text">Veicolo (opzionale)</span>
            </label>
            <select id="vehicle_id" v-model.number="form.vehicle_id" class="select select-bordered w-full">
              <option :value="null">Nessuno</option>
              <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                {{ vehicle.plate }} - {{ vehicle.name }}
              </option>
            </select>
            <div class="input-error" v-if="form.errors.vehicle_id">{{ form.errors.vehicle_id }}</div>
          </div>
        </div>

        <div class="card-actions justify-end">
          <button type="button" class="btn btn-ghost" @click="goBack">Annulla</button>
          <button type="button" class="btn btn-primary" :disabled="form.processing" @click="updateWithdraw">
            Salva modifiche
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import dayjs from 'dayjs';

const props = defineProps({
  withdraw: { type: Object, required: true },
  vehicles: { type: Array, default: () => [] },
  drivers: { type: Array, default: () => [] },
});

const form = useForm({
  withdrawn_at: props.withdraw.withdrawn_at ? dayjs(props.withdraw.withdrawn_at).format('YYYY-MM-DD') : null,
  residue_percentage: Number(props.withdraw.residue_percentage ?? 0),
  customer_id: props.withdraw.customer_id,
  site_id: props.withdraw.site_id,
  vehicle_id: props.withdraw.vehicle_id ?? null,
  driver_id: props.withdraw.driver_id ?? null,
  is_manual_entry: Boolean(props.withdraw.is_manual_entry ?? true),
});

const siteLabel = computed(() => {
  const site = props.withdraw.site;
  if (!site) return '-';
  return `${site.name} - ${site.address ?? '-'}`;
});

const customerLabel = computed(() => {
  const customer = props.withdraw.site?.customer;
  return customer?.company_name ?? '-';
});

const updateWithdraw = () => {
  form.transform((data) => ({
    ...data,
    withdrawn_at: data.withdrawn_at ? `${data.withdrawn_at} 12:00:00` : null,
  })).put(route('withdraw.update', { withdraw: props.withdraw.id }));
};

const goBack = () => {
  window.history.back();
};
</script>
