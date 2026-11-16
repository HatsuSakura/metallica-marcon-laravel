<script setup>
import AppAction from '@/Components/UI/AppAction.vue'

import { computed } from 'vue'
import AppDateTimePicker from '@/Components/UI/AppDateTimePicker.vue'
import Box from '@/Components/UI/Box.vue'
import EmptyState from '@/Components/UI/EmptyState.vue'
import OrderInfo from './OrderInfo.vue'
import StopPreviewList from './StopPreviewList.vue'

const props = defineProps({
  form: Object,
  drivers: Array,
  viewMode: String,
  clickedElement: Object,
  manageDate: Function,
  clearViewMode: Function,
  spaziCasseMotrice: [Number, String],
  spaziCasseRimorchio: [Number, String],
  spaziBancaleMotrice: [Number, String],
  spaziBancaleRimorchio: [Number, String],
  capacitaCaricoMotrice: [Number, String],
  capacitaCaricoRimorchio: [Number, String],
  ordiniCasseMotrice: [Number, String],
  ordiniCasseRimorchio: [Number, String],
  ordiniCasseRiempimento: [Number, String],
  ordiniBancaleMotrice: [Number, String],
  ordiniBancaleRimorchio: [Number, String],
  ordiniBancaleRiempimento: [Number, String],
  ordiniCaricoMotrice: [Number, String],
  ordiniCaricoRimorchio: [Number, String],
  ordiniCaricoRiempimento: [Number, String],
  manualStopOrder: Array,
  stopsByKey: Object,
  submitDisabled: Boolean,
  showSaveExit: Boolean,
  submitLabel: {
    type: String,
    default: 'Crea Viaggio',
  },
})

const emit = defineEmits(['update:manualStopOrder', 'dragging', 'open-stops-manager', 'save-exit'])

const stopOrderModel = computed({
  get: () => props.manualStopOrder,
  set: (value) => emit('update:manualStopOrder', value),
})
</script>

