<script setup>
    import { computed, watch, ref, onMounted, onUnmounted, toRaw, nextTick } from 'vue';
    import { useStore } from 'vuex';
    import { Link, useForm, usePage } from '@inertiajs/vue3'
    import dayjs from 'dayjs';
    import { format } from 'date-fns';
    import { it } from 'date-fns/locale';
    import '@vuepic/vue-datepicker/dist/main.css';
    import "vue-select/dist/vue-select.css";
    import vSelect from "vue-select";
    import { getIconForSite } from '@/Composables/getIconForSite';
    import draggable from 'vuedraggable'
    import DraggableOrder from './Components/DraggableOrder.vue';
    import JourneyMap from './Components/JourneyMap.vue';
    import eventBus from '@/eventBus';
    import { useJourneyStopsBuilder } from '@/Composables/journey/useJourneyStopsBuilder'
    import { useJourneyLookups } from '@/Composables/journey/useJourneyLookups'
    import JourneyVehiclePanels from './Components/JourneyVehiclePanels.vue'
    import JourneySidebar from './Components/JourneySidebar.vue'
    import StopManagerDrawer from './Components/StopManagerDrawer.vue'


    const props = defineProps({
      vehicles: Array,
      trailers: Array,
      cargos: Array,
      holders: Array,
      journeyStopActions: Array,
      drivers: Array,
      warehouses: Array,
      orders: Array,
      currentUser: Object
    })
    
    const page = usePage()
    /*
    const store = useStore();
    const currentSite = computed(() => store.state.currentSite || null );
    */

    const user = computed(
      () => page.props.user
    )
    
    const extendedMapMode = ref(false);
    const toggleExtendedMapMode = () => {
      extendedMapMode.value = !extendedMapMode.value;
    }

    const listMotrice      = ref([]);
    const listRimorchio    = ref([]);
    const listRiempimento  = ref([]);
    const listOrdini       = ref([...props.orders]);

    const { stops } = useJourneyStopsBuilder({
      listMotrice,
      listRimorchio,
      listRiempimento,
    })

    // versione deduplicata, più robusta del semplice concat
    const allOrders = computed(() => {
      const byId = new Map()

      const lists = [
        ...(listMotrice.value || []),
        ...(listRimorchio.value || []),
        ...(listRiempimento.value || []),
        ...(listOrdini.value || []),
      ]

      for (const o of lists) {
        if (!o?.id) continue
        if (!byId.has(o.id)) byId.set(o.id, o)
      }

      return Array.from(byId.values())
    })
    const { customerById, siteByCustomerId } = useJourneyLookups(allOrders)

    // array si Fermate (Stop) ordinate (draggable) manualmente
    const manualStopOrder = ref([])
    const mainPaneRef = ref(null)
    const stopsDrawerOpen = ref(false)
    const technicalStops = ref([])
    const technicalStopCounter = ref(0)

    const allStops = computed(() => [
      ...(stops.value || []),
      ...(technicalStops.value || []),
    ])

    const stopsEnriched = computed(() => 
      (allStops.value || []).map(s => ({
        ...s,
        customer: s.customer_id ? customerById.value.get(s.customer_id) : null,
        site: s.customer_id ? siteByCustomerId.value.get(s.customer_id) : null,
        orders_count: (s.orders || []).length,
      }))
    )

    const stopKey = (s) => {
      if (s.kind === 'technical') return `tech:${s.local_id ?? s.id}`
      return `customer:${s.customer_id}:${s.customer_visit_index ?? 1}`
    }

    const isDraggingStops = ref(false)

    const stopsByKey = computed(() => {
      const m = new Map()
      for (const s of (stopsEnriched.value || [])) m.set(stopKey(s), s)
      return m
    })


    watch(stopsEnriched, (now) => {
      if (isDraggingStops.value) return

      const incomingKeys = now.map(stopKey)
      const incomingSet = new Set(incomingKeys)

      // tieni quelli ancora presenti
      const kept = manualStopOrder.value.filter(k => incomingSet.has(k))

      // aggiungi nuovi in coda
      const missing = incomingKeys.filter(k => !kept.includes(k))

      manualStopOrder.value = [...kept, ...missing]
    }, { immediate: true })


    const stopsEnrichedOrdered = computed(() => {
      const byKey = new Map((stopsEnriched.value || []).map(s => [stopKey(s), s]))

      const ordered = []
      for (const k of (manualStopOrder.value || [])) {
        const s = byKey.get(k)
        if (s) ordered.push(s)
      }

      // safety: se per qualche motivo manca qualcosa
      if (ordered.length !== byKey.size) {
        for (const [k, s] of byKey.entries()) {
          if (!ordered.includes(s)) ordered.push(s)
        }
      }

      // renumber solo in output, senza mutare stop originali
      return ordered.map((s, idx) => ({
        ...s,
        sequence: idx + 1,
        planned_sequence: idx + 1,
      }))
    })

    /** helper per il salvataggio */
    const orderedStopsForSubmit = computed(() => {
      const out = []
      for (const k of manualStopOrder.value) {
        const s = stopsByKey.value.get(k)
        if (s) out.push(s)
      }
      return out.map((s, idx) => ({
        ...s,
        sequence: idx + 1,
        planned_sequence: idx + 1,
      }))
    })


    const form = useForm({
      dt_start: '',
      dt_end: '',
      vehicle_id: '',
      cargo_for_vehicle_id: '',
      trailer_id: '',
      cargo_for_trailer_id: '',
      driver_id: '',
      logistic_id: '',
      orders_truck: [],
      orders_trailer: [],
      orders_fulfill: [],
      compartments: {
        truck: [],
        trailer: [],
        fulfill: [],
      },
      stops: [],
    })
    
    const create = () => {
      // Before submitting, format the date correctly
      form.dt_start = dayjs(form.dt_start).format('YYYY-MM-DD HH:mm:ss');
      form.dt_end   = dayjs(form.dt_end  ).format('YYYY-MM-DD HH:mm:ss');
      // Map each list to only send IDs
      form.orders_truck    = listMotrice.value.map(order => order.id);
      form.orders_trailer  = listRimorchio.value.map(order => order.id);
      form.orders_fulfill = listRiempimento.value.map(order => order.id);
      form.compartments = {
        truck: form.orders_truck,
        trailer: form.orders_trailer,
        fulfill: form.orders_fulfill,
      }

      form.stops = orderedStopsForSubmit.value.map(s => ({
        kind: s.kind,
        customer_id: s.kind === 'customer' ? (s.customer_id ?? null) : null,
        customer_visit_index: s.kind === 'customer' ? (s.customer_visit_index ?? 1) : null,
        technical_action_id: s.kind === 'technical' ? (s.technical_action_id ?? null) : null,
        sequence: s.sequence,
        planned_sequence: s.planned_sequence,
        status: s.status ?? 'planned',
        orders: s.orders ?? [],
      }))

      form.logistic_id = user.value.id;

      form.post(route('journey.store'));
    }



    const viewMode = ref('empty');
    const clickedElement = ref();

    const handleEditItem = (element) => {
      viewMode.value = 'edit';
    }

    const handleViewItem = (element) => {
      viewMode.value = 'info';
      clickedElement.value = element
    }

    const handleViewMap = (element) => {
      viewMode.value = 'map';
      clickedElement.value = element
    }

    const clearViewMode = () => {
      viewMode.value = 'empty';
    }
