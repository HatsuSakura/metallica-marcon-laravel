<template>
  <div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body p-4 space-y-4">

      <div class="steps steps-vertical lg:steps-horizontal w-full">
        <button class="step text-left" :class="activeStep >= 1 ? 'step-primary' : ''" @click="activeStep = 1">1. Censimento e suddivisione carico</button>
        <button class="step text-left" :class="activeStep >= 2 ? 'step-primary' : ''" :disabled="!canAccessStep2" :title="!canAccessStep2 ? 'Salva prima il censimento (Step 1).' : ''" @click="goToStep2">2. Operatività e selezione magazzini</button>
      </div>

      <div v-if="isReadOnly" class="alert alert-info">
        <span>Journey gestito: schermata in sola visualizzazione.</span>
      </div>

      <div v-if="loading" class="text-sm opacity-70">Caricamento workspace...</div>

      <div v-if="!loading && activeStep === 1" class="space-y-3">
        <div v-if="!hasCargos" class="alert alert-info">
          <span>Puoi compilare il censimento ora. Configura il convoglio nello Step 2 per posizione reale e split.</span>
        </div>

        <div class="overflow-x-auto">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Item</th>
                <th>Censimento</th>
                <th>Teorico</th>
                <th>Posizione reale</th>
                <th>Previsto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.order_item_id">
                <td> <!-- Item -->
                  <div class="font-medium">{{ row.description || `Item #${row.order_item_id}` }}</div>
                  <div class="text-xs opacity-70">{{ row.customer_name || '-' }}</div>
                  <div class="text-xs opacity-70">{{ row.legacy_code || '-' }}</div>
                  <div class="text-xs opacity-70">Previsto: {{ row.planned_containers }} {{ plannedContainerTypeLabel(row) }}</div>
                </td>
                <td class="min-w-[240px]"> <!-- Censimento -->
                  <label class="label py-0"><span class="label-text text-xs">Contenitori reali</span></label>
                  <input
                    v-model.number="row.actual_containers"
                    type="number"
                    min="0"
                    class="input input-bordered input-sm w-full"
                    :disabled="isReadOnly"
                    @input="onActualContainersChanged(row)"
                  >
                  <label class="label py-0 mt-1"><span class="label-text text-xs">Peso totale (kg)</span></label>
                  <input v-model.number="row.total_weight_kg" type="number" min="0" step="0.01" class="input input-bordered input-sm w-full" :disabled="isReadOnly">
                </td>
                <td class="min-w-[80px]"> <!-- Pianificato -->
                  <div class="text-xs space-y-1">
                    <div v-if="vehicleCargo" class="flex items-center justify-even gap-2">
                      <span>
                        <font-awesome-icon :icon="['fas', 'truck']" class="text-lg" />
                      </span>
                      <span class="font-semibold text-lg">{{ plannedContainers(row, 'vehicle') }}</span>
                    </div>
                    <div v-if="trailerCargo" class="flex items-center justify-even gap-2">
                      <span>
                        <font-awesome-icon :icon="['fas', 'trailer']" class="text-lg" />
                      </span>
                      <span class="font-semibold text-lg">{{ plannedContainers(row, 'trailer') }}</span>
                    </div>
                    <div v-if="!vehicleCargo && !trailerCargo" class="opacity-60">N/D</div>
                  </div>
                </td>
                <td class="min-w-[320px] space-y-2">
                  <div class="flex items-center justify-between">
                    <label v-if="canShowSplitToggle(row)" class="label flex items-center cursor-pointer gap-2 py-0">
                      <span class="label-text text-xs">Suddividi</span>
                      <input
                        :checked="row.split_enabled"
                        type="checkbox"
                        class="toggle"
                        :disabled="!canSplitAcrossCargos || isReadOnly"
                        @change="toggleSplit(row, $event.target.checked)"
                      >
                    </label>
                    <span v-else class="text-xs opacity-70">"Suddividi" disponibile se >1 contenitore</span>
                    <div v-if="!row.split_enabled && vehicleCargo && trailerCargo" class="flex items-center gap-2">
                      <span class="text-xs">Zona di carico: <strong>{{ row.real_location === 'trailer' ? 'Rimorchio' : 'Motrice' }}</strong></span>
                      <button type="button" class="btn btn-sm btn-outline" :disabled="isReadOnly" @click="toggleDestination(row)" title="Inverti motrice/rimorchio">
                        <font-awesome-icon :icon="['fas', 'arrow-right-arrow-left']" />
                      </button>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div v-if="vehicleCargo">
                      <label class="label py-0"><span class="label-text text-xs">Motrice</span></label>
                      <input
                        :value="allocationForLocation(row, 'vehicle')"
                        type="number"
                        min="0"
                        class="input input-bordered input-sm w-full"
                        :readonly="!row.split_enabled || isReadOnly"
                        :class="(!row.split_enabled || isReadOnly) ? 'input-disabled' : ''"
                        @input="onSplitInput(row, 'vehicle', $event.target.value)"
                      >
                    </div>
                    <div v-if="trailerCargo">
                      <label class="label py-0"><span class="label-text text-xs">Rimorchio</span></label>
                      <input
                        :value="allocationForLocation(row, 'trailer')"
                        type="number"
                        min="0"
                        class="input input-bordered input-sm w-full"
                        :readonly="!row.split_enabled || isReadOnly"
                        :class="(!row.split_enabled || isReadOnly) ? 'input-disabled' : ''"
                        @input="onSplitInput(row, 'trailer', $event.target.value)"
                      >
                    </div>
                  </div>
                </td>
                <td class="min-w-[80px] text-center"> <!-- Magazzino Destinazione -->
                  <font-awesome-icon :icon="['fas', 'warehouse']" class="text-lg" /><br/>
                  <span class="text-sm">{{ row.planned_warehouse_id ? warehouseName(row.planned_warehouse_id) : '-' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-wrap gap-2">
          <button class="btn btn-sm btn-outline" :disabled="loading || !canAccessStep2" :title="!canAccessStep2 ? 'Salva prima il censimento (Step 1).' : ''" @click="goToStep2">
            <font-awesome-icon :icon="['fas', 'arrow-right']" class="mr-1 text-lg" />
            Vai a operativita
          </button>
          <button class="btn btn-sm btn-primary" :disabled="saving || loading || !hasCargos || isReadOnly" @click="saveStep1Data">
            <font-awesome-icon :icon="['fas', 'floppy-disk']" class="mr-1 text-lg" />
            Salva censimento
          </button>
        </div>

      </div>

      <div v-if="!loading && activeStep === 2" class="space-y-3">
        <div v-if="!hasCargos" class="alert alert-warning"><span>Configura prima il convoglio nello Step 2.</span></div>

        <div v-else class="overflow-x-auto">
          <table class="table table-sm">
            <thead>
              <tr>
                <th></th>
                <th v-for="cargo in cargos" :key="`inst-head-${cargo.id}`" class="min-w-[280px] align-top">
                  <div class="space-y-2">
                    <div class="font-semibold flex items-center justify-center text-lg gap-2">
                      <font-awesome-icon :icon="cargo.cargo_location === 'vehicle' ? ['fas', 'truck'] : ['fas', 'trailer']" class="text-lg"/>
                      {{ cargoLabel(cargo) }}
                    </div>

                    <div v-if="cargo.cargo_location === 'vehicle'" class="space-y-1">
                      <label class="label py-0"><span class="label-text text-xs">Magazzino</span></label>
                      <select v-model="setup.cargos.vehicle.warehouse_id" class="select select-bordered select-sm w-full" :disabled="setup.cargos.vehicle.is_grounded || isReadOnly">
                        <option :value="null">-</option>
                        <option v-for="warehouse in warehouses" :key="`veh-wh-${warehouse.id}`" :value="warehouse.id">{{ warehouse.name }}</option>
                      </select>
                      <div class="grid grid-cols-2 gap-2">
                        <select v-model="setup.cargos.vehicle.download_sequence" class="select select-bordered select-sm w-full" :disabled="isReadOnly">
                          <option :value="null">Seq -</option>
                          <option :value="1">1</option>
                          <option :value="2">2</option>
                        </select>
                        <label class="flex items-center label cursor-pointer justify-start gap-2 py-0">
                          <span class="label-text text-xs">Solo appoggio</span>
                          <input v-model="setup.cargos.vehicle.is_grounded" type="checkbox" class="toggle" :disabled="isReadOnly" />
                        </label>
                      </div>
                    </div>

                    <div v-else class="space-y-1">
                      <label class="label py-0"><span class="label-text text-xs">Magazzino</span></label>
                      <select v-model="setup.cargos.trailer.warehouse_id" class="select select-bordered select-sm w-full" :disabled="!hasJourneyTrailer || setup.cargos.trailer.is_grounded || isReadOnly">
                        <option :value="null">-</option>
                        <option v-for="warehouse in warehouses" :key="`tr-wh-${warehouse.id}`" :value="warehouse.id">{{ warehouse.name }}</option>
                      </select>
                      <div class="grid grid-cols-2 gap-2">
                        <select v-model="setup.cargos.trailer.download_sequence" class="select select-bordered select-sm w-full" :disabled="!hasJourneyTrailer || isReadOnly">
                          <option :value="null">Seq -</option>
                          <option :value="1">1</option>
                          <option :value="2">2</option>
                        </select>
                        <label class="flex items-center label cursor-pointer justify-start gap-2 py-0">
                          <span class="label-text text-xs">Solo appoggio</span>
                          <input v-model="setup.cargos.trailer.is_grounded" type="checkbox" class="toggle" :disabled="!hasJourneyTrailer || isReadOnly" />
                        </label>
                      </div>
                    </div>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="`inst-${row.order_item_id}`">
                <td>
                  <div class="font-medium">{{ row.description || `Item #${row.order_item_id}` }}</div>
                  <div class="text-xs opacity-70">{{ row.customer_name || '-' }}</div>
                  <div class="text-xs opacity-70">{{ row.legacy_code || '-' }}</div>
                  <div class="text-xs opacity-70">Previsto: {{ row.planned_containers }} {{ plannedContainerTypeLabel(row) }}</div>
                </td>
                <td v-for="cargo in cargos" :key="`inst-cell-${row.order_item_id}-${cargo.id}`" class="min-w-[240px]">
                  <div v-if="hasAllocation(row, cargo.id)">
                    <div class="grid grid-cols-2 gap-2 items-end">
                      <div>
                        <label class="label py-0"><span class="label-text text-xs">Magazzino scarico</span></label>
                        <div class="input input-bordered input-sm w-full flex items-center">
                          <span class="text-xs">
                            {{ row.planned_warehouse_id ? warehouseName(row.planned_warehouse_id) : '-' }}
                          </span>
                        </div>
                      </div>
                      <div class="input input-bordered input-sm w-full flex items-center">
                        <span class="text-xs">
                          {{
                            isRowCargoInPark(row, cargo.id)
                              ? 'Cassone in appoggio'
                              : (!hasConfiguredCargoWarehouse(row, cargo.id)
                                  ? 'Verifica destinazione in attesa'
                                  : (hasMismatch(row, cargo.id) ? 'Azione richiesta' : 'Scarico materiale'))
                          }}
                        </span>
                      </div>
                    </div>

                    <div v-if="hasMismatch(row, cargo.id) && !isRowCargoInPark(row, cargo.id)" class="mt-2 rounded border border-warning/40 bg-warning/10 p-2">
                      <div class="text-xs font-medium mb-1">
                        Destinazione differente! Previsto: {{ warehouseName(row.planned_warehouse_id) }} / effettivo {{cargoLabel(cargo)}}: {{ warehouseName(targetWarehouseByRowCargo(row, cargo.id)) }}
                      </div>
                      <select
                        v-model="row.mismatch_decisions[cargo.id].decision"
                        class="select select-bordered select-sm w-full"
                        :disabled="isReadOnly"
                        @change="onMismatchDecisionChanged(row, cargo.id)"
                      >
                        <option :value="null">Seleziona gestione</option>
                        <option value="double_unload">Doppio scarico</option>
                        <option value="grounding">Trasbordo</option>
                      </select>

                      <select
                        v-if="row.mismatch_decisions[cargo.id].decision === 'double_unload'"
                        v-model="row.mismatch_decisions[cargo.id].secondary_warehouse_id"
                        class="select select-bordered select-sm w-full mt-1"
                        :disabled="isReadOnly"
                      >
                        <option :value="null">Seleziona altro magazzino</option>
                        <option
                          v-for="warehouse in warehouses"
                          :key="`mm-wh-${row.order_item_id}-${cargo.id}-${warehouse.id}`"
                          :value="warehouse.id"
                          :disabled="Number(warehouse.id) === Number(targetWarehouseByRowCargo(row, cargo.id))"
                        >
                          {{ warehouse.name }}
                        </option>
                      </select>
                    </div>
                    <div v-else-if="isRowCargoInPark(row, cargo.id)" class="mt-2 rounded border border-info/30 bg-info/10 p-2 text-xs">
                      Cassone in appoggio: nessuna azione di scarico disponibile.
                    </div>
                    <div v-else-if="!hasConfiguredCargoWarehouse(row, cargo.id)" class="mt-2 rounded border border-base-300 bg-base-200/60 p-2 text-xs">
                      Verifica destinazione disponibile dopo aver selezionato il magazzino per {{ cargoLabel(cargo) }}.
                    </div>
                    <div v-else class="mt-2 rounded border border-success/30 bg-success/10 p-2 text-xs">
                      Destinazione elemento coincidente con destinazione {{cargoLabel(cargo)}}.
                    </div>
                  </div>
                  <div v-else class="text-xs opacity-60">Nessuna allocazione su questo cargo.</div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-wrap gap-2">
          <button class="btn btn-sm btn-outline" :disabled="loading" @click="activeStep = 1">
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="mr-1 text-lg" />
            Torna a censimento
          </button>
          <button class="btn btn-sm btn-primary" :disabled="saving || loading || !hasCargos || isReadOnly" @click="saveStep2Data">
            <font-awesome-icon :icon="['fas', 'floppy-disk']" class="mr-1 text-lg" />
            Salva selezione
          </button>
          <button class="btn btn-sm btn-secondary" :disabled="saving || loading || !hasCargos || isReadOnly" @click="confirmBaseline">
            <font-awesome-icon :icon="['fas', 'warehouse']" class="mr-1 text-lg" />
            Salva & Procedi
          </button>
        </div>

        <div class="divider my-1" />

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="space-y-2">
            <div class="font-medium text-sm">Azioni operative</div>
            <textarea
              v-model="actionNotes"
              class="textarea textarea-bordered textarea-sm w-full"
              rows="3"
              :disabled="isReadOnly"
              placeholder="Annotazioni operative"
            ></textarea>
            <div class="flex flex-wrap gap-2">
              <button
                class="btn btn-sm btn-warning"
                :disabled="saving || loading || isReadOnly || isOnHold || isManaged"
                :title="isOnHold ? 'Il dispatch è già in attesa' : (isManaged ? 'Journey già chiuso' : '')"
                @click="sendAction('hold')"
              >
                <font-awesome-icon :icon="['fas', 'pause']" class="mr-1 text-lg" />
                Metti in attesa
              </button>
              <button
                class="btn btn-sm btn-info"
                :disabled="saving || loading || isReadOnly || !isOnHold"
                :title="!isOnHold ? 'Disponibile solo quando il dispatch è in attesa' : ''"
                @click="sendAction('resume')"
              >
                <font-awesome-icon :icon="['fas', 'play']" class="mr-1 text-lg" />
                Riprendi
              </button>
              <button
                class="btn btn-sm btn-success"
                :disabled="saving || loading || isReadOnly || isOnHold || isManaged"
                :title="isOnHold ? 'Non puoi chiudere il viaggio mentre il dispatch è in attesa' : (isManaged ? 'Journey già chiuso' : '')"
                @click="sendAction('close')"
              >
                <font-awesome-icon :icon="['fas', 'clipboard-check']" class="mr-1 text-lg" />
                Chiudi viaggio
              </button>
            </div>
          </div>

          <div class="space-y-2">
            <div class="font-medium text-sm">Trasbordi proposti</div>
            <div v-if="transshipments.length === 0" class="text-sm opacity-70">Nessun trasbordo proposto.</div>
            <div v-for="need in transshipments" :key="need.id" class="flex flex-row justify-between border border-base-200 rounded p-2 text-sm">
              
              <div class="flex flex-col items-start">
                <div class="font-medium">{{ need.order_item?.description ?? `Item #${need.order_item_id}` }}</div>
                <div class="text-xs opacity-80">{{ need.order_item?.order?.customer?.company_name ?? '-' }}</div>
                <div class="text-xs opacity-80">{{ need.order_item?.order?.legacy_code ?? '-' }}</div>
              </div>
              
              <div class="flex flex-col items-start">
                <div class="font-medium">{{ transshipmentStatusLabel(need.status) }}</div>
                <div class="opacity-80">{{ need.quantity_containers }} {{ transshipmentHolderLabel(need) }} | da {{ warehouseName(need.from_warehouse_id) }} a {{ warehouseName(need.to_warehouse_id) }}</div>
                <button v-if="isTransshipmentProposed(need.status)" class="btn btn-sm btn-success mt-1" :disabled="saving || loading || isReadOnly" @click="approveTransshipmentData(need.id)">
                  Approva subito
                </button>
              </div>
              
              
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { useStore } from 'vuex';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { DISPATCH_STATUS, normalizeDispatchStatus } from '@/Constants/dispatchStatus';
import {
  isTransshipmentCancelled,
  isTransshipmentProposed as isTransshipmentProposedStatus,
  normalizeTransshipmentStatus,
  transshipmentStatusLabel as getTransshipmentStatusLabel,
} from '@/Constants/transshipmentStatus';
import {
  approveTransshipment,
  closeDispatchAudit,
  confirmWorkspace,
  extractApiMessage,
  fetchWorkspace,
  holdDispatch,
  resumeDispatch,
  saveJourneyCargos,
  saveWorkspace,
  updateDispatchPlan,
} from '@/Pages/LogisticDispatch/api/dispatchApi';

const props = defineProps({
  journey: { type: Object, required: true },
  warehouses: { type: Array, default: () => [] },
});

const emit = defineEmits(['journey-updated']);
const store = useStore();
const loading = ref(false);
const saving = ref(false);
const activeStep = ref(1);
const rows = ref([]);
const cargos = ref([]);
const transshipments = ref([]);
const actionNotes = ref('');
const step1IsSaved = ref(false);
const savedStep1Signature = ref(null);
const hasCargos = computed(() => cargos.value.length > 0);
const hasJourneyTrailer = computed(() => Boolean(props?.journey?.trailer_id));
const vehicleCargo = computed(() => cargos.value.find((cargo) => cargo.cargo_location === 'vehicle') ?? null);
const trailerCargo = computed(() => cargos.value.find((cargo) => cargo.cargo_location === 'trailer') ?? null);
const canSplitAcrossCargos = computed(() => Boolean(vehicleCargo.value && trailerCargo.value));
const isOnHold = computed(() => normalizeDispatchStatus(props?.journey?.dispatch_status) === DISPATCH_STATUS.ON_HOLD);
const isManaged = computed(() => normalizeDispatchStatus(props?.journey?.dispatch_status) === DISPATCH_STATUS.MANAGED);
const isReadOnly = computed(() => normalizeDispatchStatus(props?.journey?.dispatch_status) === DISPATCH_STATUS.MANAGED);
const currentStep1Signature = computed(() => JSON.stringify(
  rows.value.map((row) => ({
    order_item_id: Number(row.order_item_id),
    actual_containers: Number(row.actual_containers ?? 0),
    total_weight_kg: row.total_weight_kg === '' || row.total_weight_kg === null ? null : Number(row.total_weight_kg),
    allocations: Object.keys(row.allocations ?? {})
      .sort((a, b) => Number(a) - Number(b))
      .map((cargoId) => [Number(cargoId), Number(row.allocations[cargoId] ?? 0)]),
  }))
));
const step1HasUnsavedChanges = computed(() => {
  if (!step1IsSaved.value) return true;
  if (!savedStep1Signature.value) return true;
  return savedStep1Signature.value !== currentStep1Signature.value;
});
const canAccessStep2 = computed(() => isReadOnly.value || (step1IsSaved.value && !step1HasUnsavedChanges.value));

const setup = reactive({
  cargos: {
    vehicle: { warehouse_id: null, download_sequence: null, is_grounded: false },
    trailer: { warehouse_id: null, download_sequence: null, is_grounded: false },
  },
});
const syncingSequencePair = ref(false);
const parkedWarehouseMemory = reactive({
  vehicle: null,
  trailer: null,
});

const orderItems = computed(() => {
  const result = [];
  for (const order of props.journey.orders ?? []) {
    for (const item of order.items ?? []) {
      result.push({
        id: item.id,
        description: item.description,
        holder_quantity: item.holder_quantity ?? 0,
        weight_declared: item.weight_declared ?? null,
        holder_name: item.holder?.name ?? null,
        customer_name: order.customer?.company_name ?? null,
        legacy_code: order.legacy_code ?? null,
        planned_warehouse_id: item.warehouse_id ?? null,
      });
    }
  }
  return result;
});

function normalizeTransshipments(raw) {
  if (Array.isArray(raw)) return raw;
  if (raw && typeof raw === 'object') return Object.values(raw);
  return [];
}

function transshipmentStatusLabel(status) {
  return getTransshipmentStatusLabel(status);
}

function isTransshipmentProposed(status) {
  return isTransshipmentProposedStatus(status);
}

function transshipmentHolderLabel(need) {
  return need?.order_item?.holder?.name || 'unità';
}

function resolveStep1SavedState(workspace, builtRows) {
  const censusItems = workspace?.census?.items ?? [];
  const censusByItem = new Map(
    censusItems.map((row) => [Number(row.order_item_id), Number(row.actual_containers ?? 0)])
  );

  const allocationByItem = new Map();
  for (const allocation of ((workspace?.allocations ?? []).filter((row) => String(row?.source ?? 'actual') === 'actual'))) {
    const orderItemId = Number(allocation.order_item_id);
    const current = Number(allocationByItem.get(orderItemId) ?? 0);
    allocationByItem.set(orderItemId, current + Number(allocation.allocated_containers ?? 0));
  }

  if (!builtRows.length) return false;
  return builtRows.every((row) => {
    const orderItemId = Number(row.order_item_id);
    if (!censusByItem.has(orderItemId)) return false;
    const censusContainers = Number(censusByItem.get(orderItemId) ?? 0);
    const allocatedContainers = Number(allocationByItem.get(orderItemId) ?? 0);
    return censusContainers === allocatedContainers;
  });
}

function plannedContainerTypeLabel(row) {
  return row?.holder_name || 'contenitori';
}

function cargoLabel(cargo) {
  if (cargo.cargo_location === 'vehicle') return 'Motrice';
  if (cargo.cargo_location === 'trailer') return 'Rimorchio';
  return `Cargo #${cargo.id}`;
}

function warehouseName(id) {
  const warehouse = (props.warehouses ?? []).find((w) => Number(w.id) === Number(id));
  return warehouse?.name ?? `#${id}`;
}

function hasAllocation(row, cargoId) {
  return Number(row.allocations[cargoId] ?? 0) > 0;
}

function configuredWarehouseByCargo(cargo) {
  if (!cargo) return null;
  if (cargo.cargo_location === 'vehicle') {
    return setup.cargos.vehicle.warehouse_id ? Number(setup.cargos.vehicle.warehouse_id) : null;
  }
  if (cargo.cargo_location === 'trailer') {
    return setup.cargos.trailer.warehouse_id ? Number(setup.cargos.trailer.warehouse_id) : null;
  }
  return null;
}

function convoySequenceByCargo(cargo) {
  if (!cargo) return null;
  if (cargo.cargo_location === 'vehicle') {
    return setup.cargos.vehicle.download_sequence ? Number(setup.cargos.vehicle.download_sequence) : null;
  }
  if (cargo.cargo_location === 'trailer') {
    return setup.cargos.trailer.download_sequence ? Number(setup.cargos.trailer.download_sequence) : null;
  }
  return cargo.download_sequence ? Number(cargo.download_sequence) : null;
}

function normalizeSequenceValue(value) {
  const parsed = Number(value);
  if (parsed === 1 || parsed === 2) return parsed;
  return null;
}

function syncComplementarySequence(changedSide) {
  if (!hasJourneyTrailer.value || syncingSequencePair.value) return;
  syncingSequencePair.value = true;
  try {
    const vehicleSeq = normalizeSequenceValue(setup.cargos.vehicle.download_sequence);
    const trailerSeq = normalizeSequenceValue(setup.cargos.trailer.download_sequence);

    if (changedSide === 'vehicle') {
      if (vehicleSeq !== null) {
        setup.cargos.trailer.download_sequence = vehicleSeq === 1 ? 2 : 1;
      } else if (trailerSeq !== null) {
        setup.cargos.vehicle.download_sequence = trailerSeq === 1 ? 2 : 1;
      }
      return;
    }

    if (changedSide === 'trailer') {
      if (trailerSeq !== null) {
        setup.cargos.vehicle.download_sequence = trailerSeq === 1 ? 2 : 1;
      } else if (vehicleSeq !== null) {
        setup.cargos.trailer.download_sequence = vehicleSeq === 1 ? 2 : 1;
      }
    }
  } finally {
    syncingSequencePair.value = false;
  }
}

function effectiveWarehouseByCargoId(cargoId) {
  const cargo = cargos.value.find((c) => Number(c.id) === Number(cargoId));
  if (!cargo) return null;
  const configured = configuredWarehouseByCargo(cargo);
  return configured ? Number(configured) : null;
}

function targetWarehouseByRowCargo(row, cargoId) {
  return effectiveWarehouseByCargoId(cargoId);
}

function hasConfiguredCargoWarehouse(row, cargoId) {
  return Boolean(targetWarehouseByRowCargo(row, cargoId));
}

function isRowCargoInPark(row, cargoId) {
  const cargo = cargos.value.find((c) => Number(c.id) === Number(cargoId));
  if (!cargo) return false;
  if (cargo.cargo_location === 'vehicle' && setup.cargos.vehicle.is_grounded) return true;
  if (cargo.cargo_location === 'trailer' && setup.cargos.trailer.is_grounded) return true;
  return false;
}

function hasMismatch(row, cargoId) {
  if (!hasAllocation(row, cargoId)) return false;
  if (!hasConfiguredCargoWarehouse(row, cargoId)) return false;
  if (isRowCargoInPark(row, cargoId)) return false;
  const plannedWarehouseId = row.planned_warehouse_id ?? null;
  const effectiveWarehouseId = targetWarehouseByRowCargo(row, cargoId);
  if (!plannedWarehouseId || !effectiveWarehouseId) return false;
  return Number(plannedWarehouseId) !== Number(effectiveWarehouseId);
}

function defaultSecondaryWarehouseId(cargoId) {
  const current = cargos.value.find((c) => Number(c.id) === Number(cargoId));
  const other = cargos.value.find((c) => Number(c.id) !== Number(cargoId));
  if (!current || !other) return null;
  const currentWarehouseId = effectiveWarehouseByCargoId(current.id);
  const otherWarehouseId = effectiveWarehouseByCargoId(other.id);
  if (!otherWarehouseId) return null;
  if (Number(otherWarehouseId) === Number(currentWarehouseId)) return null;
  return Number(otherWarehouseId);
}

function hasLinkedTransshipment(row, cargoId) {
  const fromWarehouseId = targetWarehouseByRowCargo(row, cargoId);
  const toWarehouseId = row?.planned_warehouse_id ? Number(row.planned_warehouse_id) : null;
  const orderItemId = row?.order_item_id ? Number(row.order_item_id) : null;
  if (!fromWarehouseId || !toWarehouseId || !orderItemId) return false;

  return transshipments.value.some((need) => {
    const status = normalizeTransshipmentStatus(need?.status);
    if (isTransshipmentCancelled(status)) return false;
    return Number(need?.order_item_id) === orderItemId
      && Number(need?.from_warehouse_id) === Number(fromWarehouseId)
      && Number(need?.to_warehouse_id) === Number(toWarehouseId);
  });
}

function syncInstructionPolicyForRow(row, cargo) {
  const instruction = row.instructions?.[cargo.id];
  const mismatch = row.mismatch_decisions?.[cargo.id];
  if (!instruction || !mismatch) return;

  const cargoInPark = isRowCargoInPark(row, cargo.id);
  const effectiveWarehouseId = effectiveWarehouseByCargoId(cargo.id);
  if (cargoInPark) {
    instruction.target_warehouse_id = null;
  } else if (effectiveWarehouseId) {
    instruction.target_warehouse_id = Number(effectiveWarehouseId);
  }

  if (!hasAllocation(row, cargo.id)) {
    instruction.instruction_type = 'simple';
    mismatch.decision = null;
    mismatch.secondary_warehouse_id = null;
    return;
  }

  if (cargoInPark) {
    instruction.instruction_type = 'drop_only';
    mismatch.decision = null;
    mismatch.secondary_warehouse_id = null;
    return;
  }

  if (!hasMismatch(row, cargo.id)) {
    instruction.instruction_type = 'simple';
    if (mismatch.decision !== 'double_unload') {
      mismatch.secondary_warehouse_id = null;
    }
    return;
  }

  if (!mismatch.decision && hasLinkedTransshipment(row, cargo.id)) {
    mismatch.decision = 'grounding';
  }

  if (mismatch.decision === 'double_unload') {
    instruction.instruction_type = 'double';
    if (!mismatch.secondary_warehouse_id) {
      mismatch.secondary_warehouse_id = defaultSecondaryWarehouseId(cargo.id);
    }
    return;
  }

  instruction.instruction_type = 'simple';
  mismatch.secondary_warehouse_id = null;
}

function syncInstructionPolicies(row = null) {
  const targetRows = row ? [row] : rows.value;
  for (const targetRow of targetRows) {
    for (const cargo of cargos.value) {
      syncInstructionPolicyForRow(targetRow, cargo);
    }
  }
}

function onMismatchDecisionChanged(row, cargoId) {
  if (isReadOnly.value) return;
  const cargo = cargos.value.find((c) => Number(c.id) === Number(cargoId));
  if (!cargo) return;
  syncInstructionPolicyForRow(row, cargo);
}

function cargoIdByLocation(location) {
  if (location === 'vehicle') return vehicleCargo.value?.id ?? null;
  if (location === 'trailer') return trailerCargo.value?.id ?? null;
  return null;
}

function plannedContainers(row, location) {
  const cargoId = cargoIdByLocation(location);
  if (!cargoId) return 0;
  return Number(row.planned_allocations[cargoId] ?? 0);
}

function allocationForLocation(row, location) {
  const cargoId = cargoIdByLocation(location);
  if (!cargoId) return 0;
  return Number(row.allocations[cargoId] ?? 0);
}

function canShowSplitToggle(row) {
  return canSplitAcrossCargos.value && Number(row.actual_containers ?? 0) > 1;
}

function normalizeAllocationTotal(row) {
  const total = Math.max(0, Number(row.actual_containers ?? 0));
  const vehicleId = cargoIdByLocation('vehicle');
  const trailerId = cargoIdByLocation('trailer');

  if (!vehicleId && !trailerId) return;
  if (!trailerId) {
    row.allocations[vehicleId] = total;
    syncInstructionPolicies(row);
    return;
  }
  if (!vehicleId) {
    row.allocations[trailerId] = total;
    syncInstructionPolicies(row);
    return;
  }

  if (!row.split_enabled) {
    const destination = row.real_location === 'trailer' ? trailerId : vehicleId;
    const other = destination === vehicleId ? trailerId : vehicleId;
    row.allocations[destination] = total;
    row.allocations[other] = 0;
    syncInstructionPolicies(row);
    return;
  }

  const currentVehicle = Math.max(0, Number(row.allocations[vehicleId] ?? 0));
  const normalizedVehicle = Math.min(currentVehicle, total);
  row.allocations[vehicleId] = normalizedVehicle;
  row.allocations[trailerId] = Math.max(0, total - normalizedVehicle);
  syncInstructionPolicies(row);
}

function toggleDestination(row) {
  if (isReadOnly.value) return;
  if (!vehicleCargo.value || !trailerCargo.value) return;
  row.real_location = row.real_location === 'trailer' ? 'vehicle' : 'trailer';
  row.split_enabled = false;
  normalizeAllocationTotal(row);
}

function toggleSplit(row, enabled) {
  if (isReadOnly.value) return;
  row.split_enabled = canShowSplitToggle(row) ? Boolean(enabled) : false;
  normalizeAllocationTotal(row);
}

function onActualContainersChanged(row) {
  if (isReadOnly.value) return;
  if (!canShowSplitToggle(row)) {
    row.split_enabled = false;
  }
  normalizeAllocationTotal(row);
}

function onSplitInput(row, location, rawValue) {
  if (isReadOnly.value) return;
  if (!row.split_enabled) return;
  const vehicleId = cargoIdByLocation('vehicle');
  const trailerId = cargoIdByLocation('trailer');
  if (!vehicleId || !trailerId) return;

  const total = Math.max(0, Number(row.actual_containers ?? 0));
  const inserted = Math.max(0, Number(rawValue ?? 0));
  const normalized = Math.min(inserted, total);

  if (location === 'vehicle') {
    row.allocations[vehicleId] = normalized;
    row.allocations[trailerId] = Math.max(0, total - normalized);
    syncInstructionPolicies(row);
    return;
  }

  row.allocations[trailerId] = normalized;
  row.allocations[vehicleId] = Math.max(0, total - normalized);
  syncInstructionPolicies(row);
}

function createBaseRows(journeyCargos) {
  return orderItems.value.map((item) => {
    const allocations = {};
    const plannedAllocations = {};
    const instructions = {};
    const mismatchDecisions = {};
    for (const cargo of journeyCargos) {
      allocations[cargo.id] = 0;
      plannedAllocations[cargo.id] = 0;
      instructions[cargo.id] = {
        target_warehouse_id: item.planned_warehouse_id ?? null,
        unload_sequence: cargo.download_sequence ?? null,
        instruction_type: 'simple',
      };
      mismatchDecisions[cargo.id] = {
        decision: null,
        secondary_warehouse_id: null,
      };
    }
    return {
      order_item_id: item.id,
      description: item.description,
      customer_name: item.customer_name,
      legacy_code: item.legacy_code,
      planned_warehouse_id: item.planned_warehouse_id,
      planned_containers: item.holder_quantity,
      holder_name: item.holder_name,
      actual_containers: item.holder_quantity,
      total_weight_kg: item.weight_declared,
      split_enabled: false,
      real_location: 'vehicle',
      planned_allocations: plannedAllocations,
      allocations,
      instructions,
      mismatch_decisions: mismatchDecisions,
    };
  });
}

function bindSetupFromWorkspace(workspace) {
  const vehicleCargo = (workspace?.cargos ?? []).find((c) => c.cargo_location === 'vehicle');
  const trailerCargo = (workspace?.cargos ?? []).find((c) => c.cargo_location === 'trailer');

  setup.cargos.vehicle.warehouse_id = vehicleCargo?.warehouse_id ?? null;
  setup.cargos.vehicle.download_sequence = vehicleCargo?.download_sequence ?? null;
  setup.cargos.vehicle.is_grounded = (vehicleCargo?.operation_mode ?? null) === 'drop_only'
    ? true
    : Boolean(vehicleCargo?.is_grounded ?? false);
  parkedWarehouseMemory.vehicle = setup.cargos.vehicle.warehouse_id ?? null;

  setup.cargos.trailer.warehouse_id = trailerCargo?.warehouse_id ?? null;
  setup.cargos.trailer.download_sequence = trailerCargo?.download_sequence ?? null;
  setup.cargos.trailer.is_grounded = (trailerCargo?.operation_mode ?? null) === 'drop_only'
    ? true
    : Boolean(trailerCargo?.is_grounded ?? false);
  parkedWarehouseMemory.trailer = setup.cargos.trailer.warehouse_id ?? null;
}

function bindWorkspaceData(workspace) {
  transshipments.value = normalizeTransshipments(
    workspace?.transshipment_needs ?? workspace?.transshipmentNeeds ?? []
  );

  bindSetupFromWorkspace(workspace);

  const journeyCargos = workspace?.cargos ?? [];
  cargos.value = journeyCargos;

  const byItem = new Map();
  const builtRows = createBaseRows(journeyCargos);
  for (const row of builtRows) byItem.set(Number(row.order_item_id), row);

  for (const census of workspace?.census?.items ?? []) {
    const row = byItem.get(Number(census.order_item_id));
    if (!row) continue;
    row.actual_containers = census.actual_containers ?? row.actual_containers;
    row.total_weight_kg = census.total_weight_kg ?? row.total_weight_kg;
  }

  const plannedAllocations = (workspace?.allocations ?? []).filter((a) => a.source === 'planned');
  for (const allocation of plannedAllocations) {
    const row = byItem.get(Number(allocation.order_item_id));
    if (!row) continue;
    row.planned_allocations[allocation.journey_cargo_id] = allocation.allocated_containers ?? 0;
  }

  const actualAllocations = (workspace?.allocations ?? []).filter((a) => a.source === 'actual');
  for (const allocation of actualAllocations) {
    const row = byItem.get(Number(allocation.order_item_id));
    if (!row) continue;
    row.allocations[allocation.journey_cargo_id] = allocation.allocated_containers ?? 0;

    const instruction = [...(allocation.instructions ?? [])]
      .sort((a, b) => Number(b?.id ?? 0) - Number(a?.id ?? 0))[0];
    if (!instruction) continue;
    row.instructions[allocation.journey_cargo_id] = {
      target_warehouse_id: instruction.target_warehouse_id ?? null,
      unload_sequence: instruction.unload_sequence ?? null,
      instruction_type: instruction.instruction_type ?? 'simple',
    };
  }

  for (const decision of (workspace?.mismatch_decisions ?? [])) {
    const row = byItem.get(Number(decision.order_item_id));
    if (!row) continue;
    if (!row.mismatch_decisions?.[decision.journey_cargo_id]) continue;
    row.mismatch_decisions[decision.journey_cargo_id] = {
      decision: decision.decision ?? null,
      secondary_warehouse_id: decision.secondary_warehouse_id ?? null,
    };
  }

  for (const row of builtRows) {
    const vehicleId = cargoIdByLocation('vehicle');
    const trailerId = cargoIdByLocation('trailer');

    const hasActual = Object.values(row.allocations).some((value) => Number(value ?? 0) > 0);
    if (!hasActual) {
      for (const cargo of journeyCargos) {
        row.allocations[cargo.id] = Number(row.planned_allocations[cargo.id] ?? 0);
      }
    }

    const vehicleContainers = vehicleId ? Number(row.allocations[vehicleId] ?? 0) : 0;
    const trailerContainers = trailerId ? Number(row.allocations[trailerId] ?? 0) : 0;
    row.split_enabled = vehicleContainers > 0 && trailerContainers > 0;

    if (!row.split_enabled) {
      if (trailerContainers > 0 && vehicleContainers === 0) {
        row.real_location = 'trailer';
      } else {
        row.real_location = 'vehicle';
      }
    } else {
      row.real_location = vehicleContainers >= trailerContainers ? 'vehicle' : 'trailer';
    }

    normalizeAllocationTotal(row);
  }

  rows.value = builtRows;
  syncInstructionPolicies();
  step1IsSaved.value = resolveStep1SavedState(workspace, builtRows);
  savedStep1Signature.value = currentStep1Signature.value;
}

async function loadWorkspace() {
  loading.value = true;
  try {
    const workspace = await fetchWorkspace(props.journey.id);
    bindWorkspaceData(workspace);
    if (workspace?.journey) emit('journey-updated', workspace.journey);
  } catch (error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: extractApiMessage(error, 'Impossibile caricare il workspace dispatch.') });
  } finally {
    loading.value = false;
  }
}