<template>
  <div>
    <Box>
      <div class="flex flex-row justify-between items-center gap-2 mb-1">
        <font-awesome-icon :icon="['fas', 'id-card']" class="text-xl"/>
        <select
          v-model="form.driver_id"
          required
          id="driver_id" aria-label="Autista" name="driver_id"
          class="select select-bordered w-full max-w-xs"
          @change=""
        >
          <option disabled value="">Seleziona l'autista</option>
          <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
            {{ driver.name }}
          </option>
        </select>
      </div>

      <div class="flex flex-row justify-between items-center gap-2 mb-1">
        <font-awesome-icon :icon="['fas', 'person-walking-arrow-right']" class="text-xl"/>
        <AppDateTimePicker
          v-model="form.planned_start_at" uid="planned_start_at" name="planned_start_at" :aria-labels="{input:'Partenza prevista'}"
          model-type="yyyy-MM-dd HH:mm:ss"
          locale="it"
          format="dd/MM/yyyy HH:mm"
          required
          placeholder="Data Partenza"
          :range=false
          @update:model-value="manageDate"
        ></AppDateTimePicker>
        <div class="input-error" v-if="form.errors.planned_start_at">
          {{ form.errors.planned_start_at }}
        </div>
      </div>

      <div class="flex flex-row justify-between items-center gap-2 mb-1">
        <font-awesome-icon :icon="['fas', 'person-walking-arrow-loop-left']" class="text-xl"/>
        <AppDateTimePicker
          v-model="form.planned_end_at" uid="planned_end_at" name="planned_end_at" :aria-labels="{input:'Ritorno previsto'}"
          model-type="yyyy-MM-dd HH:mm:ss"
          locale="it"
          format="dd/MM/yyyy HH:mm"
          required
          placeholder="Data Ritorno"
          :range=false
        ></AppDateTimePicker>
        <div class="input-error" v-if="form.errors.planned_end_at">
          {{ form.errors.planned_end_at }}
        </div>
      </div>

      <div class="mt-3">
        <label class="label">Note Viaggio</label>
        <textarea
          v-model="form.notes"
          class="textarea textarea-bordered w-full"
          rows="3"
          placeholder="Inserisci eventuali note operative relative al viaggio corrente"
        />
        <div class="input-error" v-if="form.errors.notes">
          {{ form.errors.notes }}
        </div>
      </div>
    </Box>

    <div class="2xl:sticky 2xl:top-24 border-t border-base-300 pt-4 mt-4 flex flex-col gap-4 w-full">
      <!-- TOTALE VIAGGIO -->
      <div>
        <div class="w-full">
          <div class="font-semibold flex flex-row justify-start items-center">
            Totale Viaggio &nbsp;
            <font-awesome-icon :icon="['fas', 'route']" class="text-xl"/>
          </div>
        </div>

        <div class="mt-3 hidden md:grid grid-cols-[36px_repeat(4,minmax(0,1fr))] text-xs text-base-content/75">
          <div></div>
          <div class="text-center">Casse</div>
          <div class="text-center">Bancali</div>
          <div class="text-center">Spazi</div>
          <div class="text-center">Peso [t]</div>
        </div>

        <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-[36px_repeat(4,minmax(0,1fr))] md:gap-1">
          <div class="hidden md:flex items-center justify-center">
            <font-awesome-icon :icon="['fas', 'truck']" class="text-xl"/>
          </div>
          <div class="md:hidden col-span-2 flex items-center gap-2 text-sm font-medium">
            <font-awesome-icon :icon="['fas', 'truck']" class="text-lg"/>
            Totale
          </div>

          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Casse</div>
            <div class="text-xl font-medium">{{ spaziCasseMotrice + spaziCasseRimorchio }}</div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Bancali</div>
            <div class="text-xl font-medium">{{ spaziBancaleMotrice + spaziBancaleRimorchio }}</div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Spazi</div>
            <div class="text-xl font-medium">{{ spaziBancaleMotrice + spaziBancaleRimorchio }}</div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Peso</div>
            <div class="text-xl font-medium">{{ (capacitaCaricoMotrice + capacitaCaricoRimorchio) / 1000 }}</div>
          </div>
        </div>

        <!-- RESIDUO CALCOLATO -->
        <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-[36px_repeat(4,minmax(0,1fr))] md:gap-1">
          <div class="hidden md:flex items-center justify-center">
            <font-awesome-icon :icon="['fas', 'calculator']" class="text-xl"/>
          </div>
          <div class="md:hidden col-span-2 flex items-center gap-2 text-sm font-medium">
            <font-awesome-icon :icon="['fas', 'calculator']" class="text-lg"/>
            Residuo
          </div>

          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Casse</div>
            <div class="text-xl font-medium">
              <span :class="(ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento) < 0 ? 'text-error' : 'text-success'">
                {{ (spaziCasseMotrice + spaziCasseRimorchio) - (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Bancali</div>
            <div class="text-xl font-medium">
              <span :class="(ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) < 0 ? 'text-error' : 'text-success'">
                {{ (spaziBancaleMotrice + spaziBancaleRimorchio) - (ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Spazi</div>
            <div class="text-xl font-medium">
              <span :class="(spaziBancaleMotrice + spaziBancaleRimorchio) - (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) < 0 ? 'text-error' : 'text-success'">
                {{ (spaziBancaleMotrice + spaziBancaleRimorchio ) - (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Peso</div>
            <div class="text-xl font-medium">
              {{ (capacitaCaricoMotrice + capacitaCaricoRimorchio - (ordiniCaricoMotrice + ordiniCaricoRimorchio + ordiniCaricoRiempimento) )  / 1000 }}
            </div>
          </div>
        </div>

        <!-- TOTALE ORDINI -->
        <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-[36px_repeat(4,minmax(0,1fr))] md:gap-1">
          <div class="hidden md:flex items-center justify-center">
            <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-xl"/>
          </div>
          <div class="md:hidden col-span-2 flex items-center gap-2 text-sm font-medium">
            <font-awesome-icon :icon="['fas', 'cart-arrow-down']" class="text-lg"/>
            Ordini
          </div>

          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Casse</div>
            <div class="text-xl font-medium">
              {{ ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento }}
            </div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Bancali</div>
            <div class="text-xl font-medium">
              {{ ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento }}
            </div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Spazi</div>
            <div class="text-xl font-medium">
              <span :class="(ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) >  (spaziBancaleMotrice + spaziBancaleRimorchio + spaziCasseMotrice + spaziCasseRimorchio) ? 'text-error' : 'text-success'">
                {{ (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-base-300 p-2 text-center">
            <div class="text-xs text-base-content/75 md:hidden">Peso</div>
            <div class="text-xl font-medium">
              {{ (ordiniCaricoMotrice + ordiniCaricoRimorchio + ordiniCaricoRiempimento) / 1000 }}
            </div>
          </div>
        </div>
      </div>

      <!-- INFO ORDINE -->
      <div class="">
        <div class="flex flex-row justify-between items-center mb-2">
          <div>
            <h3 class="font-semibold flex flex-row justify-start items-center gap-2">
              Info Ordine &nbsp;
              <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="text-xl" />
            </h3>
          </div>
          <div v-if="viewMode != 'empty'">
            <AppAction
              type="button"

              @click.prevent="clearViewMode()"
             variant="neutral" shape="circle" aria-label="Chiudi dettaglio">
              <font-awesome-icon :icon="['fas', 'xmark']" class="text-lg"/>
            </AppAction>
          </div>
        </div>

        <div v-if="viewMode === 'info'">
          <OrderInfo
            :order="clickedElement"
          />
        </div>
        <Box v-else-if="viewMode === 'map'">
          MAP
          {{ clickedElement }}
        </Box>
        <Box v-else-if="viewMode === 'edit'">
          Edit andrà aperto in altra finestra
          {{ clickedElement }}
        </Box>
        <EmptyState v-else>
          Nessun ordine selezionato
        </EmptyState>
      </div>

      <!-- PREVIEW STOPS -->
      <StopPreviewList
        v-model:stopOrder="stopOrderModel"
        :stops-by-key="stopsByKey"
        @dragging="emit('dragging', $event)"
        @open-manager="emit('open-stops-manager')"
      />

      <div class="mb-2 flex flex-wrap gap-2">
        <AppAction
          type="submit"
          :disabled="submitDisabled || form.processing"

         variant="confirm">
          <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-lg" aria-hidden="true"/>
          {{ submitLabel }}
        </AppAction>
        <AppAction v-if="showSaveExit" variant="neutral" class="btn-success" :disabled="submitDisabled || form.processing" @click="emit('save-exit')">
          <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-lg" aria-hidden="true" />Salva ed esci
        </AppAction>
      </div>
    </div>
  </div>
</template>
