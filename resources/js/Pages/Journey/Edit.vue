<script setup>
import PlanningColumns from '@/Components/UI/PlanningColumns.vue'
import AppAction from '@/Components/UI/AppAction.vue'
import {useJourneyVehicleConfiguration} from '@/Composables/useJourneyVehicleConfiguration'
import OpenOrdersFilter from './Components/OpenOrdersFilter.vue'
import {matchesCustomerName} from '@/utils/customerNameSearch'
import AppPage from '@/Components/UI/AppPage.vue'
import RegistryForm from '@/Components/Registry/RegistryForm.vue'
import PlanningMapPanel from '@/Components/UI/PlanningMapPanel.vue'
import {usePlanningMapMode} from '@/Composables/usePlanningMapMode'
import DetachedTrailerOrders from './Components/DetachedTrailerOrders.vue'
import JourneyPlanningActions from './Components/JourneyPlanningActions.vue'

    import { computed, watch, ref, onMounted, onUnmounted, toRaw, nextTick } from 'vue';
    import { useStore } from 'vuex';
    import { Link, useForm, usePage, router } from '@inertiajs/vue3'
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
    import JourneyDocumentGenerationPanel from './Components/JourneyDocumentGenerationPanel.vue'
    import AuditCollapse from '@/Components/AuditCollapse.vue'
    import { JOURNEY_STOP_STATUS } from '@/Constants/journeyStopStatus'
    import { formatServerDateTime, parseServerDateTime } from '@/utils/serverDateTime'


    const props = defineProps({
      journey: Object,
      vehicles: Array,
      trailers: Array,
      cargos: Array,
      holders: Array,
      journeyStopActions: Array,
      drivers: Array,
      warehouses: Array,
      orders: Array,
      audits: Array,
    })

    const page = usePage()
    const store = useStore();
    /*
    const currentSite = computed(() => store.state.currentSite || null );
    */

    const user = computed(
      () => page.props.user
    )

    const auditFieldLabels = {
      driver_id: 'Autista',
      vehicle_id: 'Motrice',
      trailer_id: 'Rimorchio',
      vehicle_cargo_id: 'Cassone motrice',
      trailer_cargo_id: 'Cassone rimorchio',
      planned_start_at: 'Inizio pianificato',
      planned_end_at: 'Fine pianificata',
      actual_start_at: 'Inizio effettivo',
      actual_end_at: 'Fine effettiva',
      status: 'Stato viaggio',
      dispatch_status: 'Stato dispatch',
      notes: 'Note',
    }

    const goBackToOrders = () => {
        router.visit(route('journey.index'))
    }

    const mapMode = usePlanningMapMode();
    const extendedMapMode = ref(false);
    const toggleExtendedMapMode = () => {
      extendedMapMode.value = !extendedMapMode.value;
    }

    const listMotrice      = ref([]);
    const listRimorchio    = ref([]);
    const listRiempimento  = ref([]);
    const listOrdini       = ref([]);
    const openOrderQuery = ref('')
    const visibleOpenOrderIds = computed(() => new Set(listOrdini.value.filter(order => matchesCustomerName(order.customer?.company_name, openOrderQuery.value)).map(order => order.id)))


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

    const selectedJourneyOrders = computed(() => {
      const byId = new Map()
      for (const order of [
        ...(listMotrice.value || []),
        ...(listRimorchio.value || []),
        ...(listRiempimento.value || []),
      ]) {
        if (!order?.id) continue
        if (!byId.has(order.id)) byId.set(order.id, order)
      }
      return Array.from(byId.values())
    })
    const { customerById, siteByCustomerId } = useJourneyLookups(allOrders)

    // array si Fermate (Stop) ordinate (draggable) manualmente
    const manualStopOrder = ref([])
    const stopMeta = ref({})
    const mainPaneRef = ref(null)
    const stopsDrawerOpen = ref(false)
    const technicalStops = ref([])
    const technicalStopCounter = ref(0)

    const allStops = computed(() => [
      ...(stops.value || []),
      ...(technicalStops.value || []),
    ])

    const stopsEnriched = computed(() =>
      (allStops.value || []).map(s => {
        const key = stopKey(s)
        const meta = stopMeta.value[key] ?? {}
        const site = s.customer_id ? siteByCustomerId.value.get(s.customer_id) : null
        return {
          ...s,
          customer: s.customer_id ? customerById.value.get(s.customer_id) : null,
          site,
          orders_count: (s.orders || []).length,
          description: s.description ?? meta.description ?? '',
          address_text: s.address_text ?? meta.address_text ?? (site?.address ?? ''),
        }
      })
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
      planned_start_at: '',
      planned_end_at: '',
      notes: '',
      vehicle_id: '',
      vehicle_cargo_id: '',
      trailer_id: '',
      trailer_cargo_id: '',
      driver_id: '',
      logistics_user_id: '',
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

    const buildJourneyPayload = () => {
      const payload = {...form.data()}
      // Before submitting, format the date correctly
      payload.planned_start_at = formatServerDateTime(payload.planned_start_at);
      payload.planned_end_at   = formatServerDateTime(payload.planned_end_at);
      // Map each list to only send IDs
      payload.orders_truck    = listMotrice.value.map(order => order.id);
      payload.orders_trailer  = listRimorchio.value.map(order => order.id);
      payload.orders_fulfill = listRiempimento.value.map(order => order.id);
      payload.compartments = {
        truck: payload.orders_truck,
        trailer: payload.orders_trailer,
        fulfill: payload.orders_fulfill,
      }

      payload.stops = orderedStopsForSubmit.value.map(s => ({
        kind: s.kind,
        customer_id: s.kind === 'customer' ? (s.customer_id ?? null) : null,
        customer_visit_index: s.kind === 'customer' ? (s.customer_visit_index ?? 1) : null,
        technical_action_id: s.kind === 'technical' ? (s.technical_action_id ?? null) : null,
        location_lat: getStopCoords(s)?.lat ?? null,
        location_lng: getStopCoords(s)?.lng ?? null,
        description: s.description ?? null,
        address_text: s.address_text ?? null,
        sequence: s.sequence,
        planned_sequence: s.planned_sequence,
        status: s.status ?? 'planned',
        orders: s.orders ?? [],
      }))

      delete payload.compartments
      return payload
    }
    const savedJourneyPayload = ref(null)
    const documentsBusy = ref(true)
    const journeyDirty = computed(() => savedJourneyPayload.value !== null && JSON.stringify(buildJourneyPayload()) !== savedJourneyPayload.value)
    const saveDisabled = computed(() => form.processing || documentsBusy.value || !journeyDirty.value || !configurationReady.value)
    const updateJourney = () => {
      if (saveDisabled.value) return
      form.transform(() => buildJourneyPayload())
      form.put(route('journey.update', { journey: props.journey.id }), {
        onError: (errors) => {
          const keys = Object.keys(errors || {})
          if (keys.length) {
            store.dispatch('flash/queueMessage', { type: 'error', text: errors[keys[0]] })
          } else {
            store.dispatch('flash/queueMessage', { type: 'error', text: 'Errore durante la modifica del viaggio.' })
          }
        },
      });
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


    const {configuration, trailerEnabled} = useJourneyVehicleConfiguration(form, props);
    const configurationReady = computed(() => configuration.value.ready && (form.trailer_id || (!listRimorchio.value.length && !listRiempimento.value.length)));

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

    const checkCargo = () => {
      const c = configuration.value
      capacitaCaricoMotrice.value = Number(c.vehicle?.load_capacity ?? 0)
      capacitaCaricoRimorchio.value = c.trailerAllowed ? Number(c.trailer?.load_capacity ?? 0) : 0
      spaziCasseMotrice.value = Number(c.cargo?.spazi_casse ?? 0)
      spaziBancaleMotrice.value = Number(c.cargo?.spazi_bancale ?? 0)
      spaziCasseRimorchio.value = c.trailerAllowed && c.trailer ? Number(c.trailerCargo?.spazi_casse ?? 0) : 0
      spaziBancaleRimorchio.value = c.trailerAllowed && c.trailer ? Number(c.trailerCargo?.spazi_bancale ?? 0) : 0
      calculateTotalLoad()
    }
    const setPreferredTrailerAndCargo = () => {
      const c = configuration.value
      form.vehicle_cargo_id = c.fixedName ? (props.cargos.find(cargo => cargo.name === c.fixedName)?.id ?? '') : ''
      form.trailer_id = c.trailerAllowed ? (c.vehicle.trailer_id ?? '') : ''
      form.trailer_cargo_id = ''
      checkCargo()
    }
    const syncVehicleDerivedStateFromCurrentSelection = checkCargo
    watch(() => form.trailer_id, value => { if (!value) form.trailer_cargo_id = ''; checkCargo() })

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
  if (targetList !== listOrdini && !configurationReady.value) return;

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
  if (form.planned_start_at) {
        const startDate = dayjs(form.planned_start_at);
        const endDate = startDate.add(1, 'hour'); // Aggiunge un'ora di default
        form.planned_end_at = formatServerDateTime(endDate);
  }
}

const defaultMapLat = Number(import.meta.env.VITE_MAPS_CENTER_DEFAULT_LAT ?? 0)
const defaultMapLng = Number(import.meta.env.VITE_MAPS_CENTER_DEFAULT_LNG ?? 0)

const getStopCoords = (s) => {
  if (!s) return null
  if (s.kind === 'technical') {
    const lat = Number(s.location_lat ?? s.lat ?? s.latitude ?? NaN)
    const lng = Number(s.location_lng ?? s.lng ?? s.longitude ?? NaN)
    if (Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng }
    return null
  }
  const lat = Number(s.site?.latitude ?? NaN)
  const lng = Number(s.site?.longitude ?? NaN)
  if (Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng }
  return null
}

const getCoordsFromKey = (key) => {
  const s = stopsByKey.value.get(key)
  return getStopCoords(s)
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

  const prevKey = manualStopOrder.value.length ? manualStopOrder.value[manualStopOrder.value.length - 1] : null
  const nextKey = !prevKey && manualStopOrder.value.length ? manualStopOrder.value[0] : null
  const prevCoords = prevKey ? getCoordsFromKey(prevKey) : null
  const nextCoords = nextKey ? getCoordsFromKey(nextKey) : null
  const fallbackCoords = prevCoords ?? nextCoords ?? { lat: defaultMapLat, lng: defaultMapLng }

  technicalStopCounter.value += 1
  technicalStops.value.push({
    kind: 'technical',
    local_id: `t${technicalStopCounter.value}`,
    technical_action_id: action.id,
    action_label: action.label,
    status: JOURNEY_STOP_STATUS.PLANNED,
    orders: [],
    orders_count: 0,
    open: true,
    location_lat: fallbackCoords.lat,
    location_lng: fallbackCoords.lng,
  })
}

const removeTechnicalStop = (key) => {
  technicalStops.value = (technicalStops.value || []).filter(s => stopKey(s) !== key)
}

const updateTechnicalStopCoords = ({ key, lat, lng }) => {
  if (!key) return
  const target = (technicalStops.value || []).find(s => stopKey(s) === key)
  if (!target) return
  target.location_lat = lat
  target.location_lng = lng
}

const updateStopFields = ({ key, description, address_text }) => {
  if (!key) return
  if (key.startsWith('tech:')) {
    const target = (technicalStops.value || []).find(s => stopKey(s) === key)
    if (!target) return
    if (description !== undefined) target.description = description
    if (address_text !== undefined) target.address_text = address_text
    return
  }
  stopMeta.value[key] = {
    ...(stopMeta.value[key] ?? {}),
    ...(description !== undefined ? { description } : {}),
    ...(address_text !== undefined ? { address_text } : {}),
  }
}

const hydrateFromJourney = () => {
  const journey = props.journey || {}
  const journeyOrders = Array.isArray(journey.orders) ? journey.orders : []
  const allOrdersById = new Map((props.orders || []).map((o) => [o.id, o]))

  form.planned_start_at = formatServerDateTime(parseServerDateTime(journey.planned_start_at)) ?? ''
  form.planned_end_at = formatServerDateTime(parseServerDateTime(journey.planned_end_at)) ?? ''
  form.notes = journey.notes ?? ''
  form.vehicle_id = journey.vehicle_id ? Number(journey.vehicle_id) : ''
  form.vehicle_cargo_id = journey.vehicle_cargo_id ? Number(journey.vehicle_cargo_id) : ''
  form.trailer_id = journey.trailer_id ? Number(journey.trailer_id) : ''
  form.trailer_cargo_id = journey.trailer_cargo_id ? Number(journey.trailer_cargo_id) : ''
  form.driver_id = journey.driver_id ? Number(journey.driver_id) : ''
  form.logistics_user_id = journey.logistics_user_id ? Number(journey.logistics_user_id) : (user.value?.id ?? '')

  const selectedByLocation = {
    vehicle: [],
    trailer: [],
    fulfill: [],
  }

  for (const order of journeyOrders) {
    const source = allOrdersById.get(order.id) || order
    const location = order.cargo_location || source.cargo_location
    if (location === 'vehicle') selectedByLocation.vehicle.push(source)
    else if (location === 'trailer') selectedByLocation.trailer.push(source)
    else if (location === 'fulfill') selectedByLocation.fulfill.push(source)
  }

  listMotrice.value = selectedByLocation.vehicle
  listRimorchio.value = selectedByLocation.trailer
  listRiempimento.value = selectedByLocation.fulfill

  const selectedIds = new Set(journeyOrders.map((o) => o.id))
  listOrdini.value = (props.orders || []).filter((o) => !selectedIds.has(o.id))

  const orderedStops = [...(journey.stops || [])].sort((a, b) => {
    const aSeq = a.sequence ?? a.planned_sequence ?? 0
    const bSeq = b.sequence ?? b.planned_sequence ?? 0
    return aSeq - bSeq
  })

  technicalStops.value = []
  stopMeta.value = {}
  manualStopOrder.value = []

  for (const stop of orderedStops) {
    if (stop.kind === 'technical') {
      technicalStops.value.push({
        ...stop,
        orders: (stop.stop_orders || stop.stopOrders || []).map((so) => so.order_id).filter(Boolean),
        orders_count: (stop.stop_orders || stop.stopOrders || []).length,
      })
      manualStopOrder.value.push(`tech:${stop.id}`)
      continue
    }

    const customerVisitIndex = stop.customer_visit_index ?? 1
    const key = `customer:${stop.customer_id}:${customerVisitIndex}`
    manualStopOrder.value.push(key)
    stopMeta.value[key] = {
      description: stop.description ?? '',
      address_text: stop.address_text ?? '',
    }
  }

  technicalStopCounter.value = technicalStops.value.length

  syncVehicleDerivedStateFromCurrentSelection()
}

// Set up event listeners on mount and clean up on unmount
onMounted(async () => {
  hydrateFromJourney()
  await nextTick()
  savedJourneyPayload.value = JSON.stringify(buildJourneyPayload())

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
  if (configurationReady.value && messages.length) alert(messages.join('\n'))
}


</script>


<template>
<AppPage :title="`Modifica viaggio #${props.journey.id}`" :fluid="extendedMapMode && mapMode === 'split'">
<template #back><AppAction @click="goBackToOrders"><font-awesome-icon :icon="['fas','arrow-left']" aria-hidden="true" />Torna ai viaggi</AppAction></template>
<template #actions><JourneyPlanningActions v-model:map-mode="mapMode" :map-open="extendedMapMode" @toggle-map="toggleExtendedMapMode" /></template>
<!-- MAIN CONTAINER -->
<div class="flex min-w-0 flex-col gap-4 xl:flex-row">
  <!-- LIST HALF -->
  <div ref="mainPaneRef" class="relative min-w-0 flex-1">
    <RegistryForm :form="form" @submit="updateJourney">

      <PlanningColumns>



        <template #main>

          <!-- TESTATA TOTALI -->
          <div v-if="!configurationReady" class="mb-3 rounded-lg border border-warning p-3 text-sm" role="status">
            <p class="font-semibold">Configura il mezzo prima di caricare gli ordini</p>
            <p v-for="(message, field) in configuration.issues" :key="field">{{ message }}</p>
            <DetachedTrailerOrders v-if="!form.trailer_id && (listRimorchio.length || listRiempimento.length)"
              :can-move-to-truck="configuration.ready" :busy="form.processing"
              @move-to-truck="if (configuration.ready && !form.processing) { listMotrice.push(...listRimorchio.splice(0), ...listRiempimento.splice(0)); calculateTotalLoad() }"
              @return-to-orders="if (!form.processing) { listOrdini.push(...listRimorchio.splice(0), ...listRiempimento.splice(0)); calculateTotalLoad() }"
            />
          </div>
          <JourneyVehiclePanels
            :vehicles="props.vehicles"
            :trailers="props.trailers"
            :cargos="props.cargos"
            :form="form"
            :trailer-enabled="trailerEnabled"
            :set-preferred-trailer-and-cargo="setPreferredTrailerAndCargo"
            :check-cargo="checkCargo"
            :configuration="configuration"
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
          <div class="grid min-w-0 grid-cols-1 gap-3 lg:grid-cols-3 mt-4">
            <div class="min-w-0">

              <h3 class="font-semibold">Carico Motrice</h3>
              <draggable :disabled="form.processing || !configurationReady"
                v-model="listMotrice"
                group="orders"
                @start="drag=true"
                @end="drag=false"
                @change="calculateTotalLoad"
                item-key="id"
                class="min-h-24 space-y-2 rounded-xl border border-base-300 bg-base-200 p-2"
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
            <div class="min-w-0">
              <h3 class="font-semibold">Carico Rimorchio</h3>
              <draggable :disabled="form.processing || !configurationReady"
                v-if="trailerEnabled || listRimorchio.length || listRiempimento.length"
                v-model="listRimorchio"
                group="orders"
                @start="drag=true"
                @end="drag=false"
                @change="calculateTotalLoad"
                item-key="id"
                class="min-h-24 space-y-2 rounded-xl border border-base-300 bg-base-200 p-2"
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
            <div class="min-w-0">
              <h3 class="font-semibold">Carico a riempimento</h3>

              <draggable :disabled="form.processing || !configurationReady"
                v-model="listRiempimento"
                v-if="trailerEnabled || listRimorchio.length || listRiempimento.length"
                group="orders"
                @start="drag=true"
                @end="drag=false"
                @change="calculateTotalLoad"
                item-key="id"
                class="min-h-24 space-y-2 rounded-xl border border-base-300 bg-base-200 p-2"
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
          <OpenOrdersFilter v-model="openOrderQuery" />
            <p v-if="listOrdini.length && !visibleOpenOrderIds.size" class="py-2 text-sm" role="status">Nessun ordine aperto corrisponde alla ricerca.</p>
            <draggable :disabled="form.processing || !configurationReady"
              :list="listOrdini"
              group="orders"
              @start="drag=true"
              @end="drag=false"
              tag="div"
              class="grid min-h-24 grid-cols-1 gap-2 rounded-xl border border-base-300 bg-base-200 p-2 lg:grid-cols-2"
              item-key="id"
            >
              <template #item="{element, index}">
                <div v-show="visibleOpenOrderIds.has(element.id)" class="masonry-item">
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

          <JourneyDocumentGenerationPanel
            :journey-id="props.journey.id"
            :selected-orders="selectedJourneyOrders"
            :disabled="journeyDirty || form.processing"
            @busy="documentsBusy = $event"
          />

        </template>

        <!-- FLOATING INFO OBJECT -->
        <template #sidebar>
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
            :submit-label="'Salva viaggio'"
            :submit-disabled="saveDisabled"
            @dragging="isDraggingStops = $event"
            @open-stops-manager="openStopsDrawer"
          />
        </template>

      </PlanningColumns>

    </RegistryForm>

    <AuditCollapse
      :audits="props.audits || []"
      :is-admin="Boolean(page.props.user?.is_admin)"
      :field-labels="auditFieldLabels"
    />

    <StopManagerDrawer
      v-if="stopsDrawerOpen"
      v-model:stopOrder="manualStopOrder"
      :stops-by-key="stopsByKey"
      :technical-actions="props.journeyStopActions"
      @add-technical-stop="addTechnicalStop"
      @remove-technical-stop="removeTechnicalStop"
      @update-technical-stop-coords="updateTechnicalStopCoords"
      @update-stop-fields="updateStopFields"
      @dragging="isDraggingStops = $event"
      @close="stopsDrawerOpen = false"
    />
  </div>
  <!-- END-OF LIST HALF -->

  <!-- MAP HALF -->
  <PlanningMapPanel :open="extendedMapMode" :mode="mapMode" @close="extendedMapMode = false">
    <JourneyMap
      :orders="props.orders"
      v-model:listMotrice = "listMotrice"
      v-model:listRimorchio = "listRimorchio"
      v-model:listRiempimento = "listRiempimento"
    >

    </JourneyMap>
  </PlanningMapPanel>
  <!-- END-OF MAP HALF -->

</div>

</AppPage>
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



    .text {
      margin: 20px;
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