watch(
  () => setup.cargos.vehicle.is_grounded,
  (isGrounded, wasGrounded) => {
    if (isGrounded && !wasGrounded) {
      parkedWarehouseMemory.vehicle = setup.cargos.vehicle.warehouse_id ?? parkedWarehouseMemory.vehicle;
      setup.cargos.vehicle.warehouse_id = null;
    } else if (!isGrounded && wasGrounded) {
      if (!setup.cargos.vehicle.warehouse_id && parkedWarehouseMemory.vehicle) {
        setup.cargos.vehicle.warehouse_id = parkedWarehouseMemory.vehicle;
      }
    }
    syncInstructionPolicies();
  }
);

watch(
  () => setup.cargos.trailer.is_grounded,
  (isGrounded, wasGrounded) => {
    if (isGrounded && !wasGrounded) {
      parkedWarehouseMemory.trailer = setup.cargos.trailer.warehouse_id ?? parkedWarehouseMemory.trailer;
      setup.cargos.trailer.warehouse_id = null;
    } else if (!isGrounded && wasGrounded) {
      if (!setup.cargos.trailer.warehouse_id && parkedWarehouseMemory.trailer) {
        setup.cargos.trailer.warehouse_id = parkedWarehouseMemory.trailer;
      }
    }
    syncInstructionPolicies();
  }
);

