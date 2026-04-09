<template>
  <!-- MAIN ROW DIV -->
  <div class="flex flex-col items-start gap-2 mb-2">
    <div class="flex flex-row flex-wrap gap-2 w-full">

      <div class="flex items-center gap-2">
        <template v-if="isEditingCer || !item.cer_code_id">
          <v-select
            v-model="item.cer_code_id"
            :id="'cer-' + index"
            :options="props.cerList"
            label="code"
            :reduce="cer => cer.id"
            :filterable="true"
            :searchable="true"
            placeholder="Cod. CER"
            class="custom-style-chooser w-36 min-w-max"
            :class="[getCerStyle(item.cer_code_id), guidanceClass('cer_code_id')]"
          >
            <template #option="{ code, description, is_dangerous }">
              <span :class="Number(is_dangerous) === 1 ? 'cer-list-dangerous' : 'cer-list-normal'">{{ code }}</span>
              <br />
              <span class="text-xs"><cite>{{ description }}</cite></span>
            </template>
          </v-select>

          <button type="button" class="btn btn-sm btn-primary" :disabled="!item.cer_code_id" @click="confirmCerEdit">
            OK
          </button>
          <button v-if="lastCerCodeBeforeEdit" type="button" class="btn btn-sm btn-ghost" @click="cancelCerEdit">
            Annulla
          </button>
        </template>

        <template v-else>
          <div class="badge badge-lg" :class="getCerStyle(item.cer_code_id)">
            {{ selectedCerCode?.code }}
          </div>
          <div class="tooltip" data-tip="Cambia CER">
            <button type="button" class="btn btn-sm btn-ghost btn-circle" @click="startCerEdit">
              <font-awesome-icon :icon="['fas', 'arrows-turn-to-dots']" />
            </button>
          </div>
        </template>
      </div>

      <!-- CER Group Selector -->
      <div v-if="item.cer_code_id">
        <select :value="selectedGroupToken" @change="onGroupSelectionChange($event.target.value)" class="select select-bordered w-32">
          <option disabled value="">Gruppo</option>
          <option v-for="group in groupsForCurrentCer" :key="group.token" :value="group.token">
            {{ group.label }}
          </option>
          <option value="__new__">+ Nuovo</option>
        </select>
      </div>

      <!-- Toggle SFUSO -->
      <div class="flex flex-col items-center gap-0.5">
        <label for="sfuso">Sfuso</label>
        <input 
          id="sfuso" 
          v-model="item.is_bulk" 
          type="checkbox" 
          class="toggle"
        />
      </div>
    
      <!-- Quantity Number Input -->
      <input 
        v-model.number="item.holder_quantity" 
        type="number" 
        class="input input-bordered w-12"
        :class="guidanceClass('holder_quantity')"
        placeholder="Q.tà"
        min="1"
        :disabled="item.is_bulk"
      />
  
      <!-- Holder Select/Option -->
      <div>
        <select
          v-model="item.holder_id" 
          id="holder" 
          class="select select-bordered w-36"
          :class="guidanceClass('holder_id')"
          :disabled="item.is_bulk"
        >
          <option disabled value="">Seleziona un contenitore</option>
          <option v-for="holder in holders" :key="holder.id" :value="holder.id">
            {{ holder.name }}
          </option>
        </select>

        <!-- Dimensioni custom per holder is_custom -->
        <div v-if="!item.is_bulk && customSelectedHolder" class="flex gap-2 items-end">
          <div class="form-control">
            <label class="label"><span class="label-text">Largh. (cm)</span></label>
            <input v-model.number="item.custom_l_cm" type="number" min="0.01" step="0.01" class="input input-bordered w-28" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">Prof. (cm)</span></label>
            <input v-model.number="item.custom_w_cm" type="number" min="0.01" step="0.01" class="input input-bordered w-28" />
          </div>
          <div class="form-control">
            <label class="label"><span class="label-text">Altezza (cm)</span></label>
            <input v-model.number="item.custom_h_cm" type="number" min="0.01" step="0.01" class="input input-bordered w-28" />
          </div>
        </div>
      </div>

      <!-- Descrizione TEXT Input -->
      <input 
        v-model="item.description" 
        type="text" 
        class="input input-bordered flex-1 min-w-36"
        :class="guidanceClass('description')"
        placeholder="Descrizione"
      />
  
      <!-- Weight Number Input -->
      <input 
        v-model.number="item.weight_declared" 
        type="number" 
        step="1"
        class="input input-bordered w-20"
        :class="guidanceClass('weight_declared')"
        placeholder="Peso [Kg]"
      />
  
      <!-- MAGAZZINO Select/Option -->
      <select v-model="item.warehouse_id" id="warehouse" class="select select-bordered w-36" :class="guidanceClass('warehouse_id')">
        <option value="" disabled>Magazzino</option>
        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
          {{ warehouse.name }}
        </option>
      </select>

      <input 
        v-model="item.adr_hp" 
        :disabled="!is_selected_cer_dangerous"
        type="text" 
        class="input input-bordered flex w-20"
        :class="guidanceClass('adr_hp')"
        placeholder="HP"
      />
  
      <!-- ADR TOGGLE Input -->
      <div class="flex flex-col items-center gap-0.5">
        <label for="adr">ADR</label>
        <input 
          v-model="item.adr" 
          id="adr" 
          type="checkbox" 
          class="toggle" 
          @change="toggleAdrFields(index, item.adr)" 
        />
      </div>
  
      <button type="button" @click="$emit('remove')" class="btn btn-error btn-circle">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
    </div>

    <!-- Riga ADR (mantengo il tuo DOM toggle) -->
    <div :id="'adr-fields-' + index" class="hidden flex flex-row justify-end items-center w-full gap-2 mb-2">
      <div class="font-medium">
        Campi specifici per ADR
        <font-awesome-icon :icon="['fas', 'arrow-right']" />
      </div>

      <input 
        v-model="item.adr_un_code" 
        type="text" 
        class="input input-bordered flex basis-32"
        :class="guidanceClass('adr_un_code')"
        placeholder="Cod. UN"
      />

      <label class="label" for="adr">ADR Totale</label>
      <input 
        v-model="item.is_adr_total" 
        id="is_adr_total" 
        type="checkbox" 
        class="toggle"
        :class="guidanceClass('adr_flags')"
        @click="checkAdrEsenzioni('is_adr_total')" 
      />

      <label class="label" for="adr">Esenzione Totale</label>
      <input 
        v-model="item.has_adr_total_exemption" 
        id="has_adr_total_exemption" 
        type="checkbox" 
        class="toggle"
        :class="guidanceClass('adr_flags')"
        @click="checkAdrEsenzioni('has_adr_total_exemption')" 
      />

      <label class="label" for="adr">Esenzione Parziale</label>
      <input 
        v-model="item.has_adr_partial_exemption" 
        id="has_adr_partial_exemption" 
        type="checkbox" 
        class="toggle"
        :class="guidanceClass('adr_flags')"
        @click="checkAdrEsenzioni('has_adr_partial_exemption')" 
      />
    </div>

    <!-- Hint quando sfuso -->
    <div v-if="item.is_bulk" class="text-xs opacity-80">
      Modalità <strong>sfuso</strong> attiva: nessun contenitore attivo e quantità contenitori impostata a 0.
    </div>
  </div>

  <hr class="my-3"/>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";