/*
    const calculateTotalLoad = () =>{
      ordiniCasseMotrice.value= 0
      ordiniBancaleMotrice.value= 0
      ordiniCaricoMotrice.value= 0
      listMotrice.value.forEach(order => {
        (order.items).forEach(item => {
          ordiniCaricoMotrice.value += item.weight_declared;
        })
      });

      ordiniCasseRimorchio.value= 0
      ordiniBancaleRimorchio.value= 0
      ordiniCaricoRimorchio.value= 0
      listRimorchio.value.forEach(order => {
        (order.items).forEach(item => {
          ordiniCaricoRimorchio.value += item.weight_declared;
        })
      });
      
      ordiniCasseRiempimento.value= 0
      ordiniBancaleRiempimento.value= 0
      ordiniCaricoRiempimento.value= 0
      listRiempimento.value.forEach(order => {
        (order.items).forEach(item => {
          ordiniCaricoRiempimento.value += item.weight_declared;
        })
      });
      
    }
 */

 const calculateTotalLoad = () => {
  // MOTRICE
  const mot = computeCompartmentLoad(
    listMotrice.value,
    spaziCasseMotrice.value,
    spaziBancaleMotrice.value
  )

  // peso: esattamente quello che facevi prima
  ordiniCaricoMotrice.value   = mot.pesoTot
  // spazi a terra:
  ordiniBancaleMotrice.value  = mot.bancali.used
  ordiniCasseMotrice.value    = mot.casse.used_spaces

  showCapacityAlerts(mot, 'Motrice')

  // RIMORCHIO
  const rim = computeCompartmentLoad(
    listRimorchio.value,
    spaziCasseRimorchio.value,
    spaziBancaleRimorchio.value
  )

  ordiniCaricoRimorchio.value  = rim.pesoTot
  ordiniBancaleRimorchio.value = rim.bancali.used
  ordiniCasseRimorchio.value   = rim.casse.used_spaces

  if (trailerEnabled.value) showCapacityAlerts(rim, 'Rimorchio')

  // RIEMPIMENTO: stima complessiva (se ti interessa anche qui il peso totale)
  const rie = computeCompartmentLoad(
    listRiempimento.value,
    (spaziCasseMotrice.value + spaziCasseRimorchio.value),
    (spaziBancaleMotrice.value + spaziBancaleRimorchio.value)
  )

  ordiniCaricoRiempimento.value  = rie.pesoTot
  ordiniBancaleRiempimento.value = rie.bancali.used
  ordiniCasseRiempimento.value   = rie.casse.used_spaces
}


    const trailerEnabled          = ref(false);

    const spaziCasseMotrice       = ref(0);
    const spaziBancaleMotrice     = ref(0);
    const capacitaCaricoMotrice   = ref(0);
    const ordiniCasseMotrice      = ref(0);
    const ordiniBancaleMotrice    = ref(0);
    const ordiniCaricoMotrice     = ref(0);

    const spaziCasseRimorchio     = ref(0);
    const spaziBancaleRimorchio   = ref(0);
    const capacitaCaricoRimorchio = ref(0);
    const ordiniCasseRimorchio    = ref(0);
    const ordiniBancaleRimorchio  = ref(0);
    const ordiniCaricoRimorchio   = ref(0);
        
    const ordiniCasseRiempimento    = ref(0);
    const ordiniBancaleRiempimento  = ref(0);
    const ordiniCaricoRiempimento   = ref(0);
     
    const setPreferredTrailerAndCargo = () => {
      const selectedVehicle = props.vehicles.find(vehicle => vehicle.id === form.vehicle_id)
      capacitaCaricoMotrice.value = selectedVehicle.load_capacity;
      capacitaCaricoRimorchio.value = 0;
      // Verifico se può avere un rimorchio (NON POSSONO Furgoni e Camion con Sponda)
      if (selectedVehicle.has_trailer){
        form.trailer_id = selectedVehicle.trailer_id? selectedVehicle.trailer_id : '';
        trailerEnabled.value = true;
        const selectedTrailer = props.trailers.find(trailer => trailer.id === form.trailer_id );
        capacitaCaricoRimorchio.value = selectedTrailer.load_capacity;
      }
      else{
        form.trailer_id = '';
        trailerEnabled.value = false;
      }

      // Verifico se SPONDA o FURGONE e setto il corretto CASSONE PREDEFINITO
      if (selectedVehicle.type === 'sponda'){
        form.cargo_for_vehicle_id = props.cargos.find(cargo => cargo.name === 'Sponda').id;
      }
      else if (selectedVehicle.type === 'furgone') {
        form.cargo_for_vehicle_id = props.cargos.find(cargo => cargo.name === 'Furgone').id;
      }
      else{
        form.cargo_for_vehicle_id = ''
      }

      checkCargo({target:'internal'});
    }

    const checkCargo = (evt) => {
      //console.log(evt.target.id);
      const fieldName = evt.target.id;
      const selectedVehicle = props.vehicles.find(vehicle => vehicle.id === form.vehicle_id);
      const selectedVehicleCargo = ref(props.cargos.find(cargo => cargo.id === form.cargo_for_vehicle_id));
      const selectedTrailerCargo = ref(props.cargos.find(cargo => cargo.id === form.cargo_for_trailer_id));

      // Gestisco il caso in cui si sia modificato il rimorchio "TRAILER"
      if (fieldName === 'trailer_id') {
        if (form.trailer_id === '' ){
          selectedTrailerCargo.value = null;
          form.cargo_for_trailer_id = '';
          capacitaCaricoRimorchio.value = 0;
        }
        else{
          const selectedTrailer = props.trailers.find(trailer => trailer.id === form.trailer_id );
          capacitaCaricoRimorchio.value = selectedTrailer.load_capacity;
        }
      }
      


      if (selectedVehicleCargo.value && selectedTrailerCargo.value){
        if (selectedVehicleCargo.value.is_long && selectedTrailerCargo.value.is_long){
          alert('ATTENZIONE: autotreno con combinazione di due cassoni LUNGHI')
          // Reset the correct form field based on the event source
          if (fieldName === 'cargo_for_vehicle_id') {
            selectedVehicleCargo.value = null;
            form.cargo_for_vehicle_id = ''; // Reset the form value
          } 
          else if (fieldName === 'cargo_for_trailer_id') {
            selectedTrailerCargo.value = null;
            form.cargo_for_trailer_id = ''; // Reset the form value
          }

          // Also reset the select element visually
          evt.target.value = '';
        }
      }

      if (selectedVehicle.has_trailer && selectedVehicleCargo.value && (
          (selectedVehicleCargo.value.name === 'Sponda') || (selectedVehicleCargo.value.name === 'Furgone')
        )
      ){
        alert('Opzione "' + selectedVehicleCargo.value.name + '" selezionabile solo per motrice di tipo ' + selectedVehicleCargo.value.name);
        selectedVehicleCargo.value = null;
        form.cargo_for_vehicle_id = ''; // Reset the form value
      }

      spaziCasseMotrice.value     = 0;
      spaziBancaleMotrice.value   = 0;
      if(selectedVehicleCargo.value) {
        spaziCasseMotrice.value   = selectedVehicleCargo.value.spazi_casse;
        spaziBancaleMotrice.value = selectedVehicleCargo.value.spazi_bancale;
      }

      spaziCasseRimorchio.value   = 0;
      spaziBancaleRimorchio.value = 0;
      if(selectedTrailerCargo.value) {
        spaziCasseRimorchio.value   = selectedTrailerCargo.value.spazi_casse;
        spaziBancaleRimorchio.value = selectedTrailerCargo.value.spazi_bancale;
      }

      calculateTotalLoad()
    }


