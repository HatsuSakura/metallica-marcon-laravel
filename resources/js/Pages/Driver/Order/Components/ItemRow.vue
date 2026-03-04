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
                  :class="getCerStyle(item.cer_code_id)"
                >
                  <template #option="{ code, description, is_dangerous }">
                    <span :class="is_dangerous === 1 ? 'cer-list-dangerous' : 'cer-list-normal'">{{ code }}</span>
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

            <div v-if="item.cer_code_id">
              <select :value="selectedGroupToken" @change="onGroupSelectionChange($event.target.value)" class="select select-bordered w-32">
                <option disabled value="">Gruppo</option>
                <option v-for="group in groupsForCurrentCer" :key="group.token" :value="group.token">
                  {{ group.label }}
                </option>
                <option value="__new__">+ Nuovo</option>
              </select>
            </div>
        
            <!-- Quantity Number Input -->
            <input 
                v-model.number="item.holder_quantity" 
                type="number" 
                class="input input-bordered w-12"
                placeholder="Q.tà"
                min="1"
            />
        
            <!-- Holder Select/Option -->
            <select v-model="item.holder_id" id="holder" class="select select-bordered w-36">
                <option disabled value="">Seleziona un contenitore</option>
                <option v-for="holder in holders" :key="holder.id" :value="holder.id">
                {{ holder.name }}
                </option>
            </select>
        
            <!-- Descrizione TEXT Input -->
            <input 
                v-model="item.description" 
                type="text" 
                class="input input-bordered flex-1 min-w-36"
                placeholder="Descrizione"
            />
        
            <!-- Weight Number Input -->
            <input 
                v-model.number="item.weight_declared" 
                type="number" 
                step="1"
                class="input input-bordered w-20"
                placeholder="Peso [Kg]"
            />
        
            <!-- MAGAZZINO Select/Option -->
            <select v-model="item.warehouse_id" id="warehouse" class="select select-bordered w-36">
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
                @click="toggleAdrFields(index, item.adr)" 
                />
            </div>
        
            <button type="button" @click="$emit('remove')" class="btn btn-error btn-circle">
                <font-awesome-icon :icon="['fas', 'xmark']" />
            </button>
        </div>
  
        <!-- Riga ADR -->
        <div :id="'adr-fields-' + index" class="hidden flex flex-row justify-end items-center w-full gap-2 mb-2">
            <div class="font-medium">
            Campi specifici per ADR
            <font-awesome-icon :icon="['fas', 'arrow-right']" />
            </div>

            <input 
            v-model="item.adr_un_code" 
            type="text" 
            class="input input-bordered flex basis-32"
            placeholder="Cod. UN"
            />

            <label class="label" for="adr">ADR Totale</label>
            <input 
            v-model="item.is_adr_total" 
            id="is_adr_total" 
            type="checkbox" 
            class="toggle" 
            @click="checkAdrEsenzioni('is_adr_total')" 
            />

            <label class="label" for="adr">Esenzione Totale</label>
            <input 
            v-model="item.has_adr_total_exemption" 
            id="has_adr_total_exemption" 
            type="checkbox" 
            class="toggle" 
            @click="checkAdrEsenzioni('has_adr_total_exemption')" 
            />

            <label class="label" for="adr">Esenzione Parziale</label>
            <input 
            v-model="item.has_adr_partial_exemption" 
            id="has_adr_partial_exemption" 
            type="checkbox" 
            class="toggle" 
            @click="checkAdrEsenzioni('has_adr_partial_exemption')" 
            />


        </div>
    </div>
  </template>
  
  <script setup>
  import { computed, ref, watch } from "vue";
  import vSelect from "vue-select";
  import "vue-select/dist/vue-select.css";
  
  const props = defineProps({ 
    item: Object,
    index: Number,
    items: Array,
    cerList: Array, 
    holders: Array,
    warehouses: Array,
  });
  
  const emit = defineEmits(['remove']);
  
  const is_selected_cer_dangerous = ref(false);
  const isEditingCer = ref(!props.item?.cer_code_id);
  const lastCerCodeBeforeEdit = ref(props.item?.cer_code_id ?? null);

  const selectedCerCode = computed(() => {
    if (!props.item?.cer_code_id) {
      return null;
    }
    return props.cerList.find((c) => Number(c.id) === Number(props.item.cer_code_id)) ?? null;
  });

  const getCerStyle = (cerId) => {
    if (!cerId){
      is_selected_cer_dangerous.value = false;
      return ""; // Return an empty class for undefined or null values
    } 
    const cer = props.cerList.find((c) => c.id === cerId);
    is_selected_cer_dangerous.value = Boolean(cer?.is_dangerous);
    return cer?.is_dangerous === 1 ? "cer-selected-dangerous" : "cer-selected-normal";
  };

  watch(is_selected_cer_dangerous, (newVal) => {
      if (!newVal) {
        props.item.adr_hp = '';
      }
  });

  function startCerEdit() {
    lastCerCodeBeforeEdit.value = props.item.cer_code_id ?? null;
    isEditingCer.value = true;
  }

  function confirmCerEdit() {
    if (!props.item.cer_code_id) {
      return;
    }
    if (!labelMatchesCer(props.item.cer_code_id, props.item.order_item_group_label)) {
      props.item.order_item_group_id = null;
      createNewGroup(props.item.cer_code_id);
    }
    isEditingCer.value = false;
  }

  function cancelCerEdit() {
    props.item.cer_code_id = lastCerCodeBeforeEdit.value;
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
    const currentToken = normalizeToken(props.item);

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
    props.item.order_item_group_id = group.id ?? null;
    props.item.order_item_group_label = group.label;
  }

  function createNewGroup(cerId) {
    assignGroup({
      id: null,
      label: buildNextGroupLabel(cerId),
    });
  }

  const groupsForCurrentCer = computed(() => {
    if (!props.item?.cer_code_id) {
      return [];
    }
    return groupsByCer(props.item.cer_code_id);
  });

  const selectedGroupToken = computed(() => normalizeToken(props.item) || '');

  function onGroupSelectionChange(token) {
    if (token === '__new__') {
      createNewGroup(props.item.cer_code_id);
      return;
    }

    const selected = groupsForCurrentCer.value.find((group) => group.token === token);
    if (selected) {
      assignGroup(selected);
    }
  }

  watch(
    () => props.item.cer_code_id,
    (newCerId, oldCerId) => {
      if (!newCerId) {
        props.item.order_item_group_id = null;
        props.item.order_item_group_label = null;
        isEditingCer.value = true;
        return;
      }

      if (Number(newCerId) === Number(oldCerId)) {
        return;
      }

      // CER changed: clear stale assignment from previous CER before resolving new group.
      props.item.order_item_group_id = null;
      props.item.order_item_group_label = null;

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
    () => [props.item.cer_code_id, props.item.order_item_group_id, props.item.order_item_group_label],
    ([cerId, groupId, groupLabel]) => {
      if (!cerId) {
        return;
      }
      if (groupId || groupLabel) {
        if (groupLabel && !labelMatchesCer(cerId, groupLabel)) {
          props.item.order_item_group_id = null;
          createNewGroup(cerId);
        }
        return;
      }
      createNewGroup(cerId);
    },
    { immediate: true }
  );
  
  const toggleAdrFields = (index, isAdrEnabled) => {
    const adrFields = document.getElementById(`adr-fields-${index}`);
    if (adrFields) {
      adrFields.classList.toggle('hidden', !isAdrEnabled);
    }
  };

  const checkAdrEsenzioni = (clickedElement) => {
    props.item.is_adr_total = false;
    props.item.has_adr_total_exemption = false;
    props.item.has_adr_partial_exemption = false;
    switch(clickedElement){
      case 'is_adr_total':
        props.item.is_adr_total = true;
        break;
      case 'has_adr_total_exemption':
        props.item.has_adr_total_exemption = true;
        break;
      case 'has_adr_partial_exemption':
        props.item.has_adr_partial_exemption = true;
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
  

