<script setup>
import Box from '@/Components/UI/Box.vue'

defineProps({
  vehicles: Array,
  trailers: Array,
  cargos: Array,
  form: Object,
  trailerEnabled: Boolean,
  setPreferredTrailerAndCargo: Function,
  checkCargo: Function,
  spaziCasseMotrice: [Number, String],
  spaziBancaleMotrice: [Number, String],
  capacitaCaricoMotrice: [Number, String],
  ordiniCasseMotrice: [Number, String],
  ordiniBancaleMotrice: [Number, String],
  ordiniCaricoMotrice: [Number, String],
  spaziCasseRimorchio: [Number, String],
  spaziBancaleRimorchio: [Number, String],
  capacitaCaricoRimorchio: [Number, String],
  ordiniCasseRimorchio: [Number, String],
  ordiniBancaleRimorchio: [Number, String],
  ordiniCaricoRimorchio: [Number, String],
})
</script>

<template>
  <div class="flex flex-row justify-items-stretch gap-2 w-full">
    <!-- MOTRICE -->
    <Box class="flex-1">
      <div class="w-full">
        <div class="flex flex-row justify-center items-center">
          Motrice &nbsp;
          <font-awesome-icon :icon="['fas', 'truck']" class="text-4xl"/>
        </div>
      </div>

      <div class="flex flex-row items-center gap-4 mt-2 pr-2">
        <select
          v-model="form.vehicle_id"
          required
          id="vehicle_id"
          class="select select-bordered w-full max-w-xs"
          @change="setPreferredTrailerAndCargo"
        >
          <option disabled value="">Seleziona la motrice</option>
          <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
            {{ vehicle.plate }} - {{ vehicle.name }}
          </option>
        </select>

        <select
          v-model="form.cargo_for_vehicle_id"
          required
          id="cargo_for_vehicle_id"
          class="select select-bordered w-full max-w-xs"
          :disabled="!trailerEnabled"
          @change="checkCargo"
        >
          <option disabled value="">Nessun cassone</option>
          <option v-for="cargo in cargos" :key="cargo.id" :value="cargo.id">
            {{ cargo.name }}
          </option>
        </select>
      </div>

      <div class="mt-3 hidden md:grid grid-cols-[36px_repeat(4,minmax(0,1fr))] text-xs text-gray-500">
        <div></div>
        <div class="text-center">Casse</div>
        <div class="text-center">Bancali</div>
        <div class="text-center">Spazi</div>
        <div class="text-center">Peso [t]</div>
      </div>

      <!-- SPACES AND ORDERS MOTRICE -->
      <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-[36px_repeat(4,minmax(0,1fr))] md:gap-1">
        <div class="hidden md:flex items-center justify-center">
          <font-awesome-icon :icon="['fas', 'truck']" class="text-xl"/>
        </div>
        <div class="md:hidden col-span-2 flex items-center gap-2 text-sm font-medium">
          <font-awesome-icon :icon="['fas', 'truck']" class="text-lg"/>
          Capienza
        </div>

        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Casse</div>
          <div class="text-xl font-medium">{{ spaziCasseMotrice }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Bancali</div>
          <div class="text-xl font-medium">{{ spaziBancaleMotrice }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Spazi</div>
          <div class="text-xl font-medium">{{ spaziBancaleMotrice }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Peso</div>
          <div class="text-xl font-medium">{{ capacitaCaricoMotrice / 1000 }} </div>
        </div>
      </div>

      <!-- CALCOLO RESIDUI SPACES AND ORDERS MOTRICE -->
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
            <span :class="(spaziCasseMotrice - ordiniBancaleMotrice) < ordiniCasseMotrice ? 'text-error' : 'text-success'">
              {{ spaziCasseMotrice - ordiniBancaleMotrice }}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Bancali</div>
          <div class="text-xl font-medium">
            <span :class="(spaziBancaleMotrice - ordiniCasseMotrice) < ordiniBancaleMotrice ? 'text-error' : 'text-success'">
              {{ spaziBancaleMotrice - ordiniCasseMotrice}}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Spazi</div>
          <div class="text-xl font-medium">
            <span :class="(spaziBancaleMotrice - ordiniCasseMotrice - ordiniBancaleMotrice) < 0 ? 'text-error' : 'text-success'">
              {{ spaziBancaleMotrice - ordiniCasseMotrice - ordiniBancaleMotrice }}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden"></div>
          <div class="text-xl font-medium">
            {{ (capacitaCaricoMotrice - ordiniCaricoMotrice) / 1000 }} 
          </div>
        </div>
      </div>

      <!-- CARRY SPACES AND ORDERS MOTRICE -->
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
          <div class="text-xl font-medium">{{ ordiniCasseMotrice }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Bancali</div>
          <div class="text-xl font-medium">{{ ordiniBancaleMotrice }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Spazi</div>
          <div class="text-xl font-medium">
            <span :class="(spaziBancaleMotrice) < (ordiniBancaleMotrice + ordiniCasseMotrice) ? 'text-error' : 'text-success'">
              {{ ordiniBancaleMotrice + ordiniCasseMotrice}}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Peso</div>
          <div class="text-xl font-medium">{{ ordiniCaricoMotrice / 1000 }} </div>
        </div>
      </div>
    </Box>

    <!-- RIMORCHIO -->
    <Box class="flex-1">
      <div class="w-full">
        <div class="flex flex-row justify-center items-center">
          Rimorchio &nbsp;
          <font-awesome-icon :icon="['fas', 'trailer']" class="text-4xl"/>
        </div>
      </div>

      <div class="flex flex-row items-center gap-4 mt-2 pr-2">
        <select
          v-model="form.trailer_id"
          id="trailer_id"
          class="select select-bordered w-full max-w-xs"
          :disabled="!trailerEnabled"
          @change="checkCargo"
        >
          <option value="">Nessun rimorchio</option>
          <option v-for="trailer in trailers" :key="trailer.id" :value="trailer.id">
            {{ trailer.plate }} - {{ trailer.name }}
          </option>
        </select>

        <select
          v-model="form.cargo_for_trailer_id"
          :required="form.trailer_id ? true : false"
          id="cargo_for_trailer_id"
          class="select select-bordered w-full max-w-xs"
          :disabled="!trailerEnabled"
          @change="checkCargo"
        >
          <option disabled value="">Nessun cassone</option>
          <option v-for="cargo in cargos" :key="cargo.id" :value="cargo.id">
            {{ cargo.name }}
          </option>
        </select>
      </div>

      <div class="mt-3 hidden md:grid grid-cols-[36px_repeat(4,minmax(0,1fr))] text-xs text-gray-500">
        <div></div>
        <div class="text-center">Casse</div>
        <div class="text-center">Bancali</div>
        <div class="text-center">Spazi</div>
        <div class="text-center">Peso [t]</div>
      </div>

      <!-- SPACES AND ORDERS RIMORCHIO -->
      <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-[36px_repeat(4,minmax(0,1fr))] md:gap-1">
        <div class="hidden md:flex items-center justify-center">
          <font-awesome-icon :icon="['fas', 'trailer']" class="text-xl"/>
        </div>
        <div class="md:hidden col-span-2 flex items-center gap-2 text-sm font-medium">
          <font-awesome-icon :icon="['fas', 'trailer']" class="text-lg"/>
          Capienza
        </div>

        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Casse</div>
          <div class="text-xl font-medium">{{ spaziCasseRimorchio }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Bancali</div>
          <div class="text-xl font-medium">{{ spaziBancaleRimorchio }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Spazi</div>
          <div class="text-xl font-medium">{{ spaziBancaleRimorchio }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Peso</div>
          <div class="text-xl font-medium">{{ capacitaCaricoRimorchio / 1000 }} </div>
        </div>
      </div>

      <!-- CALCOLO RESIDUI SPACES AND ORDERS RIMORCHIO -->
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
            <span :class="(spaziCasseRimorchio - ordiniBancaleRimorchio) < ordiniCasseRimorchio ? 'text-error' : 'text-success'">
              {{ spaziCasseRimorchio - ordiniBancaleRimorchio }}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Bancali</div>
          <div class="text-xl font-medium">
            <span :class="(spaziBancaleRimorchio - ordiniCasseRimorchio) < ordiniBancaleRimorchio ? 'text-error' : 'text-success'">
              {{ spaziBancaleRimorchio - ordiniCasseRimorchio}}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Spazi</div>
          <div class="text-xl font-medium">
            <span :class="(spaziBancaleRimorchio - ordiniCasseRimorchio - ordiniBancaleRimorchio) < 0 ? 'text-error' : 'text-success'">
              {{ spaziBancaleRimorchio - ordiniCasseRimorchio - ordiniBancaleRimorchio }}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Peso</div>
          <div class="text-xl font-medium">
            {{ (capacitaCaricoRimorchio - ordiniCaricoRimorchio) / 1000 }} 
          </div>
        </div>
      </div>

      <!-- CARRY SPACES AND ORDERS RIMORCHIO -->
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
          <div class="text-xl font-medium">{{ ordiniCasseRimorchio }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Bancali</div>
          <div class="text-xl font-medium">{{ ordiniBancaleRimorchio }}</div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Spazi</div>
          <div class="text-xl font-medium">
            <span :class="(spaziBancaleRimorchio) < (ordiniBancaleRimorchio + ordiniCasseRimorchio) ? 'text-error' : 'text-success'">
              {{ ordiniBancaleRimorchio + ordiniCasseRimorchio}}
            </span>
          </div>
        </div>
        <div class="rounded-md border border-gray-500/60 p-2 text-center">
          <div class="text-xs text-gray-500 md:hidden">Peso</div>
          <div class="text-xl font-medium">{{ ordiniCaricoRimorchio / 1000 }} </div>
        </div>
      </div>
    </Box>
  </div>
</template>
