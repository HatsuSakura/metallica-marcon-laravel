<!-- resources/js/Pages/Admin/Recipes/RecipeNodeEditor.vue -->
<script setup>
import AppAction from '@/Components/UI/AppAction.vue'
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import axios from 'axios'
import { useStore } from 'vuex'
import AutocompleteCombo from '@/Components/AutocompleteCombo.vue'

const props = defineProps({
  recipeId: { type: Number, required: true },
  catalog:  { type: Array,  required: true },   // [{id,name,type}, ...] pre-caricato
  nodes:    { type: Array,  default: () => []}, // unica prop con le radici già caricate
})

const store = useStore()

/** Stato locale (buffer) */
const roots = ref([])     // [{ id?, catalog_item_id, catalog_item:{id,name,type}, children:[] }]
const busy = ref(false)
const baseline = ref('[]')
const savedRoots = ref([])
const treeSnapshot = nodes => JSON.stringify((nodes || []).map(n => ({id:n.catalog_item_id ?? n.catalog_item?.id ?? null, unknown:!n.catalog_item_id ? (n._freeText || n._selected?.name || '') : ''})))
const hasChanges = computed(() => treeSnapshot(roots.value) !== baseline.value)
const dirty = ref(false)  // flag modifiche locali

const catalogMap = computed(() => {
  const m = new Map()
  ;(props.catalog || []).forEach(ci => m.set(ci.id, ci))
  return m
})

/* Registro dei ref ai component Autocomplete (per riga) */
const comboRefs = ref(new Map())
function registerComboRef(id, el) {
  if (el) comboRefs.value.set(id, el)
  else comboRefs.value.delete(id)
}

function deepClone(x) {
  return JSON.parse(JSON.stringify(x ?? null))
}

function normalizeNode(n) {
  const id = n?.id ?? (tempId--)               // preserva id o usa temp
  const ciId = n?.catalog_item_id ?? n?.catalog_item?.id ?? null
  const ciObj = n?.catalog_item ?? (ciId ? catalogMap.value.get(ciId) || null : null)

  return {
    id,
    catalog_item_id: ciId,
    catalog_item: ciObj ? { ...ciObj } : null,
    children: Array.isArray(n?.children) ? n.children.map(normalizeNode) : [],
    _selected: ciObj ? { ...ciObj } : null,    // per l’autocomplete
  }
}

function normalizeTree(arr) {
  return (arr || []).map(normalizeNode)
}


/** IdRATA _selected per l’autocomplete dalla lista catalog */
function hydrateSelectedFromCatalog() {
  const byId = new Map((props.catalog || []).map(c => [c.id, c]))
  const walk = (arr = []) => {
    for (const n of arr) {
      if (n.catalog_item_id && !n._selected) {
        n._selected = byId.get(n.catalog_item_id) || n.catalog_item || null
      }
      if (n.children?.length) walk(n.children)
    }
  }
  walk(roots.value)
}

/** Mount & reactive sync da props.nodes */
onMounted(() => {
  roots.value = normalizeTree(props.nodes || [])
  dirty.value = false
  baseline.value = treeSnapshot(roots.value)
  savedRoots.value = deepClone(roots.value)
})

watch(() => props.nodes, (n) => {
  roots.value = normalizeTree(n || [])
  dirty.value = false
  baseline.value = treeSnapshot(roots.value)
  savedRoots.value = deepClone(roots.value)
})


/* --- Helpers tree --- */
let tempId = -1
function makeNode(ci) {
  return {
    id: tempId--,                 // id temporaneo client
    catalog_item_id: ci?.id ?? null,
    catalog_item: ci ? { ...ci } : null,
    _selected: ci ? { ...ci } : null,  // utile per AutocompleteCombo
    children: [],
  }
}

function removeNodeInPlace(arr, id) {
  const idx = arr.findIndex(n => n.id === id)
  if (idx >= 0) { arr.splice(idx, 1); return true }
  for (const n of arr) {
    if (n.children?.length && removeNodeInPlace(n.children, id)) return true
  }
  return false
}

/* --- UI azioni locali --- */
function addRoot() {
  const node = makeNode(null)
  roots.value.push(node)
  dirty.value = true

  // Focus sul nuovo input dopo il render
  nextTick(() => {
    const inst = comboRefs.value.get(node.id)
    inst?.focus?.()
  })
}

