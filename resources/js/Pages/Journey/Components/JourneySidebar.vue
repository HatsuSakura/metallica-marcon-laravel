<script setup>
import { computed } from 'vue'
import VueDatePicker from '@vuepic/vue-datepicker'
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
})

const emit = defineEmits(['update:manualStopOrder', 'dragging', 'open-stops-manager'])

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
          id="driver_id"
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
        <VueDatePicker
          v-model="form.dt_start"
          locale="it"
          format="dd/MM/yyyy HH:mm"
          required
          placeholder="Data Partenza"
          :range=false
          time-picker-inline
          auto-apply
          minutes-increment="5"
          minutes-grid-increment="5"
          closeOnScroll="false"
          @closed="manageDate"
        ></VueDatePicker>
        <div class="input-error" v-if="form.errors.dt_start">
          {{ form.errors.dt_start }}
        </div>
      </div>

      <div class="flex flex-row justify-between items-center gap-2 mb-1">
        <font-awesome-icon :icon="['fas', 'person-walking-arrow-loop-left']" class="text-xl"/>
        <VueDatePicker
          v-model="form.dt_end"
          locale="it"
          format="dd/MM/yyyy HH:mm"
          required
          placeholder="Data Ritorno"
          :range=false
          time-picker-inline
          auto-apply
          minutes-increment="5"
          minutes-grid-increment="5"
          closeOnScroll="false"
        ></VueDatePicker>
        <div class="input-error" v-if="form.errors.dt_end">
          {{ form.errors.dt_end }}
        </div>
      </div>
    </Box>

    <div class="sticky top-24 shadow-md px-4 pb-4 mt-4 rounded-md flex flex-col gap-4 w-full">
      <!-- TOTALE VIAGGIO -->
      <div>
        <div class="w-full">
          <div class="font-semibold flex flex-row justify-start items-center">
            Totale Viaggio &nbsp;
            <font-awesome-icon :icon="['fas', 'route']" class="text-xl"/>
          </div>
        </div>

        <div class="mt-3 hidden md:grid grid-cols-[36px_repeat(4,minmax(0,1fr))] text-xs text-gray-500">
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

          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Casse</div>
            <div class="text-xl font-medium">{{ spaziCasseMotrice + spaziCasseRimorchio }}</div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Bancali</div>
            <div class="text-xl font-medium">{{ spaziBancaleMotrice + spaziBancaleRimorchio }}</div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Spazi</div>
            <div class="text-xl font-medium">{{ spaziBancaleMotrice + spaziBancaleRimorchio }}</div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Peso</div>
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

          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Casse</div>
            <div class="text-xl font-medium">
              <span :class="(ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento) < 0 ? 'text-error' : 'text-success'">
                {{ (spaziCasseMotrice + spaziCasseRimorchio) - (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Bancali</div>
            <div class="text-xl font-medium">
              <span :class="(ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) < 0 ? 'text-error' : 'text-success'">
                {{ (spaziBancaleMotrice + spaziBancaleRimorchio) - (ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Spazi</div>
            <div class="text-xl font-medium">
              <span :class="(spaziBancaleMotrice + spaziBancaleRimorchio) - (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) < 0 ? 'text-error' : 'text-success'">
                {{ (spaziBancaleMotrice + spaziBancaleRimorchio ) - (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Peso</div>
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

          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Casse</div>
            <div class="text-xl font-medium">
              {{ ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento }}
            </div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Bancali</div>
            <div class="text-xl font-medium">
              {{ ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento }}
            </div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Spazi</div>
            <div class="text-xl font-medium">
              <span :class="(ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) >  (spaziBancaleMotrice + spaziBancaleRimorchio + spaziCasseMotrice + spaziCasseRimorchio) ? 'text-error' : 'text-success'">
                {{ (ordiniBancaleMotrice + ordiniBancaleRimorchio + ordiniBancaleRiempimento + ordiniCasseMotrice + ordiniCasseRimorchio + ordiniCasseRiempimento) }}
              </span>
            </div>
          </div>
          <div class="rounded-md border border-gray-500/60 p-2 text-center">
            <div class="text-xs text-gray-500 md:hidden">Peso</div>
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
            <button
              type="button"
              class="btn btn-primary btn-circle btn-sm"
              @click.prevent="clearViewMode()"
            >
              <font-awesome-icon :icon="['fas', 'xmark']" class="text-lg"/>
            </button>
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
          Nssun ordine selezionato
        </EmptyState>
      </div>

      <!-- PREVIEW STOPS -->
      <StopPreviewList
        v-model:stopOrder="stopOrderModel"
        :stops-by-key="stopsByKey"
        @dragging="emit('dragging', $event)"
        @open-manager="emit('open-stops-manager')"
      />

      <div class="mb-2">
        <button
          type="submit"
          class="btn btn-primary"
        >
          <font-awesome-icon :icon="['fas', 'floppy-disk']" class="text-xl"/>
          Crea Viaggio
        </button>
      </div>
    </div>
  </div>
</template>