// Handlers for moving orders
const handleSetToTruck = (order) => {
  moveOrder(order, listMotrice);
};

const handleSetToTrailer = (order) => {
  moveOrder(order, listRimorchio);
};

const handleSetToRiempimento = (order) => {
  moveOrder(order, listRiempimento);
};

const handleSetToOrders = (order) => {
  moveOrder(order, listOrdini);
};

// Helper function to move an order between lists
const moveOrder = (order, targetList) => {
 
  if ( targetList != listMotrice &&
      listMotrice.value.findIndex( (o) => o.id === order.id) !== -1
    ) {
    const index = listMotrice.value.findIndex((o) => o.id === order.id);
    const [removed] = listMotrice.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }
  if (targetList != listRimorchio &&
      listRimorchio.value.findIndex((o) => o.id === order.id) !== -1
    ) {
    const index = listRimorchio.value.findIndex((o) => o.id === order.id);
    const [removed] = listRimorchio.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }
  if (targetList != listRiempimento &&
      listRiempimento.value.findIndex((o) => o.id === order.id) !== -1
    ) {
    const index = listRiempimento.value.findIndex((o) => o.id === order.id);
    const [removed] = listRiempimento.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }
  if (targetList != listOrdini &&
      listOrdini.value.findIndex((o) => o.id === order.id) !== -1
    ) {
    const index = listOrdini.value.findIndex((o) => o.id === order.id);
    const [removed] = listOrdini.value.splice(index, 1); // Remove from source
    targetList.value.push(removed); // Add to target
  }

  calculateTotalLoad();

};