async function onSelectCatalog(node, picked) {
  node.catalog_item_id = picked?.id ?? null
  node.catalog_item    = picked ? { ...picked } : null
  node._selected       = picked || null
  node._freeText       = ''  // reset testo libero
  // se MATERIAL -> foglia
  if (picked && picked.type === 'material' && node.children?.length) {
    node.children = []
  }
  // 👇 auto-import solo a video (non salva nulla) se non ha ancora figli
  if (picked?.type === 'component' && (!node.children || node.children.length === 0)) {
    await importDefaultChildren(node)
  }
  dirty.value = true
}

function deleteNode(id) {
  removeNodeInPlace(roots.value, id)
  dirty.value = true
}

/* --- Importa (in memoria) la ricetta predefinita del COMPONENT scelto --- */
async function importDefaultChildren(node) {
  const ci = node.catalog_item
  if (!ci || ci.type !== 'component') return

  // NB: l’endpoint può tornare: { items:[...] } o { nodes:[...] } o direttamente un array
  const r = await axios.get('/api/recipes/default-tree', { params: { catalog_item_id: ci.id } })
  const raw = r.data ?? []
  const arr = Array.isArray(raw) ? raw : (raw.items || raw.nodes || [])

  // costruisci i figli in memoria, risolvendo anche quando arriva solo catalog_item_id
  node.children = buildNodesFromTemplate(arr)
  dirty.value = true
}

function buildNodesFromTemplate(items) {
  return (items || []).map(it => {
    // prova nell’ordine: oggetto completo -> resolve da id -> null
    const resolved =
      it.catalog_item ??
      (it.catalog_item_id ? catalogMap.value.get(it.catalog_item_id) : null) ??
      null

    const n = makeNode(resolved)
    const kids = it.children || it.items || it.nodes || []
    if (kids && kids.length) {
      n.children = buildNodesFromTemplate(kids)
    }
    return n
  })
}

/* --- Validazione e serializzazione --- */
function collectInvalid(nodes) {
  const out = []
  const walk = (arr=[]) => {
    for (const n of arr) {
      if (!n.catalog_item_id) out.push(n)
      if (n.children?.length) walk(n.children)
    }
  }
  walk(nodes)
  return out
}

// --- Serializzazione per sync: SOLO radici, nessun children ---
function serialize(nodes) {
  // inviamo solo [{ catalog_item_id }, ...]
  return (nodes || [])
    .map(n => n?.catalog_item_id ? { catalog_item_id: n.catalog_item_id } : null)
    .filter(Boolean)
}

// dentro <script setup> di RecipeNodeEditor.vue

function onComboCommit(node, text) {
  const clean = (text || '').trim()
  if (!clean) {
    // campo vuoto → niente unknown
    node._selected = null
    node.catalog_item_id = null
    node.catalog_item = null
    dirty.value = true
    return
  }

  // prova a risolvere sul catalogo per nome (case-insensitive)
  const match = (props.catalog || []).find(
    c => c.name.toLowerCase() === clean.toLowerCase()
  )

  if (match) {
    // selezione valida → collega al catalogo
    onSelectCatalog(node, match)
  } else {
    // testo libero → marca come “unknown”
    node._selected = { id: null, name: clean, type: null }
    node.catalog_item_id = null
    node.catalog_item = null
    dirty.value = true
  }
}


async function saveAll() {
  if (busy.value || !hasChanges.value) return
  // 1. Controllo nodi senza catalog_item_id
  const invalid = collectInvalid(roots.value)

  // 2. Controllo nodi sconosciuti (fuori catalogo)
  const unknowns = []
  const walk = (arr=[]) => {
    for (const n of arr) {
      if (isUnknown(n)) unknowns.push(n)
      if (n.children?.length) walk(n.children)
    }
  }
  walk(roots.value)

  if (invalid.length || unknowns.length) {
    alert('Non puoi salvare: ci sono elementi non validi o non presenti in catalogo.')
    return
  }

  const payload = { nodes: serialize(roots.value) }
  busy.value = true
  try {
  const res = await axios.put(route('recipes.nodes.sync', props.recipeId), payload)
  store.dispatch('flash/queueMessage', { type: 'success', text: 'Tutti gli elementi sono stati aggiornati' })
  dirty.value = false
  baseline.value = treeSnapshot(roots.value)
  savedRoots.value = deepClone(roots.value)
  } catch(e) {
    store.dispatch('flash/queueMessage', { type: 'error', text: 'Salvataggio della ricetta non riuscito. Riprova.' })
    console.error(e)
    return
  } finally { busy.value = false }
}


function resetAll() {
  roots.value = normalizeTree(savedRoots.value)
  hydrateSelectedFromCatalog()
  dirty.value = false
}