watch(
  () => [setup.cargos.vehicle.warehouse_id, setup.cargos.trailer.warehouse_id],
  () => {
    if (!setup.cargos.vehicle.is_grounded && setup.cargos.vehicle.warehouse_id) {
      parkedWarehouseMemory.vehicle = setup.cargos.vehicle.warehouse_id;
    }
    if (!setup.cargos.trailer.is_grounded && setup.cargos.trailer.warehouse_id) {
      parkedWarehouseMemory.trailer = setup.cargos.trailer.warehouse_id;
    }
    syncInstructionPolicies();
  }
);

watch(
  () => setup.cargos.vehicle.download_sequence,
  () => {
    syncComplementarySequence('vehicle');
    syncInstructionPolicies();
  }
);

watch(
  () => setup.cargos.trailer.download_sequence,
  () => {
    syncComplementarySequence('trailer');
    syncInstructionPolicies();
  }
);

function goToStep2() {
  if (!canAccessStep2.value) {
    store.dispatch('flash/queueMessage', { type: 'warning', text: 'Salva prima il censimento (Step 1).' });
    return;
  }
  activeStep.value = 2;
}

function buildCensusPayload() {
  const payload = { census: { items: [] }, allocations: [] };

  for (const row of rows.value) {
    payload.census.items.push({
      order_item_id: row.order_item_id,
      actual_containers: Number(row.actual_containers ?? 0),
      total_weight_kg: row.total_weight_kg === '' || row.total_weight_kg === null ? null : Number(row.total_weight_kg),
      source: 'phone',
    });

    for (const cargo of cargos.value) {
      const allocated = Number(row.allocations[cargo.id] ?? 0);
      if (allocated <= 0) continue;

      payload.allocations.push({
        journey_cargo_id: cargo.id,
        order_item_id: row.order_item_id,
        allocated_containers: allocated,
        source: 'actual',
      });
    }
  }

  return payload;
}

