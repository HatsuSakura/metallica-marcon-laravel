// ExplosionEditor.vue
<template>
  <div class="flex flex-col gap-3">
    <!-- Header con budget -->
    <div class="flex items-center justify-between">
      <div class="font-medium">Controllo peso totale</div>
      <div class="text-sm opacity-70">
        Padre:
        <span class="badge badge-ghost">{{ parentNet.toFixed(3) }} kg</span>
        Figli:
        <span class="badge badge-ghost">{{ totalChildren.toFixed(3) }} kg</span>
        Residuo:
        <span class="badge" :class="residual >= 0 ? 'badge-success' : 'badge-error'">
          {{ residual.toFixed(3) }} kg
        </span>
      </div>
    </div>
    <progress class="progress w-full" :value="progressValue" max="100"></progress>

    <!-- Toolbar -->
    <div class="flex flex-wrap items-center gap-2 py-2">
      <button class="btn btn-sm btn-primary" @click="addRoot">+ Aggiungi Elemento/Componente</button>
    </div>

    <!-- Tree -->
    <div class="rounded-lg border border-base-300">
      <div
        v-for="row in flatRows"
        :key="row.id"
        class="flex flex-row items-center gap-2 px-3 py-2 border-t border-base-200 w-full"
      >
        <!-- sinistra: indent + toggle + combo + tipo + peso -->
        <div class="flex justify-start gap-2 items-center w-2/3" :style="{ paddingLeft: `${row.depth * 16}px` }">
          <button
            v-if="row.childrenCount"
            class="btn btn-ghost btn-xs"
            @click.stop="toggleCollapse(row.id)"
            :title="row.collapsed ? 'Espandi figli' : 'Comprimi figli'"
          >
            <font-awesome-icon :icon="row.collapsed ? ['fas','chevron-right'] : ['fas','chevron-down']" />
          </button>
          <button v-else class="btn btn-ghost btn-xs btn-disabled opacity-50">
            <font-awesome-icon :icon="['fas','circle']" class="fa-2xs" />
          </button>

          <div class="w-64">
            <AutocompleteCombo
              :items="catalog"
              v-model="getNode(row.id)._selected"
              :initial-text="row.displayName"
              :allow-types="['material','component']"
              placeholder="Materiale o componente…"
              @select="picked => onPickCatalog(row.id, picked)"
              @enter="text   => onFreeText(row.id, text)"
            />
          </div>

          <div>
            <div class="badge" :class="row.isComponent ? 'badge-info' : (row.isMaterial ? 'badge-success' : 'badge-ghost')">
              {{ row.catalog_type || '—' }}
            </div>
          </div>

          <div class="flex items-center gap-2 w-32">
            <template v-if="row.isComponent">
              <span class="badge badge-outline w-full justify-center">
                {{ formatKg(derivedWeights.get(row.id) ?? 0) }} kg
              </span>
            </template>
            <template v-else>
              <input
                type="number"
                step="0.001"
                min="0"
                class="input input-sm input-bordered w-full"
                :value="row.weight_net ?? ''"
                @input="onWeightInput(($event.target).value, row.id)"
              />
              <label class="text-xs opacity-70">Kg</label>
            </template>
          </div>
        </div>

        <!-- destra: azioni -->
        <div class="flex justify-end gap-2 ml-auto items-center w-1/3 pr-4">
          <div v-if="row.isComponent" class="flex justify-between gap-2 items-center">
            <button class="btn btn-ghost" @click="addChild(row.id)" title="Aggiungi figlio">
              <span v-if="row.childrenCount === 0">esplodi</span>
              <span v-else>+ riga</span>
            </button>

            <div class="flex items-center gap-2">
              <select class="select select-sm" :value="getNode(row.id)._selectedRecipeId ?? ''" @change="e => onRowRecipeChange(row.id, e)">
                <option value="">Sel. ricetta</option>
                <option v-for="r in recipes" :key="r.id" :value="r.id">
                  {{ r.name }}<span v-if="r.version"> (v{{ r.version }})</span>
                </option>
              </select>

              <button
                class="btn btn-ghost btn-sm"
                :class="getNode(row.id)._selectedRecipeId ? '' : 'btn-disabled'"
                :disabled="!getNode(row.id)._selectedRecipeId"
                @click="applyRecipeHere(row.id)"
                title="Applica ricetta a questa riga"
              >
                Applica
              </button>
            </div>
          </div>

          <button class="btn btn-error btn-circle" @click="removeRow(row.id)">
            <font-awesome-icon :icon="['fas', 'trash']" />
          </button>
        </div>
      </div>

      <div v-if="!flatRows.length" class="p-4 text-sm opacity-70">
        Nessun materiale/componente presente.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import AutocompleteCombo from '@/Components/AutocompleteCombo.vue'