const manageDate = () => {
  if (form.dt_start) {
        const startDate = dayjs(form.dt_start);
        const endDate = startDate.add(1, 'hour'); // Aggiunge un'ora di default
        form.dt_end = endDate.toDate();
  }
}

const openStopsDrawer = async () => {
  stopsDrawerOpen.value = true
  await nextTick()
  requestAnimationFrame(() => {
    const drawer = document.getElementById('stop-manager-drawer')
    if (!drawer) {
      window.scrollTo({ top: 0, behavior: 'smooth' })
      return
    }
    const headerOffset = 64
    const rectTop = drawer.getBoundingClientRect().top
    const targetTop = window.scrollY + rectTop - headerOffset
    window.scrollTo({ top: targetTop, behavior: 'smooth' })
  })
}

const addTechnicalStop = (actionId) => {
  const idNum = Number(actionId)
  const action = (props.journeyStopActions || []).find(a => Number(a.id) === idNum)
  if (!action) return

  technicalStopCounter.value += 1
  technicalStops.value.push({
    kind: 'technical',
    local_id: `t${technicalStopCounter.value}`,
    technical_action_id: action.id,
    action_label: action.label,
    status: 'planned',
    orders: [],
    orders_count: 0,
  })
}

const removeTechnicalStop = (key) => {
  technicalStops.value = (technicalStops.value || []).filter(s => stopKey(s) !== key)
}