function buildOperationalPayload() {
  const payload = { allocations: [], unload_instructions: [], mismatch_decisions: [] };

  for (const row of rows.value) {
    for (const cargo of cargos.value) {
      const allocated = Number(row.allocations[cargo.id] ?? 0);
      if (allocated <= 0) continue;

      const cargoInPark = isRowCargoInPark(row, cargo.id);
      const instruction = row.instructions[cargo.id] ?? {};
      const targetWarehouseId = targetWarehouseByRowCargo(row, cargo.id);

      payload.allocations.push({
        journey_cargo_id: cargo.id,
        order_item_id: row.order_item_id,
        allocated_containers: allocated,
        source: 'actual',
      });

      if (!cargoInPark && targetWarehouseId) {
        payload.unload_instructions.push({
          journey_cargo_id: cargo.id,
          order_item_id: row.order_item_id,
          target_warehouse_id: Number(targetWarehouseId),
          unload_sequence: convoySequenceByCargo(cargo),
          instruction_type: instruction.instruction_type ?? 'simple',
        });
      }

      const mismatch = row.mismatch_decisions?.[cargo.id] ?? {};
      if (!cargoInPark && hasMismatch(row, cargo.id)) {
        const decision = mismatch.decision ?? null;
        if (!decision) continue;
        payload.mismatch_decisions.push({
          journey_cargo_id: cargo.id,
          order_item_id: row.order_item_id,
          decision,
          secondary_warehouse_id: decision === 'double_unload' && mismatch.secondary_warehouse_id
            ? Number(mismatch.secondary_warehouse_id)
            : null,
        });
      }
    }
  }

  return payload;
}