/** SSOT: il parent passa v-model:item */
const item = defineModel('item')

const props = defineProps({ 
  index: Number,
  items: Array,
  cerList: Array, 
  holders: Array,
  warehouses: Array,
});

const emit = defineEmits(['remove']);

const is_selected_cer_dangerous = ref(false);
const isEditingCer = ref(!item.value?.cer_code_id);
const lastCerCodeBeforeEdit = ref(item.value?.cer_code_id ?? null);

const selectedCerCode = computed(() => {
  if (!item.value?.cer_code_id) {
    return null;
  }
  return props.cerList.find((c) => Number(c.id) === Number(item.value.cer_code_id)) ?? null;
});

const isBlank = (value) => value === null || value === undefined || `${value}`.trim() === '';
const isTruthy = (value) => value === true || value === 1 || value === '1';
const isBulkSelected = computed(() => isTruthy(item.value?.is_bulk));
const adrEnabled = computed(() => isTruthy(item.value?.adr));
const requiresHp = computed(() => Boolean(selectedCerCode.value?.is_dangerous));
const hasAdrFlag = computed(() =>
  isTruthy(item.value?.is_adr_total)
  || isTruthy(item.value?.has_adr_total_exemption)
  || isTruthy(item.value?.has_adr_partial_exemption)
);