// Set up event listeners on mount and clean up on unmount
onMounted(() => {
  eventBus.on('setOrderToTruckList', handleSetToTruck);
  eventBus.on('setOrderToTrailerList', handleSetToTrailer);
  eventBus.on('setOrderToRiempimentoList', handleSetToRiempimento);
  eventBus.on('setOrderToOrdersList', handleSetToOrders);

  calculateTotalLoad(); // inizializza peso + spazi
});

onUnmounted(() => {
  eventBus.off('setOrderToTruckList', handleSetToTruck);
  eventBus.off('setOrderToTrailerList', handleSetToTrailer);
  eventBus.off('setOrderToRiempimentoList', handleSetToRiempimento);
  eventBus.off('setOrderToOrdersList', handleSetToOrders);
});



/*
 * GESTIONE SPAZI A TERRA
 */

 // ====== COSTANTI & HELPERS ======
const HOLDER_ID_BANCALE = 2
const HOLDER_ID_CASSA   = 4

// Opzionale: impronta standard del bancale in cm^2 (se vuoi usare fallback footprint)
const PALLET_FOOTPRINT_CM2 = null // es. 120*80 = 9600 se vuoi usarlo

const ceilDiv = (num, den) => Math.ceil(num / den)

// --- Mappa holders by id, con flag “dichiarati” ---
const holdersById = computed(() => {
  const map = {}
  for (const h of (props.holders || [])) {
    map[h.id] = {
      id: h.id,
      name: h.name,
      is_custom: !!h.is_custom,
      volume_cm3: h.volume ?? null, // atteso in cm^3 quando sarà popolato
      equivalent_holder_id: h.equivalent_holder_id ?? null,
      equivalent_units: h.equivalent_units ?? null,
      is_declared_bancale: h.id === HOLDER_ID_BANCALE,
      is_declared_cassa:   h.id === HOLDER_ID_CASSA,
    }
  }
  return map
})

// ====== DIMENSIONI/CLASSIFICAZIONE NON STANDARD ======
// Recupera dimensioni in cm dai campi dell'item (custom_l_cm, custom_w_cm, custom_h_cm)
const getCustomDimsCm = (item) => {
  const L = Number(item.custom_l_cm ?? 0)
  const W = Number(item.custom_w_cm ?? 0)
  const H = Number(item.custom_h_cm ?? 0)
  return { L, W, H }
}
const volumeCm3 = ({L, W, H}) => (L>0 && W>0 && H>0) ? (L*W*H) : 0
const footprintCm2 = ({L, W}) => (L>0 && W>0) ? (L*W) : 0

// Trova holder standard (is_custom=0, volume valorizzato) col volume minimo >= nsVol
const findMinimalDominatingHolderByVolume = (nsVol) => {
  const candidates = (props.holders || [])
    .filter(h => !h.is_custom && h.volume && h.volume > 0)
    .sort((a,b) => a.volume - b.volume)
  for (const h of candidates) {
    if (h.volume >= nsVol) return h
  }
  return null
}

const classifyNonStandardItem = (item) => {
  // Ritorna { kind: 'bancale'|'cassa', units: number }
  const dims = getCustomDimsCm(item)
  const nsVol = volumeCm3(dims)
  const nsFoot = footprintCm2(dims)

  // 1) Volume: holder minimo dominante
  const eq = nsVol > 0 ? findMinimalDominatingHolderByVolume(nsVol) : null
  if (eq) {
    if (eq.id === HOLDER_ID_BANCALE || eq.equivalent_holder_id === HOLDER_ID_BANCALE) {
      return { kind: 'bancale', units: 1 }
    }
    if (eq.id === HOLDER_ID_CASSA || eq.equivalent_holder_id === HOLDER_ID_CASSA) {
      return { kind: 'cassa', units: 1 }
    }
    if (eq.equivalent_holder_id === HOLDER_ID_BANCALE) return { kind: 'bancale', units: 1 }
    if (eq.equivalent_holder_id === HOLDER_ID_CASSA)   return { kind: 'cassa', units: 1 }
  }

  // 2) Fallback footprint (se configurato)
  if (PALLET_FOOTPRINT_CM2 && nsFoot > 0) {
    if (nsFoot >= PALLET_FOOTPRINT_CM2) {
      const units = Math.max(1, ceilDiv(nsFoot, PALLET_FOOTPRINT_CM2))
      return { kind: 'bancale', units }
    } else {
      return { kind: 'cassa', units: 1 }
    }
  }

  // 3) Fallback altezza prudenziale
  const TALL_THRESHOLD_CM = 80 // puoi promuoverla a config UI
  if (dims.H && dims.H > TALL_THRESHOLD_CM) return { kind: 'bancale', units: 1 }
  return { kind: 'cassa', units: 1 }
}