async function saveStep1Data() {
  if (isReadOnly.value) return;
  saving.value = true;
  try {
    const payload = buildCensusPayload();
    await saveWorkspace(props.journey.id, payload);
    step1IsSaved.value = true;
    savedStep1Signature.value = currentStep1Signature.value;
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Censimento salvato.' });
    await loadWorkspace();
  } catch (error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: extractApiMessage(error, error?.message ?? 'Salvataggio censimento non riuscito.') });
  } finally {
    saving.value = false;
  }
}

async function saveStep2Data() {
  if (isReadOnly.value) return;
  saving.value = true;
  try {
    const derivedIsDoubleLoad = Boolean(
      hasJourneyTrailer.value &&
      setup.cargos.vehicle.warehouse_id &&
      setup.cargos.trailer.warehouse_id &&
      Number(setup.cargos.vehicle.warehouse_id) !== Number(setup.cargos.trailer.warehouse_id)
    );
    const derivedIsTemporaryStorage = Boolean(
      setup.cargos.vehicle.is_grounded ||
      (hasJourneyTrailer.value && setup.cargos.trailer.is_grounded)
    );

    const planData = await updateDispatchPlan(props.journey.id, {
      is_double_load: derivedIsDoubleLoad,
      is_temporary_storage: derivedIsTemporaryStorage,
    });

    await saveJourneyCargos(props.journey.id, [
      {
        cargo_location: 'vehicle',
        enabled: true,
        warehouse_id: setup.cargos.vehicle.is_grounded ? null : setup.cargos.vehicle.warehouse_id,
        download_sequence: setup.cargos.vehicle.download_sequence,
        is_grounded: setup.cargos.vehicle.is_grounded,
        operation_mode: setup.cargos.vehicle.is_grounded ? 'drop_only' : 'unload',
      },
      {
        cargo_location: 'trailer',
        enabled: hasJourneyTrailer.value,
        warehouse_id: setup.cargos.trailer.is_grounded ? null : setup.cargos.trailer.warehouse_id,
        download_sequence: setup.cargos.trailer.download_sequence,
        is_grounded: setup.cargos.trailer.is_grounded,
        operation_mode: setup.cargos.trailer.is_grounded ? 'drop_only' : 'unload',
      },
    ]);

    const payload = buildOperationalPayload();
    await saveWorkspace(props.journey.id, payload);
    emit('journey-updated', planData?.journey ?? {});
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Operativita salvata.' });
    await loadWorkspace();
  } catch (error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: extractApiMessage(error, error?.message ?? 'Salvataggio operativita non riuscito.') });
  } finally {
    saving.value = false;
  }
}