const guidanceNextField = computed(() => {
  if (isBlank(item.value?.cer_code_id)) return 'cer_code_id';

  if (!isBulkSelected.value) {
    if (isBlank(item.value?.holder_quantity) || Number(item.value?.holder_quantity) <= 0) return 'holder_quantity';
    if (isBlank(item.value?.holder_id)) return 'holder_id';
  }

  if (isBlank(item.value?.description)) return 'description';
  if (isBlank(item.value?.weight_declared)) return 'weight_declared';
  if (isBlank(item.value?.warehouse_id)) return 'warehouse_id';

  if (requiresHp.value && isBlank(item.value?.adr_hp)) return 'adr_hp';

  if (adrEnabled.value) {
    if (isBlank(item.value?.adr_un_code)) return 'adr_un_code';
    if (!hasAdrFlag.value) return 'adr_flags';
  }

  return null;
});

const guidanceClass = (fieldKey) => (
  guidanceNextField.value === fieldKey ? 'ring-2 ring-warning ring-offset-1' : ''
);

// === Imballo NON standard (holder is_custom) ===
const customSelectedHolder = computed(() => {
  return props.holders.find(h => h.id === item.value.holder_id)?.is_custom
})

watch(() => item.value.holder_id, () => {
  if (!customSelectedHolder.value) {
    item.value.custom_l_cm = item.value.custom_w_cm = item.value.custom_h_cm = null;
  }
});

// === Sfuso: normalizzazione ===
if (item.value.is_bulk === undefined) {
  item.value.is_bulk = false;
}

watch(() => item.value.is_bulk, (isBulk) => {
  if (isBulk) {
    item.value.holder_id = '';
    item.value.holder_quantity = 0;
    // piallo anche le dimensioni custom per sicurezza
    item.value.custom_l_cm = item.value.custom_w_cm = item.value.custom_h_cm = null;
  } else {
    if (!item.value.holder_quantity || item.value.holder_quantity < 1) {
      item.value.holder_quantity = 1;
    }
  }
}, { immediate: true });

// === CER pericoloso / stile ===
const getCerStyle = (cerId) => {
  if (!cerId){
    is_selected_cer_dangerous.value = false;
    return "";
  } 
  const cer = props.cerList.find((c) => Number(c.id) === Number(cerId));
  is_selected_cer_dangerous.value = Boolean(cer?.is_dangerous);
  return Number(cer?.is_dangerous) === 1 ? "cer-selected-dangerous" : "cer-selected-normal";
};

watch(is_selected_cer_dangerous, (newVal) => {
  if (!newVal) {
    item.value.adr_hp = '';
  }
});

watch(
  () => item.value.adr,
  (isAdrEnabled) => {
    const enabled = isTruthy(isAdrEnabled);
    toggleAdrFields(props.index, enabled);

    if (enabled) {
      return;
    }

    item.value.adr_un_code = null;
    item.value.is_adr_total = false;
    item.value.has_adr_total_exemption = false;
    item.value.has_adr_partial_exemption = false;
  },
  { immediate: true }
);

function startCerEdit() {
  lastCerCodeBeforeEdit.value = item.value.cer_code_id ?? null;
  isEditingCer.value = true;
}

function confirmCerEdit() {
  if (!item.value.cer_code_id) {
    return;
  }
  if (!labelMatchesCer(item.value.cer_code_id, item.value.order_item_group_label)) {
    item.value.order_item_group_id = null;
    createNewGroup(item.value.cer_code_id);
  }
  isEditingCer.value = false;
}

function cancelCerEdit() {
  item.value.cer_code_id = lastCerCodeBeforeEdit.value;
  isEditingCer.value = false;
}

function normalizeToken(it) {
  if (it?.order_item_group_id) {
    return `id:${it.order_item_group_id}`;
  }
  if (it?.order_item_group_label) {
    return `label:${it.order_item_group_label}`;
  }
  return null;
}

function groupsByCer(cerId, excludeCurrent = false) {
  const map = new Map();
  const currentToken = normalizeToken(item.value);

  (props.items || [])
    .filter((it) => Number(it?.cer_code_id) === Number(cerId))
    .forEach((it) => {
      const token = normalizeToken(it);
      if (!token) {
        return;
      }
      if (excludeCurrent && token === currentToken) {
        return;
      }
      if (!map.has(token)) {
        map.set(token, {
          token,
          id: it.order_item_group_id ?? null,
          label: it.order_item_group_label ?? `Gruppo ${map.size + 1}`,
        });
      }
    });

  return Array.from(map.values());
}

function getCerCodeById(cerId) {
  return props.cerList.find((c) => Number(c.id) === Number(cerId))?.code ?? String(cerId);
}

function labelMatchesCer(cerId, label) {
  if (!cerId || !label) {
    return false;
  }
  const cerCode = getCerCodeById(cerId);
  return String(label).startsWith(`${cerCode}.`);
}