// ====== CALCOLO COMPARTIMENTO ======
function computeCompartmentLoad(orders, capCasseBase, capBancaliBase) {
  const totalsByHolder = {}
  let pesoTot = 0

  for (const o of (orders || [])) {
    for (const it of (o.items || [])) {
      pesoTot += Number(it.weight_declared ?? 0)

      const holder_id = it.holder_id ?? it.holder?.id
      const qty = Number(it.holder_quantity ?? it.qty ?? 0)
      if (!holder_id || !qty) continue

      const h = holdersById.value[holder_id]
      if (!h) continue

      // Aggrego grezzo per holder (standard non-equivalenti)
      totalsByHolder[holder_id] = (totalsByHolder[holder_id] ?? 0) + qty
    }
  }

  // Bancali e Casse
  let bancaliDeclared = 0
  let bancaliEquiv = 0
  let casseDeclaredUnits = 0 // pezzi cassa (poi /3 -> spazi)
  let casseEquivUnits = 0

  // 1) Explode totalsByHolder distinguendo dichiarati/equivalenti e custom
  for (const [holderIdStr, qty] of Object.entries(totalsByHolder)) {
    const holderId = Number(holderIdStr)
    const h = holdersById.value[holderId]
    if (!h || !qty) continue

    if (!h.is_custom) {
      // ---- STANDARD ----
      if (h.is_declared_bancale) { bancaliDeclared += qty; continue }
      if (h.is_declared_cassa)   { casseDeclaredUnits += qty; continue }

      if (h.equivalent_holder_id === HOLDER_ID_BANCALE && h.equivalent_units) {
        bancaliEquiv += ceilDiv(qty, h.equivalent_units)
        continue
      }
      if (h.equivalent_holder_id === HOLDER_ID_CASSA && h.equivalent_units) {
        casseEquivUnits += ceilDiv(qty, h.equivalent_units)
        continue
      }

      // Se standard ma senza mapping → ignora o log
      continue
    }

    // ---- CUSTOM per holder_id di tipo "Imballo NON Standard" ----
    // Serve iterare sui singoli item per leggere dimensioni (non basta il totale by holder)
  }

  // 2) Aggiungi contributo dei NON standard item iterando sugli item (serve per le dimensioni)
  for (const o of (orders || [])) {
    for (const it of (o.items || [])) {
      const holder_id = it.holder_id ?? it.holder?.id
      const qty = Number(it.holder_quantity ?? it.qty ?? 0)
      if (!holder_id || !qty) continue
      const h = holdersById.value[holder_id]
      if (!h || !h.is_custom) continue

      const cls = classifyNonStandardItem(it) // {kind, units}
      if (cls.kind === 'bancale') {
        bancaliEquiv += (cls.units * qty)
      } else {
        casseEquivUnits += (cls.units * qty)
      }
    }
  }

  // 3) Totali usati
  const bancaliUsed = bancaliDeclared + bancaliEquiv
  const casseUnitsTotal = casseDeclaredUnits + casseEquivUnits
  const casseUsedSpaces = Math.ceil(casseUnitsTotal / 3) // impilamento a 3

  // 4) Interdipendenza
  let cassaCapAvail = 0
  if (bancaliUsed >= capBancaliBase) {
    cassaCapAvail = 0
  } else {
    cassaCapAvail = Math.max(0, Number(capCasseBase ?? 0) - bancaliUsed)
  }

  const overBancali = bancaliUsed > Number(capBancaliBase ?? 0)
  const overCasse   = casseUsedSpaces > cassaCapAvail

  return {
    pesoTot,
    bancali: {
      used: bancaliUsed,
      cap: Number(capBancaliBase ?? 0),
      over: overBancali,
      breakdown: { declared: bancaliDeclared, equivalent: bancaliEquiv },
    },
    casse: {
      used_spaces: casseUsedSpaces,
      cap_base: Number(capCasseBase ?? 0),
      cap_available: cassaCapAvail,
      over: overCasse,
      breakdown: {
        declared_units: casseDeclaredUnits,
        equivalent_units: casseEquivUnits,
      },
    },
  }
}