const props = defineProps({
  explosions: { type: Array, required: true }, // albero iniziale
  catalog:    { type: Array, required: true },
  recipes:    { type: Array, default: () => [] },
  parentNet:  { type: Number, default: 0 },
})

const emit = defineEmits(['update'])

const deepClone = (x) => JSON.parse(JSON.stringify(x))

/* --------- init: prendo le explosions una sola volta --------- */
function normalizeIncomingTree(list, parentId = null) {
  const src = Array.isArray(list) ? list : []
  return src.map(n => {
    const rawChildren =
      Array.isArray(n.children) ? n.children
      : Array.isArray(n.children_recursive) ? n.children_recursive
      : Array.isArray(n.childrenRecursive) ? n.childrenRecursive
      : []

    const ci = n.catalog_item ?? n.catalogItem ?? null

    return {
      id: n.id ?? null,
      parent_explosion_id: n.parent_explosion_id ?? parentId ?? null,

      catalog_item_id: n.catalog_item_id ?? ci?.id ?? null,
      catalog_item: ci ? { ...ci } : (n.catalog_item_id ? { id: n.catalog_item_id } : null),
      _selected: n._selected ?? (ci ? { ...ci } : null),

      weight_net: n.weight_net ?? null,
      notes: n.notes ?? null,

      recipe_id: n.recipe_id ?? null,
      _selectedRecipeId: n._selectedRecipeId ?? n.recipe_id ?? null,

      explosion_source: n.explosion_source ?? 'ad_hoc',
      _collapsed: !!n._collapsed,

      children: normalizeIncomingTree(rawChildren, n.id ?? null),
    }
  })
}

// 👇 editor “own the state”: nessun watcher su props.explosions
const nodes = ref(normalizeIncomingTree(props.explosions ?? []))

/* --------- helpers --------- */
function isComponentNode(n) { return n?.catalog_item?.type === 'component' || n?._selected?.type === 'component' }
function isMaterialNode(n)  { return n?.catalog_item?.type === 'material'  || n?._selected?.type === 'material' }

function findNode(arr, id) {
  for (const n of arr) {
    if (n.id === id) return n
    if (n.children?.length) {
      const hit = findNode(n.children, id)
      if (hit) return hit
    }
  }
  return null
}
function getNode(id) { return findNode(nodes.value, id) }

/* --------- flatten per rendering --------- */
const flatRows = computed(() => {
  const out = []
  const walk = (arr, depth = 0) => {
    for (const n of arr) {
      const collapsed = !!n._collapsed
      const children = Array.isArray(n.children) ? n.children : []
      out.push({
        id: n.id,
        depth,
        parent_explosion_id: n.parent_explosion_id ?? null,
        catalog_item_id: n.catalog_item_id ?? null,
        catalog_type: n.catalog_item?.type || n._selected?.type || '',
        weight_net: n.weight_net ?? null,
        displayName: n.catalog_item?.name || n._selected?.name || '',
        collapsed,
        childrenCount: children.length,
        isComponent: isComponentNode(n),
        isMaterial: isMaterialNode(n),
      })
      if (!collapsed && children.length) walk(children, depth + 1)
    }
  }
  walk(nodes.value, 0)
  return out
})

/* --------- budget/progress --------- */
const parentNet     = computed(() => Number(props.parentNet ?? 0))
const sumMaterials  = (arr) => arr.reduce((acc, n) => acc + (isMaterialNode(n) ? (Number(n.weight_net) || 0) : 0) + (n.children?.length ? sumMaterials(n.children) : 0), 0)
const totalChildren = computed(() => sumMaterials(nodes.value))
const residual      = computed(() => parentNet.value - totalChildren.value)
const progressValue = computed(() => parentNet.value <= 0 ? 0 : Math.max(0, Math.min(100, (totalChildren.value / parentNet.value) * 100)))

/* --------- CRUD --------- */
let tempId = -1
function makeLocalNode(parentId = null, ci = null) {
  return {
    id: tempId--,
    parent_explosion_id: parentId,
    catalog_item_id: ci?.id ?? null,
    catalog_item: ci ? { ...ci } : null,
    _selected: ci ? { ...ci } : null,
    weight_net: null,
    notes: null,
    explosion_source: 'ad_hoc',
    _collapsed: false,
    children: [],
  }
}

