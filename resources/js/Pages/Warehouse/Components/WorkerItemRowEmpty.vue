<template>
<Box class="bg-neutral">
    <div class="flex flex-row gap-4 w-full items-stretch justify-start">

        <!-- ID RIGA -->
        <div class="flex flex-row items-center w-16">
        <div class="badge badge-outline badge-info">
            {{ index +1 }}
        </div>
        </div>

        <!-- BLOCCO Quantità e tipologia -->
        <div class="flex flex-col justify-evenly w-1/3">

            <div class="flex flex-row gap-4">
                <div class="badge badge-primary badge-lg">
                    {{item.holder_quantity}} x {{ item.holder.name }}     
                </div>

                <div class="badge badge-lg" :class="item.cer_code.is_dangerous ? 'badge-error' : 'badge-primary'">
                {{ item.cer_code.code }}
                </div>
            </div>

            <div :class="item.cer_code_id.is_dangerous ? 'text-error' : 'text-info'">
                {{ item.cer_code.description }}
            </div>

            <div class="">
                {{ item.description }}
            </div>

            <div class="flex flex-row justify-start gap-4">

                <div>
                <span v-if="item.adr_hp" class="badge badge-error badge-lg">HP: {{ item.adr_hp }}</span>
                <span v-else class="badge badge-primary badge-lg">NO HP</span>
                </div>

                <div>
                <span v-if="item.adr" class="badge badge-primary badge-lg">Cod. UN = {{ item.adr_onu_code }} con 
                    <span v-if="item.adr_totale" class="badge badge-primary badge-lg">ADR Totale</span>
                    <span v-if="item.adr_esenzione_totale" class="badge badge-primary badge-lg">Esenzione Totale</span>
                    <span v-if="item.adr_esenzione_parziale" class="badge badge-primary badge-lg">Esenzione Parziale</span>
                </span>
                <span v-else class="badge badge-primary badge-lg">NO ADR</span>
                </div>
            </div>
        </div>

        <!-- BLOCCO Peso e magazzino -->
        <div class="flex flex-col gap-1 justify-evenly">
                <div class="flex flex-row gap-4 justify-evenly">
                    <div>
                        <font-awesome-icon :icon="['fas', 'weight-scale']" class="text-2xl text-primary"/>
                        cliente: 
                        <div class="badge badge-primary badge-lg">
                        {{ item.weight_declared }} Kg    
                        </div>
                    </div>

                    <div>
                        <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl text-primary"/>
                        previsto:
                        <div class="badge badge-primary badge-lg">
                        {{ item.warehouse.denominazione }}
                        </div>
                    </div>

                    <div>
                        <!-- MAGAZZINO Select/Option -->
                        <font-awesome-icon :icon="['fas', 'warehouse']" class="text-2xl text-primary"/>
                        effettivo: 
                        <div class="badge badge-primary badge-lg">
                        {{ warehouse.denominazione }}
                        </div>
                    </div>

                </div>

                <div>
                    <input 
                        v-model="item.warehouse_notes" 
                        type="text" 
                        class="input input-bordered flex w-full"
                        placeholder="Note / Non conformità"
                    />
                </div>
        </div>


    </div>


  
</Box>

</template>

<script setup>
  import Box from "@/Components/UI/Box.vue";
  import { ref, watch, computed } from "vue";
  import { usePage } from '@inertiajs/vue3';
  import vSelect from "vue-select";
  import "vue-select/dist/vue-select.css";
  
  const props = defineProps({ 
    item: Object,
    index: Number,
    warehouse: Object,
  });
</script>