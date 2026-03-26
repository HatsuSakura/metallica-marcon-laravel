<script setup>
import axios from 'axios';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useStore } from 'vuex';
import { ORDER_DOCUMENTS_STATUS, ORDER_STATUS, normalizeOrderDocumentsStatus, normalizeOrderStatus } from '@/Constants/orderStatus';

const props = defineProps({
  journeyId: {
    type: Number,
    default: null,
  },
  selectedOrders: {
    type: Array,
    default: () => [],
  },
});

const store = useStore();
const loading = ref(false);
const generatingAll = ref(false);
const generatingByOrder = ref({});
const serverOrders = ref([]);
const summary = ref({
  total: 0,
  ready: 0,
  not_ready: 0,
  all_ready: false,
});
const POLLING_INTERVAL_MS = 5000;
let pollingTimer = null;

const selectedOrderIds = computed(() => {
  const ids = new Set();
  for (const order of props.selectedOrders ?? []) {
    if (!order?.id) continue;
    ids.add(Number(order.id));
  }
  return Array.from(ids.values());
});

const filteredServerOrders = computed(() => {
  const ids = new Set(selectedOrderIds.value);
  return (serverOrders.value ?? []).filter((order) => ids.has(Number(order.id)));
});

const selectedSummary = computed(() => {
  const total = filteredServerOrders.value.length;
  const ready = filteredServerOrders.value
    .filter((order) => normalizeOrderStatus(order.status) === ORDER_STATUS.READY)
    .length;
  return {
    total,
    ready,
    notReady: Math.max(0, total - ready),
    allReady: total > 0 && ready === total,
  };
});

const hasGeneratingOrders = computed(() =>
  (serverOrders.value ?? []).some(
    (order) => normalizeOrderDocumentsStatus(order?.documents_status) === ORDER_DOCUMENTS_STATUS.GENERATING
  )
);

async function refresh() {
  if (!props.journeyId) return;
  loading.value = true;
  try {
    const response = await axios.get(`/api/journeys/${props.journeyId}/documents-status`);
    serverOrders.value = response?.data?.orders ?? [];
    summary.value = response?.data?.summary ?? summary.value;
  } catch (error) {
    serverOrders.value = [];
    summary.value = { total: 0, ready: 0, not_ready: 0, all_ready: false };
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: error?.response?.data?.message ?? 'Errore nel caricamento stato documenti journey.',
    });
  } finally {
    loading.value = false;
  }
}

async function generateBulk() {
  if (!props.journeyId || generatingAll.value || selectedOrderIds.value.length === 0) return;
  generatingAll.value = true;
  try {
    const response = await axios.post(`/api/journeys/${props.journeyId}/generate-documents`, {
      order_ids: selectedOrderIds.value,
    });
    store.dispatch('flash/queueMessage', {
      type: response?.data?.type ?? 'success',
      text: response?.data?.message ?? 'Generazione documenti avviata.',
    });
    await refresh();
  } catch (error) {
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: error?.response?.data?.message ?? 'Errore durante la generazione bulk dei documenti.',
    });
  } finally {
    generatingAll.value = false;
  }
}

async function generateSingle(orderId) {
  if (!props.journeyId || !orderId || generatingByOrder.value[orderId]) return;
  generatingByOrder.value = { ...generatingByOrder.value, [orderId]: true };
  try {
    const response = await axios.post(`/api/journeys/${props.journeyId}/generate-documents`, {
      order_ids: [Number(orderId)],
    });
    store.dispatch('flash/queueMessage', {
      type: response?.data?.type ?? 'success',
      text: response?.data?.message ?? `Generazione avviata per ordine #${orderId}.`,
    });
    await refresh();
  } catch (error) {
    store.dispatch('flash/queueMessage', {
      type: 'error',
      text: error?.response?.data?.message ?? `Errore generazione documenti per ordine #${orderId}.`,
    });
  } finally {
    const next = { ...generatingByOrder.value };
    delete next[orderId];
    generatingByOrder.value = next;
  }
}

function documentsBadgeClass(state) {
  const normalized = normalizeOrderDocumentsStatus(state);
  if (normalized === ORDER_DOCUMENTS_STATUS.GENERATED) return 'badge-success';
  if (normalized === ORDER_DOCUMENTS_STATUS.GENERATING) return 'badge-info';
  if (normalized === ORDER_DOCUMENTS_STATUS.FAILED) return 'badge-error';
  return 'badge-warning';
}

function readinessBadgeClass(status) {
  return normalizeOrderStatus(status) === ORDER_STATUS.READY ? 'badge-success' : 'badge-warning';
}

function downloadUrl(order, type) {
  const fileName = type === 'xlsx' ? order?.model_document_name : order?.adr_hp_document_name;
  if (!order?.id || !fileName) return '#';
  return `/api/orders/${order.id}/documents/${encodeURIComponent(fileName)}/download`;
}

function pdfAvailabilityLabel(order) {
  if (order?.has_adr_hp_document) return '';
  if (order?.requires_adr_hp_document) return 'PDF non ancora generato';
  return 'Etichette non necessarie';
}