function addRoot() {
  nodes.value.push(makeLocalNode(null))
  pushUpdate()
}

function addChild(parentId) {
  const n = getNode(parentId)
  if (!n) return
  if (!isComponentNode(n)) {
    // se non ha tipo, cerca di forzare a “component” così può avere figli
    if (!n._selected && !n.catalog_item) {
      n._selected = { id: null, name: '', type: 'component' }
    } else {
      return
    }
  }
  n.children = n.children || []
  n.children.push(makeLocalNode(n.id, null))
  n._collapsed = false
  pushUpdate()
}

function removeNodeInPlace(arr, id) {
  const idx = arr.findIndex(n => n.id === id)
  if (idx >= 0) { arr.splice(idx, 1); return true }
  for (const n of arr) {
    if (n.children?.length && removeNodeInPlace(n.children, id)) return true
  }
  return false
}
function removeRow(rowId) {
  removeNodeInPlace(nodes.value, rowId)
  pushUpdate()
}

/* --------- edit fields --------- */
function onPickCatalog(rowId, picked) {
  const n = getNode(rowId)
  if (!n) return
  n.catalog_item_id = picked?.id ?? null
  n.catalog_item    = picked ? { ...picked } : null
  n._selected       = picked || null

  if (picked?.type === 'material') {
    n.children = []
    n._collapsed = false
  }
  pushUpdate()
}

function onFreeText(rowId, text) {
  const n = getNode(rowId)
  if (!n) return
  n._selected       = text ? { id: null, name: text, type: 'material' } : null
  n.catalog_item    = null
  n.catalog_item_id = null
  pushUpdate()
}

function onWeightInput(val, rowId) {
  const n = getNode(rowId)
  if (!n || isComponentNode(n)) return
  const parsed = val === '' ? null : Math.max(0, Number(val))
  n.weight_net = Number.isFinite(parsed) ? parsed : null
  pushUpdate()
}

function toggleCollapse(rowId) {
  const n = getNode(rowId)
  if (!n) return
  n._collapsed = !n._collapsed
  pushUpdate() // così il parent mantiene lo stesso stato di UI
}

/* --------- ricette per riga --------- */
function onRowRecipeChange(rowId, e) {
  const n = getNode(rowId)
  if (!n) return
  const rid = e?.target?.value ? Number(e.target.value) : null
  n._selectedRecipeId = rid
  pushUpdate()
}

async function applyRecipeHere(rowId) {
  const n = getNode(rowId)
  if (!n || !isComponentNode(n)) return
  const rid = n._selectedRecipeId
  if (!rid) return

  const r = await axios.get(route('api.recipes.tree', { recipe: rid }))
  const tree  = Array.isArray(r.data) ? r.data : []
  const built = buildNodesFromRecipeItems(tree, n.id)
  const current = Array.isArray(n.children) ? n.children : []
  n.children = current.filter(ch => ch.explosion_source !== 'recipe').concat(built)
  n._collapsed = false
  pushUpdate()
}

function buildNodesFromRecipeItems(items, parentId) {
  return (items || []).map(ri => {
    const id = tempId--
    const ci = ri.catalog_item || null
    const node = {
      id,
      parent_explosion_id: parentId,
      catalog_item_id: ci?.id ?? null,
      catalog_item: ci ? { ...ci } : null,
      _selected: ci ? { ...ci } : null,
      weight_net: ri.weight_net ?? null,
      explosion_source: 'recipe',
      notes: null,
      _collapsed: false,
      children: [],
    }
    if (ri.children?.length) {
      node.children = buildNodesFromRecipeItems(ri.children, id)
    }
    return node
  })
}

/* --------- derivati --------- */
const derivedWeights = computed(() => {
  const map = new Map()
  const sumNode = (n) => {
    if (Array.isArray(n.children) && n.children.length) {
      const s = n.children.reduce((acc, c) => acc + sumNode(c), 0)
      map.set(n.id, s)
      return s
    }
    const leaf = Number(n.weight_net) || 0
    map.set(n.id, leaf)
    return leaf
  }
  for (const root of nodes.value) sumNode(root)
  return map
})

function formatKg(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n.toFixed(3) : '0.000'
}

/* --------- emit --------- */
function pushUpdate() {
  // emetti **sempre** la versione serializzabile (niente proxy)
  emit('update', deepClone(nodes.value))
}
</script>
