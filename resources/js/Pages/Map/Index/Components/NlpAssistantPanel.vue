<template>
  <div class="w-full h-full flex flex-col gap-4">
    <div>
      <h3 class="text-lg font-semibold">
        <font-awesome-icon :icon="['fas', 'robot']" />
        Assistente NLP
      </h3>
      <p class="text-sm opacity-70">
        Inserisci una query in linguaggio naturale per filtrare i siti in mappa.
      </p>
    </div>

    <label class="form-control w-full">
      <div class="label">
        <span class="label-text">Query</span>
      </div>
      <textarea
        v-model="queryText"
        class="textarea textarea-bordered h-28"
        placeholder="Es: clienti vicino a CAREL entro 30 km con materiali ferrosi"
      />
    </label>

    <!-- Buttons row: [Cerca] [AI] [Filtri]   [confidence]   [Reset] -->
    <div class="flex items-center gap-2">

      <button
        class="btn btn-primary btn-sm"
        :disabled="isLoading || !queryText.trim()"
        @click="executeQuery"
      >
        <span v-if="isLoading" class="loading loading-spinner loading-xs" />
        {{ isLoading ? 'Ricerca...' : 'Cerca' }}
      </button>

      <label class="flex items-center gap-1 cursor-pointer select-none" title="Usa il modello AI per interpretazioni complesse">
        <input type="checkbox" v-model="aiEnabled" class="toggle toggle-secondary toggle-xs" />
        <span class="text-xs font-medium flex items-center gap-1">
          <font-awesome-icon :icon="['fas', 'wand-magic-sparkles']" class="text-secondary" />
          AI
        </span>
      </label>

      <label class="flex items-center gap-1 cursor-pointer select-none" title="Interseca i risultati con i filtri attivi sulla mappa">
        <input type="checkbox" v-model="respectFiltersModel" class="toggle toggle-accent toggle-xs" />
        <span class="text-xs font-medium flex items-center gap-1">
          <font-awesome-icon :icon="['fas', 'filter']" class="text-accent" />
          Filtri
        </span>
      </label>

      <!-- Confidence pill — center -->
      <div class="flex-1 flex justify-center">
        <span
          v-if="confidence"
          class="badge badge-sm font-semibold cursor-help"
          :class="confidenceBadgeClass"
          :title="confidence.issues.join(' — ') || 'Tutti i criteri risolti correttamente.'"
        >
          {{ confidenceLabel }}
        </span>
      </div>

      <!-- Reset — right -->
      <button class="btn btn-ghost btn-sm" :disabled="!hasResult && !ambiguousReference" @click="resetAll">
        Reset
      </button>
    </div>

    <!-- API error -->
    <div v-if="error" class="alert alert-error py-2">
      <span class="text-sm">{{ error }}</span>
    </div>

    <!-- Disambiguation prompt -->
    <div v-if="ambiguousReference" class="alert alert-warning py-2 flex flex-col items-start gap-2">
      <div class="text-sm font-semibold">
        Ambiguità: "{{ ambiguousReference.token }}" corrisponde a più clienti. Scegli:
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="candidate in ambiguousReference.candidates"
          :key="candidate.id"
          class="btn btn-sm btn-outline"
          @click="selectCustomer(candidate)"
        >
          {{ candidate.name }}
        </button>
      </div>
    </div>

    <!-- Low/medium confidence alert (heuristic only) -->
    <div
      v-if="showConfidenceAlert"
      class="alert alert-warning py-2 flex flex-col items-start gap-1"
    >
      <div v-for="issue in confidence.issues" :key="issue" class="text-sm">⚠ {{ issue }}</div>
      <div class="text-xs opacity-80 mt-1">
        Prova ad attivare la ricerca <strong>AI</strong> per un risultato più preciso.
      </div>
    </div>

    <!-- Filter exclusion alert — reactive, no re-query needed -->
    <div v-if="showFilterExclusionAlert" class="alert alert-info py-2 flex flex-col items-start gap-1">
      <div v-if="filteredCount === 0" class="text-sm font-semibold">
        ⚠ Tutti i {{ rawSiteCount }} risultati sono esclusi dai filtri mappa attivi.
      </div>
      <div v-else class="text-sm">
        {{ rawSiteCount - filteredCount }} risultati esclusi dai filtri mappa attivi.
      </div>
      <div class="text-xs opacity-80 mt-1">
        Disattiva <strong>Filtri</strong> per vedere l'intero risultato della query.
      </div>
    </div>

    <!-- Result summary -->
    <div v-if="hasResult" class="text-sm">
      Candidati trovati:
      <span class="font-semibold">{{ filteredCount }}</span>
      <template v-if="respectFiltersModel && rawSiteCount !== filteredCount">
        <span class="opacity-50"> / {{ rawSiteCount }} totali</span>
      </template>
    </div>

    <!-- JSON preview — developer only -->
    <details v-if="isDeveloper && parsedQuery" class="text-xs">
      <summary class="cursor-pointer text-sm font-medium opacity-60 mb-1">Preview JSON</summary>
      <pre class="bg-base-200 rounded p-3 overflow-auto max-h-48">{{ prettyParsedQuery }}</pre>
    </details>

    <!-- Journey action -->
    <div v-if="hasResult">
      <button class="btn btn-outline btn-sm" @click="goToJourney">
        Vai a Journey/Create
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  sites: { type: Array, default: () => [] },
  respectFilters: { type: Boolean, default: true },
  filteredCount: { type: Number, default: 0 },
});