function xlsAvailabilityLabel(order) {
  return order?.has_model_document ? '' : 'XLS non ancora generato';
}

function stopPolling() {
  if (pollingTimer) {
    clearInterval(pollingTimer);
    pollingTimer = null;
  }
}

function startPolling() {
  if (pollingTimer || !props.journeyId) return;
  pollingTimer = setInterval(() => {
    refresh();
  }, POLLING_INTERVAL_MS);
}

watch(
  () => props.journeyId,
  (journeyId) => {
    stopPolling();
    if (!journeyId) {
      serverOrders.value = [];
      summary.value = { total: 0, ready: 0, not_ready: 0, all_ready: false };
      return;
    }
    refresh();
  },
  { immediate: true }
);

watch(
  hasGeneratingOrders,
  (isGenerating) => {
    if (isGenerating) {
      startPolling();
      return;
    }

    stopPolling();
  },
  { immediate: true }
);

onBeforeUnmount(() => {
  stopPolling();
});
</script>

<template>
  <div class="card bg-base-100 border border-base-200 shadow-sm mt-4">
    <div class="card-body p-4 space-y-3">
      <div class="flex items-center justify-between">
        <div class="font-semibold">Documentazione Ordini associati al Viaggio</div>
        <button v-if="journeyId" type="button" class="btn btn-ghost btn-sm" :disabled="loading" @click="refresh">
          <font-awesome-icon :icon="['fas', 'rotate-right']" />
          Aggiorna
        </button>
      </div>

      <div v-if="!journeyId" class="alert alert-info">
        <span>Salva prima il viaggio. Dopo il salvataggio potrai generare i documenti per ordine o in bulk.</span>
      </div>

      <template v-else>
        <div class="flex flex-wrap gap-2 text-sm">
          <span class="badge badge-neutral">Journey ordini: {{ summary.total }}</span>
          <span class="badge badge-success">Journey ready: {{ summary.ready }}</span>
          <span class="badge" :class="selectedSummary.allReady ? 'badge-success' : 'badge-warning'">
            Selezione pronta: {{ selectedSummary.ready }}/{{ selectedSummary.total }}
          </span>
        </div>

        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="btn btn-primary btn-sm"
            :disabled="generatingAll || selectedOrderIds.length === 0"
            @click="generateBulk"
          >
            <font-awesome-icon :icon="['fas', 'file-export']" />
            {{ generatingAll ? 'Avvio...' : 'Genera documenti' }}
          </button>
          <span class="text-xs opacity-70 self-center">
            Avvia la generazione su tutti gli ordini presenti nel viaggio.
          </span>
        </div>

        <div v-if="filteredServerOrders.length === 0" class="text-sm opacity-70">
          Nessun ordine selezionato salvato su questo journey.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>Ordine</th>
                <th>Cliente</th>
                <th>Ordine</th>
                <th>Documenti</th>
                <th>Download</th>
                <th>Azione</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in filteredServerOrders" :key="order.id">
                <td>#{{ order.id }} ({{ order.legacy_code || 'N/D' }})</td>
                <td>{{ order.customer_name || '-' }}</td>
                <td><span class="badge" :class="readinessBadgeClass(order.status)">{{ order.status }}</span></td>
                <td>
                  <span class="badge" :class="documentsBadgeClass(order.documents_status)">{{ order.documents_status }}</span>
                      <div class="text-xs opacity-70 whitespace-nowrap">
                      {{ xlsAvailabilityLabel(order) }} {{ pdfAvailabilityLabel(order) }}
                    </div>
                </td>
                <td>
                  <div class="inline-flex flex-col gap-1">
                    <div class="flex nowrap gap-2">
                      <div v-if="order.has_model_document" class="tooltip tooltip-success" data-tip="Modulo Ritiro">
                        <a
                          class="btn btn-ghost btn-sm"
                          :class="{ 'btn-disabled pointer-events-none opacity-50': !order.has_model_document }"
                          :href="order.has_model_document ? downloadUrl(order, 'xlsx') : undefined"
                          :aria-disabled="!order.has_model_document"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          <font-awesome-icon :icon="['fas', 'file-excel']" class="text-green-600 text-xl" />
                        </a>
                      </div>
                      <div v-if="order.has_adr_hp_document" class="tooltip tooltip-error" data-tip="Etichette PERICOLOSI">
                        <a
                          class="btn btn-ghost btn-sm"
                          :class="{ 'btn-disabled pointer-events-none opacity-50': !order.has_adr_hp_document }"
                          :href="order.has_adr_hp_document ? downloadUrl(order, 'pdf') : undefined"
                          :aria-disabled="!order.has_adr_hp_document"
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          <font-awesome-icon :icon="['fas', 'file-pdf']" class="text-red-500 text-xl" />
                        </a>
                      </div>
                    </div>

                  </div>
                </td>
                <td>
                  <button
                    type="button"
                    class="btn btn-outline btn-xs"
                    :disabled="Boolean(generatingByOrder[order.id])"
                    @click="generateSingle(order.id)"
                  >
                    {{ generatingByOrder[order.id] ? 'Avvio...' : 'Genera' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </div>
</template>
