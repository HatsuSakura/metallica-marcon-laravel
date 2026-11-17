<template>
    <article class="order-material min-w-0 space-y-3 border-b border-base-300 py-3">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0 flex-1 basis-64 space-y-1">
                <label :for="`order-item-${index}-cer`" class="label">Materiale / CER</label>
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <template v-if="isEditingCer || !item.cer_code_id">
                        <v-select v-model="item.cer_code_id" :input-id="`order-item-${index}-cer`" :data-form-field="`items.${index}.cer_code_id`" :options="props.cerList" label="code" :reduce="cer => cer.id" :filterable="true" :searchable="true" placeholder="Seleziona CER" class="custom-style-chooser min-w-0 flex-1 basis-48" :class="[getCerStyle(item.cer_code_id),guidanceClass('cer_code_id')]">
                            <template #option="{code,description,is_dangerous}"><span :class="Number(is_dangerous) === 1 ? 'text-error font-semibold' : ''">{{ code }}</span><p class="whitespace-normal text-sm">{{ description }}</p></template>
                        </v-select>
                        <AppAction variant="confirm" :disabled="!item.cer_code_id" @click="confirmCerEdit">Conferma CER</AppAction>
                        <AppAction v-if="lastCerCodeBeforeEdit" variant="cancel" @click="cancelCerEdit">Annulla</AppAction>
                    </template>
                    <template v-else><CerCodeBadge :code="selectedCerCode?.code" :dangerous="Number(selectedCerCode?.is_dangerous) === 1" :unassigned="!selectedCerCode?.code" /><AppAction variant="function" shape="circle" aria-label="Modifica CER" @click="startCerEdit"><font-awesome-icon :icon="['fas','pencil']" aria-hidden="true" /></AppAction></template>
                </div>
            </div>
            <AppAction variant="remove" shape="circle" :aria-label="`Rimuovi materiale ${selectedCerCode?.code || index+1}`" @click="$emit('remove')"><font-awesome-icon :icon="['fas','trash-can']" aria-hidden="true" /></AppAction>
        </div>
        <div class="material-main">
            <label v-if="item.cer_code_id" class="form-control min-w-0"><span class="label">Gruppo materiale</span><select :value="selectedGroupToken" @change="onGroupSelectionChange($event.target.value)" class="select select-bordered"><option disabled value="">Gruppo</option><option v-for="group in groupsForCurrentCer" :key="group.token" :value="group.token">{{ group.label }}</option><option value="__new__">+ Nuovo gruppo</option></select></label>
            <label class="flex min-h-12 items-center gap-2 self-end text-sm"><input v-model="item.is_bulk" :name="`items.${index}.is_bulk`" type="checkbox" class="toggle toggle-primary" />Sfuso</label>
            <label class="form-control min-w-0"><span class="label">Quantità contenitori</span><input v-model.number="item.holder_quantity" :id="`order-item-${index}-quantity`" :name="`items.${index}.holder_quantity`" type="number" class="input input-bordered" :class="guidanceClass('holder_quantity')" min="1" :disabled="item.is_bulk" /></label>
            <label class="form-control min-w-0"><span class="label">Contenitore</span><select v-model="item.holder_id" :id="`order-item-${index}-holder`" :name="`items.${index}.holder_id`" class="select select-bordered" :class="guidanceClass('holder_id')" :disabled="item.is_bulk"><option disabled value="">Seleziona contenitore</option><option v-for="holder in holders" :key="holder.id" :value="holder.id">{{ holder.name }}</option></select></label>
            <label class="form-control min-w-0"><span class="label">Peso stimato (kg)</span><input v-model.number="item.weight_declared" :id="`order-item-${index}-weight`" :name="`items.${index}.weight_declared`" type="number" step="1" class="input input-bordered" :class="guidanceClass('weight_declared')" /></label>
            <label class="form-control min-w-0"><span class="label">Magazzino</span><select v-model="item.warehouse_id" :id="`order-item-${index}-warehouse`" :name="`items.${index}.warehouse_id`" class="select select-bordered" :class="guidanceClass('warehouse_id')"><option value="" disabled>Seleziona magazzino</option><option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option></select></label>
        </div>
        <div class="material-details">
            <label class="form-control min-w-0 material-description"><span class="label">Descrizione materiale</span><input v-model="item.description" :id="`order-item-${index}-description`" :name="`items.${index}.description`" type="text" class="input input-bordered" :class="guidanceClass('description')" /></label>
            <label class="form-control min-w-0 material-hp"><span class="label">HP</span><input v-model="item.adr_hp" :id="`order-item-${index}-hp`" :name="`items.${index}.adr_hp`" :disabled="!requiresHp" type="text" class="input input-bordered" :class="guidanceClass('adr_hp')" /></label>
            <label class="material-adr flex min-h-12 items-center gap-2 self-end text-sm"><span>ADR</span><input v-model="item.adr" :name="`items.${index}.adr`" type="checkbox" class="toggle toggle-primary" /></label>
        </div>
        <div v-if="!item.is_bulk && customSelectedHolder" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <label v-for="dimension in dimensions" :key="dimension.key" class="form-control min-w-0"><span class="label">{{ dimension.label }} (cm)</span><input v-model.number="item[dimension.key]" type="number" min="0.01" step="0.01" class="input input-bordered" /></label>
        </div>
        <div v-show="isTruthy(item.adr)" class="grid grid-cols-1 gap-3 rounded-xl bg-base-200 p-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="form-control min-w-0"><span class="label">Codice UN</span><input v-model="item.adr_un_code" :id="`order-item-${index}-un`" :name="`items.${index}.adr_un_code`" type="text" class="input input-bordered" :class="guidanceClass('adr_un_code')" /></label>
            <label v-for="flag in adrFlags" :key="flag.key" class="flex min-h-12 items-center gap-2 text-sm"><input v-model="item[flag.key]" :name="`items.${index}.${flag.key}`" type="checkbox" class="toggle toggle-primary" :class="guidanceClass('adr_flags')" @click="checkAdrEsenzioni(flag.key)" />{{ flag.label }}</label>
        </div>
        <p v-if="item.is_bulk" class="text-sm">Materiale sfuso: contenitore e quantità non richiesti.</p>
    </article>