function buildNextGroupLabel(cerId) {
  const cerCode = getCerCodeById(cerId);
  const labels = groupsByCer(cerId).map((g) => g.label);
  let index = 1;
  labels.forEach((label) => {
    const match = String(label).match(new RegExp(`^${cerCode.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\.(\\d+)$`));
    if (match) {
      index = Math.max(index, Number(match[1]) + 1);
    }
  });
  while (labels.includes(`${cerCode}.${index}`)) {
    index += 1;
  }
  return `${cerCode}.${index}`;
}

function assignGroup(group) {
  item.value.order_item_group_id = group.id ?? null;
  item.value.order_item_group_label = group.label;
}

function createNewGroup(cerId) {
  assignGroup({
    id: null,
    label: buildNextGroupLabel(cerId),
  });
}

const groupsForCurrentCer = computed(() => {
  if (!item.value?.cer_code_id) {
    return [];
  }
  return groupsByCer(item.value.cer_code_id);
});

const selectedGroupToken = computed(() => normalizeToken(item.value) || '');

function onGroupSelectionChange(token) {
  if (token === '__new__') {
    createNewGroup(item.value.cer_code_id);
    return;
  }

  const selected = groupsForCurrentCer.value.find((group) => group.token === token);
  if (selected) {
    assignGroup(selected);
  }
}

watch(
  () => item.value.cer_code_id,
  (newCerId, oldCerId) => {
    if (!newCerId) {
      item.value.order_item_group_id = null;
      item.value.order_item_group_label = null;
      isEditingCer.value = true;
      return;
    }

    if (Number(newCerId) === Number(oldCerId)) {
      return;
    }

    // CER changed: clear stale assignment from previous CER before resolving new group.
    item.value.order_item_group_id = null;
    item.value.order_item_group_label = null;

    const existingGroups = groupsByCer(newCerId, true);

    if (existingGroups.length === 0) {
      createNewGroup(newCerId);
      isEditingCer.value = false;
      return;
    }

    if (existingGroups.length === 1) {
      const choice = window.confirm(
        `Esiste gia 1 gruppo per questo CER (${existingGroups[0].label}).\nOK: usa gruppo esistente\nAnnulla: crea nuovo gruppo`
      );
      if (choice) {
        assignGroup(existingGroups[0]);
      } else {
        createNewGroup(newCerId);
      }
      isEditingCer.value = false;
      return;
    }

    const options = existingGroups
      .map((group, idx) => `${idx + 1}) ${group.label}`)
      .join('\n');
    const answer = window.prompt(
      `Esistono ${existingGroups.length} gruppi per questo CER:\n${options}\n\nInserisci il numero del gruppo da usare.\nInserisci 0 per creare un nuovo gruppo.`,
      '1'
    );

    const selectedIndex = Number(answer);
    if (Number.isInteger(selectedIndex) && selectedIndex >= 1 && selectedIndex <= existingGroups.length) {
      assignGroup(existingGroups[selectedIndex - 1]);
      isEditingCer.value = false;
      return;
    }

    createNewGroup(newCerId);
    isEditingCer.value = false;
  }
);

watch(
  () => [item.value.cer_code_id, item.value.order_item_group_id, item.value.order_item_group_label],
  ([cerId, groupId, groupLabel]) => {
    if (!cerId) {
      return;
    }
    if (groupId || groupLabel) {
      if (groupLabel && !labelMatchesCer(cerId, groupLabel)) {
        item.value.order_item_group_id = null;
        createNewGroup(cerId);
      }
      return;
    }
    createNewGroup(cerId);
  },
  { immediate: true }
);

// === Mantengo la tua gestione DOM per la riga ADR ===
function toggleAdrFields(index, isAdrEnabled) {
  const adrFields = document.getElementById(`adr-fields-${index}`);
  if (adrFields) {
    adrFields.classList.toggle('hidden', !isAdrEnabled);
  }
}

const checkAdrEsenzioni = (clickedElement) => {
  item.value.is_adr_total = false;
  item.value.has_adr_total_exemption = false;
  item.value.has_adr_partial_exemption = false;
  switch(clickedElement){
    case 'is_adr_total':
      item.value.is_adr_total = true;
      break;
    case 'has_adr_total_exemption':
      item.value.has_adr_total_exemption = true;
      break;
    case 'has_adr_partial_exemption':
      item.value.has_adr_partial_exemption = true;
      break;
  }
}
</script>

<style scoped>
.cer-list-dangerous {
  color: red;
  font-weight: bold;
}

.cer-selected-dangerous {
  background-color: #ffe6e6; /* Light red background for dangerous items */
}
</style>