async function confirmBaseline() {
  if (isReadOnly.value) return;
  saving.value = true;
  try {
    const data = await confirmWorkspace(props.journey.id);
    emit('journey-updated', data?.journey ?? { dispatch_status: DISPATCH_STATUS.IN_PROGRESS });
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Baseline confermata.' });
    await loadWorkspace();
  } catch (error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: extractApiMessage(error, 'Conferma baseline non riuscita.') });
  } finally {
    saving.value = false;
  }
}

async function sendAction(type) {
  if (isReadOnly.value) return;
  saving.value = true;
  try {
    if (type === 'hold') {
      const data = await holdDispatch(props.journey.id, actionNotes.value);
      emit('journey-updated', data?.journey ?? { dispatch_status: DISPATCH_STATUS.ON_HOLD });
      store.dispatch('flash/queueMessage', { type: 'success', text: 'Viaggio messo in attesa.' });
      await loadWorkspace();
      return;
    }

    if (type === 'resume') {
      const data = await resumeDispatch(props.journey.id, actionNotes.value);
      emit('journey-updated', data?.journey ?? { dispatch_status: DISPATCH_STATUS.IN_PROGRESS });
      store.dispatch('flash/queueMessage', { type: 'success', text: 'Viaggio ripreso correttamente.' });
      await loadWorkspace();
      return;
    }

    if (type !== 'close') {
      return;
    }

    const data = await closeDispatchAudit(props.journey.id, actionNotes.value);
    emit('journey-updated', data?.journey ?? { dispatch_status: DISPATCH_STATUS.MANAGED });
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Chiusura viaggio completata correttamente.' });
    await loadWorkspace();
  } catch (error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: extractApiMessage(error, 'Errore durante l\'operazione dispatch.') });
  } finally {
    saving.value = false;
  }
}

async function approveTransshipmentData(transshipmentId) {
  if (isReadOnly.value) return;
  saving.value = true;
  try {
    await approveTransshipment(transshipmentId);
    store.dispatch('flash/queueMessage', { type: 'success', text: 'Trasbordo approvato.' });
    await loadWorkspace();
  } catch (error) {
    store.dispatch('flash/queueMessage', { type: 'error', text: extractApiMessage(error, 'Approvazione trasbordo non riuscita.') });
  } finally {
    saving.value = false;
  }
}

loadWorkspace();
</script>