</template>

<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
const dimensions = [{key:'custom_l_cm',label:'Larghezza'},{key:'custom_w_cm',label:'Profondità'},{key:'custom_h_cm',label:'Altezza'}]
const adrFlags = [{key:'is_adr_total',label:'ADR totale'},{key:'has_adr_total_exemption',label:'Esenzione totale'},{key:'has_adr_partial_exemption',label:'Esenzione parziale'}]

import CerCodeBadge from '@/Components/UI/CerCodeBadge.vue';
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
const requiresHp = computed(() => isTruthy(selectedCerCode.value?.is_dangerous));
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
    return "";
  }
  const cer = props.cerList.find((c) => Number(c.id) === Number(cerId));
  return Number(cer?.is_dangerous) === 1 ? "cer-selected-dangerous" : "cer-selected-normal";
};

watch(requiresHp, (newVal) => {
  if (!newVal) {
    item.value.adr_hp = '';
  }
});

watch(
  () => item.value.adr,
  (isAdrEnabled) => {
    const enabled = isTruthy(isAdrEnabled);

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
  color: oklch(var(--er));
  font-weight: bold;
}

.cer-selected-dangerous {
  background-color: oklch(var(--er) / .1);
}
</style>

<style scoped>
.order-material { container-type: inline-size; }
.material-main, .material-details { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; }
.material-adr { justify-self: end; }
@container (min-width: 36rem) {
    .material-main { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .material-details { grid-template-columns: minmax(0, 1fr) minmax(8rem, .4fr) auto; align-items: end; }
    .material-adr { margin-left: 1rem; }
}
@container (min-width: 64rem) {
    .material-main { grid-template-columns: minmax(0, 1.2fr) auto minmax(0, .9fr) minmax(0, 1.6fr) minmax(0, .9fr) minmax(0, 1.3fr); align-items: end; }
    .material-details { grid-template-columns: minmax(0, 3fr) minmax(10rem, 1fr) minmax(8rem, .75fr); }
}
</style>