// ====== ALERT SINTETICI ======
function showCapacityAlerts(loadObj, labelCompartimento) {
  const messages = []
  if (loadObj.bancali.over) {
    messages.push(`${labelCompartimento}: spazi BANCALI superati (usati ${loadObj.bancali.used} / cap ${loadObj.bancali.cap})`)
  }
  if (loadObj.casse.over) {
    messages.push(`${labelCompartimento}: spazi CASSE superati (usati ${loadObj.casse.used_spaces} / disp ${loadObj.casse.cap_available})`)
  }
  if (messages.length) alert(messages.join('\n'))
}


</script>
    

<template>


<button 
    type="button"
    class="btn btn-primary btn-circle btn-toggle-map"
    :class="extendedMapMode? 'btn-error' : 'btn-success'"
    @click.prevent="toggleExtendedMapMode()"
>
  <font-awesome-icon 
    :icon="['fas', 'location-pin-lock']" 
    class="text-2xl"
  />
 </button>

<!-- MAIN CONTAINER -->
<div class="flex flex-row" :class="extendedMapMode? 'full-width' : 'container mx-auto'">
  <!-- LIST HALF -->
  <div ref="mainPaneRef" :class="extendedMapMode? 'w-1/2 px-8 relative' : 'w-full relative'">
    <form @submit.prevent="create">

      <div class="grid grid-cols-12 justify-stretch gap-2 w-full mb-4">


        
        <div class="col-span-9">

          <!-- TESTATA TOTALI -->
          <JourneyVehiclePanels
            :vehicles="props.vehicles"
            :trailers="props.trailers"
            :cargos="props.cargos"
            :form="form"
            :trailer-enabled="trailerEnabled"
            :set-preferred-trailer-and-cargo="setPreferredTrailerAndCargo"
            :check-cargo="checkCargo"
            :spazi-casse-motrice="spaziCasseMotrice"
            :spazi-bancale-motrice="spaziBancaleMotrice"
            :capacita-carico-motrice="capacitaCaricoMotrice"
            :ordini-casse-motrice="ordiniCasseMotrice"
            :ordini-bancale-motrice="ordiniBancaleMotrice"
            :ordini-carico-motrice="ordiniCaricoMotrice"
            :spazi-casse-rimorchio="spaziCasseRimorchio"
            :spazi-bancale-rimorchio="spaziBancaleRimorchio"
            :capacita-carico-rimorchio="capacitaCaricoRimorchio"
            :ordini-casse-rimorchio="ordiniCasseRimorchio"
            :ordini-bancale-rimorchio="ordiniBancaleRimorchio"
            :ordini-carico-rimorchio="ordiniCaricoRimorchio"
          />

          <!-- LISTE DRAGGABILI -->
          <div class="flex flex-row justify-items-stretch gap-2 w-full mt-4">
            <div class="flex-1">
              
              <h3 class="font-semibold">Carico Motrice</h3>
              <draggable 
                v-model="listMotrice" 
                group="orders" 
                @start="drag=true" 
                @end="drag=false" 
                @change="calculateTotalLoad"
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableOrder
                    :element = "element"
                    :index = "index"
                    @edit-item="handleEditItem"
                    @view-item="handleViewItem"
                    @view-map="handleViewMap"
                  />
                </template>
              </draggable>

            </div>
            <div class="flex-1">
              <h3 class="font-semibold">Carico Rimorchio</h3>
              <draggable 
                v-if="trailerEnabled"
                v-model="listRimorchio" 
                group="orders" 
                @start="drag=true" 
                @end="drag=false" 
                @change="calculateTotalLoad"
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableOrder
                    :element = "element"
                    :index = "index"
                    @edit-item="handleEditItem"
                    @view-item="handleViewItem"
                    @view-map="handleViewMap"
                  />
                </template>
              </draggable>
            </div>
            <div class="flex-1">
              <h3 class="font-semibold">Carico a riempimento</h3>

              <draggable 
                v-model="listRiempimento" 
                v-if="trailerEnabled"
                group="orders" 
                @start="drag=true" 
                @end="drag=false" 
                @change="calculateTotalLoad"
                item-key="id"
                class=" bg-primary p-1 pb-16 rounded-md"
              >
                <template #item="{element, index}">
                  <DraggableOrder
                    :element = "element"
                    :index = "index"
                    @edit-item="handleEditItem"
                    @view-item="handleViewItem"
                    @view-map="handleViewMap"
                  />
                </template>
              </draggable>
            </div>
          </div>

          <!-- MASONRY ORDINI -->
          <div class="w-full mb-8 mt-4">
          <h3 class="font-semibold">Ordini Aperti</h3>
            <draggable
              :list="listOrdini"
              group="orders"
              @start="drag=true" 
              @end="drag=false" 
              tag="div"
              class="masonry-grid bg-primary p-1 pb-16 rounded-md"
              item-key="id"          
            >
              <template #item="{element, index}">
                <div class="masonry-item">
                <DraggableOrder
                  :element = "element"
                  :index = "index"
                  @edit-item="handleEditItem"
                  @view-item="handleViewItem"
                  @view-map="handleViewMap"
                />
                </div>
              </template>
            </draggable>
          </div>

        </div>

        <!-- FLOATING INFO OBJECT -->
        <div class="col-span-3">
          <JourneySidebar
            v-model:manualStopOrder="manualStopOrder"
            :form="form"
            :drivers="props.drivers"
            :view-mode="viewMode"
            :clicked-element="clickedElement"
            :manage-date="manageDate"
            :clear-view-mode="clearViewMode"
            :spazi-casse-motrice="spaziCasseMotrice"
            :spazi-casse-rimorchio="spaziCasseRimorchio"
            :spazi-bancale-motrice="spaziBancaleMotrice"
            :spazi-bancale-rimorchio="spaziBancaleRimorchio"
            :capacita-carico-motrice="capacitaCaricoMotrice"
            :capacita-carico-rimorchio="capacitaCaricoRimorchio"
            :ordini-casse-motrice="ordiniCasseMotrice"
            :ordini-casse-rimorchio="ordiniCasseRimorchio"
            :ordini-casse-riempimento="ordiniCasseRiempimento"
            :ordini-bancale-motrice="ordiniBancaleMotrice"
            :ordini-bancale-rimorchio="ordiniBancaleRimorchio"
            :ordini-bancale-riempimento="ordiniBancaleRiempimento"
            :ordini-carico-motrice="ordiniCaricoMotrice"
            :ordini-carico-rimorchio="ordiniCaricoRimorchio"
            :ordini-carico-riempimento="ordiniCaricoRiempimento"
            :stops-by-key="stopsByKey"
            @dragging="isDraggingStops = $event"
            @open-stops-manager="openStopsDrawer"
          />
        </div>

      </div>

    </form>

    <StopManagerDrawer
      v-if="stopsDrawerOpen"
      v-model:stopOrder="manualStopOrder"
      :stops-by-key="stopsByKey"
      :technical-actions="props.journeyStopActions"
      @add-technical-stop="addTechnicalStop"
      @remove-technical-stop="removeTechnicalStop"
      @dragging="isDraggingStops = $event"
      @close="stopsDrawerOpen = false"
    />
  </div>
  <!-- END-OF LIST HALF -->

  <!-- MAP HALF -->
  <div v-if="extendedMapMode" :class="extendedMapMode? 'w-1/2 sticky top-24' : 'w-0'">
    <JourneyMap 
      :orders="props.orders"
      v-model:listMotrice = "listMotrice"
      v-model:listRimorchio = "listRimorchio"
      v-model:listRiempimento = "listRiempimento"
    >

    </JourneyMap>
  </div>
  <!-- END-OF MAP HALF -->

</div>

</template>
    

    <style scoped>
    .button {
      margin-top: 35px;
    }
    .handle {
      float: left;
      padding-top: 8px;
      padding-bottom: 8px;
    }
    
    .close {
      float: right;
      padding-top: 8px;
      padding-bottom: 8px;
    }
    
    input {
      display: inline-block;
      width: 50%;
    }
    
    .text {
      margin: 20px;
    }

    .btn-toggle-map{
      position: fixed;
      left: 32px;
      top: 74px;
    }

    .masonry-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      grid-auto-rows: auto;
      gap: 4px;
    }

    .masonry-item {
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    </style>