const emit = defineEmits(['apply-candidates', 'clear-candidates', 'update:respectFilters']);

// v-model bridge for respectFilters (owned by Index.vue)
const respectFiltersModel = computed({
  get: () => props.respectFilters,
  set: (val) => emit('update:respectFilters', val),
});

// AI toggle — persisted locally
const aiEnabled = ref(localStorage.getItem('nlp_ai_enabled') === 'true');
watch(aiEnabled, (val) => localStorage.setItem('nlp_ai_enabled', String(val)));

// Developer role check
const page = usePage();
const isDeveloper = computed(() => page.props.auth?.user?.role === 'developer');

const queryText        = ref('');
const isLoading        = ref(false);
const error            = ref('');
const parsedQuery      = ref(null);
const confidence       = ref(null);
const hasResult        = ref(false);
const rawSiteCount     = ref(0);
const ambiguousReference = ref(null); // { token, candidates: [{id, name}] }
const forcedCustomer   = ref(null);   // { id, name } — set after user picks from disambiguation

const prettyParsedQuery = computed(() => JSON.stringify(parsedQuery.value, null, 2));

const confidenceBadgeClass = computed(() => ({
  'badge-success': confidence.value?.level === 'alta',
  'badge-warning': confidence.value?.level === 'media',
  'badge-error':   confidence.value?.level === 'bassa',
}));
const confidenceLabel = computed(() => ({ alta: 'Alta', media: 'Media', bassa: 'Bassa' })[confidence.value?.level] ?? '');

const showConfidenceAlert = computed(() =>
  !aiEnabled.value && confidence.value?.level !== 'alta' && (confidence.value?.issues?.length ?? 0) > 0,
);

const showFilterExclusionAlert = computed(() =>
  hasResult.value && props.respectFilters && rawSiteCount.value > props.filteredCount,
);

async function executeQuery(forced = null) {
  error.value            = '';
  confidence.value       = null;
  ambiguousReference.value = null;
  isLoading.value        = true;

  const context = { source: 'map', ai: aiEnabled.value };
  if (forced) context.force_customer = forced;

  try {
    const response = await axios.post('/api/nlp/logistics/execute', {
      query: queryText.value,
      context,
    });

    parsedQuery.value = response.data.parsed ?? null;
    confidence.value  = response.data.confidence ?? null;

    const ambig = response.data.ambiguousReference ?? null;
    if (ambig && ambig.candidates?.length > 1) {
      // Pause: ask user to pick a customer — do not apply to map yet
      ambiguousReference.value = ambig;
      console.log('[NLP] ambiguousReference set, returning early');
      isLoading.value = false;
      return;
    }

    const returnedSites = response.data.result?.sites ?? [];
    rawSiteCount.value  = returnedSites.length;
    hasResult.value     = true;

    emit('apply-candidates', {
      parsedQuery: parsedQuery.value,
      siteIds: returnedSites.map((s) => Number(s.id)),
      sites: returnedSites,
    });

  } catch (err) {
    error.value      = err?.response?.data?.error?.message ?? 'Ricerca non riuscita. Riprova con una query più specifica.';
    parsedQuery.value  = null;
    rawSiteCount.value = 0;
    hasResult.value    = false;
  } finally {
    isLoading.value = false;
  }
}

function selectCustomer(candidate) {
  forcedCustomer.value     = candidate;
  ambiguousReference.value = null;
  executeQuery(candidate);
}

function resetAll() {
  queryText.value          = '';
  parsedQuery.value        = null;
  confidence.value         = null;
  error.value              = '';
  rawSiteCount.value       = 0;
  hasResult.value          = false;
  ambiguousReference.value = null;
  forcedCustomer.value     = null;
  emit('clear-candidates');
}

function goToJourney() {
  const payload = parsedQuery.value ? JSON.stringify(parsedQuery.value) : '';
  router.visit(route('journey.create', { nlp_query: payload }));
}
</script>
