<template>
  <div class="w-full h-full flex flex-col gap-4">
    <div>
      <h3 class="text-lg font-semibold">
        <font-awesome-icon :icon="['fas', 'robot']" />
        Assistente NLP
      </h3>
      <p class="text-sm opacity-70">
        Inserisci una query in linguaggio naturale per ridurre i siti mostrati.
      </p>
    </div>

    <label class="form-control w-full">
      <div class="label">
        <span class="label-text">Query</span>
      </div>
      <textarea
        v-model="queryText"
        class="textarea textarea-bordered h-28"
        placeholder="Es: clienti vicino a Rossi entro 50 km non pericolosi"
      />
    </label>

    <div class="flex gap-2">
      <button class="btn btn-primary btn-sm" :disabled="isParsing || !queryText.trim()" @click="parseQuery">
        <span v-if="isParsing" class="loading loading-spinner loading-xs" />
        {{ isParsing ? 'Parsing...' : 'Parse' }}
      </button>
      <button class="btn btn-ghost btn-sm" :disabled="!parsedQuery" @click="resetAll">Reset</button>
    </div>

    <div v-if="parseError" class="alert alert-error py-2">
      <span class="text-sm">{{ parseError }}</span>
    </div>

    <div v-if="parsedQuery" class="space-y-2">
      <div class="text-sm font-medium">Preview JSON</div>
      <pre class="bg-base-200 rounded p-3 text-xs overflow-auto max-h-48">{{ prettyParsedQuery }}</pre>
    </div>

    <div v-if="parseWarning" class="alert alert-warning py-2">
      <span class="text-sm">{{ parseWarning }}</span>
    </div>

    <div v-if="parsedQuery" class="space-y-2">
      <div class="text-sm">
        Candidati trovati: <span class="font-semibold">{{ candidateCount }}</span> su {{ totalSites }}
      </div>
      <div class="flex gap-2">
        <button class="btn btn-success btn-sm" @click="applyCandidates">Applica alla mappa</button>
        <button class="btn btn-outline btn-sm" @click="goToJourney">Vai a Journey/Create</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  sites: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['apply-candidates', 'clear-candidates']);

const queryText = ref('');
const isParsing = ref(false);
const parseError = ref('');
const parseWarning = ref('');
const parsedQuery = ref(null);
const candidateSiteIds = ref([]);

const prettyParsedQuery = computed(() => JSON.stringify(parsedQuery.value, null, 2));
const candidateCount = computed(() => candidateSiteIds.value.length);
const totalSites = computed(() => props.sites.length);

function normalizeText(value) {
  return (value || '').toLowerCase().trim();
}

function toRadians(degrees) {
  return (degrees * Math.PI) / 180;
}

function haversineKm(lat1, lng1, lat2, lng2) {
  const earthRadiusKm = 6371;
  const dLat = toRadians(lat2 - lat1);
  const dLng = toRadians(lng2 - lng1);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2;
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return earthRadiusKm * c;
}

function candidateIdsFromParsed(query) {
  let candidates = [...props.sites];
  const ref = query?.reference;

  const refName = normalizeText(ref?.customer?.name || ref?.site?.name || '');

  if (query?.site_filters?.risk_min != null || query?.site_filters?.risk_max != null) {
    const riskMin = query?.site_filters?.risk_min;
    const riskMax = query?.site_filters?.risk_max;
    candidates = candidates.filter((site) => {
      const riskRaw = site?.calculated_risk_factor ?? site?.calculated_risk_factor;
      if (riskRaw == null) return false;
      const risk = Number(riskRaw);
      if (riskMin != null && risk < Number(riskMin)) return false;
      if (riskMax != null && risk > Number(riskMax)) return false;
      return true;
    });
  }

  if (query?.site_filters?.days_to_next_pickup_max != null) {
    candidates = candidates.filter((site) => {
      const daysRaw = site?.days_until_next_withdraw;
      if (daysRaw == null) return false;
      return Number(daysRaw) <= Number(query.site_filters.days_to_next_pickup_max);
    });
  }

  if (query?.geo?.radius_km && refName) {
    const refSite = props.sites.find((site) => {
      const customerName = site?.customer?.company_name || '';
      return normalizeText(customerName).includes(refName);
    });

    if (refSite?.latitude != null && refSite?.longitude != null) {
      const originLat = Number(refSite.latitude);
      const originLng = Number(refSite.longitude);
      candidates = candidates.filter((site) => {
        if (site?.latitude == null || site?.longitude == null) return false;
        const distance = haversineKm(originLat, originLng, Number(site.latitude), Number(site.longitude));
        return distance <= Number(query.geo.radius_km);
      });
    }
  }

  return candidates.map((site) => site.id);
}

async function parseQuery() {
  parseError.value = '';
  parseWarning.value = '';
  isParsing.value = true;
  try {
    const response = await axios.post('/api/nlp/logistics/parse', {
      query: queryText.value,
      context: {
        source: 'map',
      },
    });

    const query = response?.data?.parsed;
    if (!query) {
      throw new Error('Missing parsed query payload');
    }

    parsedQuery.value = query;
    candidateSiteIds.value = candidateIdsFromParsed(query);
    const warnings = Array.isArray(response?.data?.warnings) ? response.data.warnings : [];
    parseWarning.value = warnings.join(' ');
  } catch (error) {
    parseError.value = error?.response?.data?.error?.message || 'Parsing non riuscito. Riprova con una query piu specifica.';
  } finally {
    isParsing.value = false;
  }
}

function applyCandidates() {
  emit('apply-candidates', {
    parsedQuery: parsedQuery.value,
    siteIds: candidateSiteIds.value,
  });
}

function resetAll() {
  queryText.value = '';
  parsedQuery.value = null;
  candidateSiteIds.value = [];
  parseError.value = '';
  parseWarning.value = '';
  emit('clear-candidates');
}

function goToJourney() {
  const payload = parsedQuery.value ? JSON.stringify(parsedQuery.value) : '';
  router.visit(route('journey.create', { nlp_query: payload }));
}
</script>