function onFreeTextInput(node, text) {
  const t = (text || '').trim()
  if (!t) {
    // input svuotato
    node._freeText = ''
    // non tocchiamo catalog_item_id se l’utente aveva già selezionato qualcosa
    return
  }

  // prova match esatto (case-insensitive) su catalogo
  const found = (props.catalog || []).find(ci =>
    ci.name.toLowerCase() === t.toLowerCase()
  )

  if (found) {
    // si comporta come una selezione
    onSelectCatalog(node, found)
    node._freeText = ''
  } else {
    // è testo libero: scollega dal catalogo
    node.catalog_item_id = null
    node.catalog_item    = null
    node._selected       = { name: t }   // mantiene il testo nell’input
    node._freeText       = t
    dirty.value = true
  }
}

function isUnknown(node) {
  // “sconosciuto” se NON c’è un catalog_item_id
  // e c’è del testo libero (_freeText) oppure _selected senza id
  const hasId = !!node?.catalog_item_id
  const freeT = (node?._freeText || '').trim()
  const sel   = node?._selected
  const selFree = sel && !sel.id && (sel.name || '').trim()

  return !hasId && (!!freeT || !!selFree)
}

/*
function isUnknown(node) {
  // testo digitato ma senza collegamento a catalog_item
  console.log(node)
  const name = node?._selected?.name ?? node?.catalog_item?.name ?? ''
  const resp = !!name.trim() && !node?.catalog_item_id
  console.log('isUnknown?', name, node?.catalog_item_id, resp)
  return resp
}
*/

</script>

<template>
  <fieldset :disabled="busy" class="min-w-0 space-y-3">

    <ul class="bg-base-200 rounded-box p-2">
      <li v-if="!roots.length" class="opacity-70">
        Nessun nodo presente. Aggiungi un nodo root e seleziona materiale o componente.
      </li>

      <li v-for="n in roots"
        :key="n.id"
        class="mb-2"
        :class="isUnknown(n) ? 'border border-error bg-error/10' : 'border border-transparent'"
      >
        <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
          <div class="min-w-0 flex-1 basis-48">
            <AutocompleteCombo
              :ref="el => registerComboRef(n.id, el)"
              :items="catalog"
              :allow-types="['material','component']"
              :initial-text="n.catalog_item?.name || ''"
              v-model="n._selected"
              placeholder="Materiale o componente…"
              @select="(picked) => onSelectCatalog(n, picked)"
              @input-text="(txt) => onFreeTextInput(n, txt)"
              @enter="(txt) => onFreeTextInput(n, txt)"
              @commitText="(text) => onComboCommit(n, text)"
            />
            <!-- helper text -->
            <p v-if="isUnknown(n)" class="mt-1 text-xs text-error">
              Materiale/Componente non presente in catalogo
            </p>
          </div>

          <!-- Importa figli dalla ricetta predefinita (solo per component) -->
          <button
            v-if="n.catalog_item?.type === 'component'"
            type="button" class="btn btn-outline min-h-12"
            @click="importDefaultChildren(n)"
          >
          Visualizza sub-componenti
          </button>

          <span v-if="isUnknown(n)" class="badge badge-error badge-outline">non in catalogo</span>


          <AppAction variant="remove" shape="circle" :disabled="busy" :aria-label="`Rimuovi ${n.catalog_item?.name || 'elemento'} dalla ricetta`" @click="deleteNode(n.id)"><font-awesome-icon :icon="['fas','trash-can']" aria-hidden="true" /></AppAction>
        </div>

        <!-- preview figli -->
        <ul v-if="n.children?.length" class="pl-6 mt-2">
          <li v-for="c in n.children" :key="c.id" class="opacity-80 text-sm">
            • {{ c.catalog_item?.name }}
            <ul v-if="c.children?.length" class="pl-4 mt-1">
              <li v-for="g in c.children" :key="g.id">– {{ g.catalog_item?.name }}</li>
            </ul>
          </li>
        </ul>
      </li>
    </ul>

    <div class="flex flex-wrap items-center gap-2 mt-4">
      <AppAction variant="function" :disabled="busy" @click="addRoot">Aggiungi elemento / componente</AppAction>
      <AppAction variant="confirm" :busy="busy" :disabled="!hasChanges" @click="saveAll">Salva composizione ricetta</AppAction>
      <AppAction variant="cancel" :disabled="busy || !hasChanges" @click="resetAll">Annulla modifiche</AppAction>
    </div>


  </fieldset>
</template>
