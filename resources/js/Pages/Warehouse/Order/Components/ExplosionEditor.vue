<template>
  <div class="flex flex-col gap-3">
    <!-- HEADER: budget / residuo -->
    <div class="flex items-center justify-between">
      <div class="font-medium">Controllo peso totale</div>
      <div class="text-sm opacity-70">
        Netto pesato:
        <span class="badge badge-ghost">{{ parentNet.toFixed(3) }} kg</span>
        Materiali/Componenti:
        <span class="badge badge-ghost">{{ totalChildren.toFixed(3) }} kg</span>
        Differenza:
        <span class="badge" :class="residual >= 0 ? 'badge-success' : 'badge-error'">
          {{ residual.toFixed(3) }} kg
        </span>
      </div>
    </div>

    <!-- PROGRESS -->
    <progress class="progress w-full" :value="progressValue" max="100"></progress>

    <!-- TOOLBAR -->
    <div class="flex flex-wrap items-center gap-2 py-2">
      <button class="btn btn-sm btn-primary" @click="emitAddRoot">+ Aggiungi Materiale/Componente</button>
    </div>

    <!-- TREE (flat render + indent) -->
    <div class="rounded-lg border border-base-300">
      <div
        v-for="row in flatRows"
        :key="row.id"
        class="flex flex-row items-center gap-2 px-3 py-2 border-t border-base-200 w-full"
      >
        <!-- SINISTRA: indent + toggle + autocomplete + tipo + peso -->
        <div class="flex justify-start gap-2 items-center w-2/3" :style="{ paddingLeft: `${row.depth * 16}px` }">
          <!-- toggle collapse (solo se ci sono figli) -->
          <button
            v-if="row.childrenCount"
            class="btn btn-ghost btn-xs"
            @click.stop="emitToggleCollapse(row)"
            :title="row.collapsed ? 'Espandi figli' : 'Comprimi figli'"
          >
            <font-awesome-icon :icon="row.collapsed ? ['fas','chevron-right'] : ['fas','chevron-down']" />
          </button>
          <button v-else class="btn btn-ghost btn-xs btn-disabled opacity-50">
            <font-awesome-icon :icon="['fas','circle']" class="fa-2xs" />
          </button>

          <!-- Autocomplete: NON mutiamo, emettiamo patch; model-value serve solo a mostrare lo stato attuale -->
          <div class="w-64">
            <AutocompleteCombo
              :items="catalog"
              :model-value="row.selected || null"
              :initial-text="row.displayName"
              :allow-types="['material','component']"
              placeholder="Materiale o componente…"
              @select="picked => emitUpdateCatalog(row, picked)"
              @enter="text   => emitFreeText(row, text)"
            />
          </div>

          <!-- Tipo -->
          <div>
            <div class="badge" :class="row.isComponent ? 'badge-info' : (row.isMaterial ? 'badge-success' : 'badge-ghost')">
              {{ row.catalogType || '—' }}
            </div>
          </div>

          <!-- Peso (input solo per materiali; per component badge con somma figli) -->
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
                :value="row.weightNet ?? ''"
                @input="onWeightInput(($event.target).value, row)"
              />
              <label class="text-xs opacity-70">Kg</label>
            </template>
          </div>
        </div>

        <!-- DESTRA: azioni -->
        <div class="flex justify-end gap-2 ml-auto items-center w-1/3 pr-4">
          <div v-if="row.isComponent" class="flex justify-between gap-2 items-center">
            <!-- + figlio -->
            <button class="btn btn-ghost" @click="emitAddChild(row)" title="Aggiungi figlio">
              <span v-if="row.childrenCount === 0">esplodi</span>
              <span v-else>+ riga</span>
            </button>

            <!-- Ricette -->
            <div class="flex items-center gap-2">
              <select class="select select-sm" :value="row.selectedRecipeId ?? ''" @change="e => emitSetRecipe(row, e)">
                <option value="">Sel. ricetta</option>
                <option v-for="r in recipes" :key="r.id" :value="r.id">
                  {{ r.name }}<span v-if="r.version"> (v{{ r.version }})</span>
                </option>
              </select>

              <button
                class="btn btn-ghost btn-sm"
                :class="row.selectedRecipeId ? '' : 'btn-disabled'"
                :disabled="!row.selectedRecipeId"
                @click="emitApplyRecipe(row)"
                title="Applica ricetta a questa riga"
              >
                Applica
              </button>
            </div>
          </div>

          <button class="btn btn-error btn-circle" @click="emitRemove(row)">
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
/**
 * ExplosionEditor (dummy-child, patch-based)
 *
 * ➤ SSOT nel parent: questo componente NON detiene lo stato dell’albero,
 *   non effettua fetch, non muta props. Si occupa solo di:
 *   - render dell’albero (flatten su props.tree)
 *   - emettere eventi granulari di patch (add/remove/update/toggle/apply recipe)
 *
 * ➤ Contratto:
 *   Props:
 *     - tree:    ExplosionNode[]   (preferito)  — albero normalizzato con .children
 *     - explosions: ExplosionNode[] (deprecato) — fallback per compatibilità (lettura sola)
 *     - catalog:    CatalogItem[]   (id, name, type: 'material'|'component')
 *     - recipes:    RecipeRef[]     ricette pronte: {id,name,version,catalog_item,nodes:[...]}
 *     - parentNet:  number          (peso netto del padre)
 *   Emits:
 *     - explosion:add-root                       ()                          // crea un root vuoto
 *     - explosion:add-child                      ({ parentId })              // crea figlio del parentId
 *     - explosion:remove                         ({ id })                    // elimina nodo
 *     - explosion:update-node                    ({ id, patch, hints? })     // es. { weight_net }, { catalog_* }, ecc.
 *     - explosion:toggle-collapse                ({ id, collapsed })         // persistiamo _collapsed nel parent
 *     - explosion:set-recipe                     ({ id, recipeId })          // seleziona ricetta
 *     - explosion:apply-recipe                   ({ id, recipeId })          // parent fa fetch + sostituisce figli
 *
 * ➤ Note:
 *   - Le “regole” (es: se diventa material → svuota figli) NON vengono applicate qui,
 *     ma il child può inviare “hints” al parent (es. { normalizeToMaterialLeaf: true }).
 *   - Le somme/derived weights sono calcolate qui SOLO per visualizzare badge/pbar.
 *     Nessuna mutazione viene fatta lato child.
 */

import { computed } from 'vue'
import AutocompleteCombo from '@/Components/AutocompleteCombo.vue'

/* ======================
 * Props / Emits
 * ====================== */
const props = defineProps({
  // 🔹 Nuovo nome (preferito)
  tree:       { type: Array, required: false, default: null },
  // 🔸 Fallback per retro-lettura (non mutato)
  explosions: { type: Array, required: false, default: null },

  catalog:    { type: Array, required: true },
  recipes:    { type: Array, default: () => [] }, // iniettate dal controller ad es. [{ id, name, version, catalog_item, nodes:[...] }, ...]
  parentNet:  { type: Number, default: 0 },
})

const emit = defineEmits([
  'explosion:add-root',
  'explosion:add-child',
  'explosion:remove',
  'explosion:update-node',
  'explosion:toggle-collapse',
  'explosion:set-recipe',
  'explosion:apply-recipe',
])

/* ======================
 * Helpers puri
 * ====================== */

/** Ritorna la sorgente d’albero (preferisci props.tree; fallback props.explosions) */
const inputTree = computed(() => {
  if (Array.isArray(props.tree)) return props.tree
  if (Array.isArray(props.explosions)) return props.explosions
  return []
})

/** Tipo nodo */
function isComponentNode(n) {
  const t = n?.catalog_item?.type ?? n?._selected?.type ?? n?.type
  return t === 'component'
}
function isMaterialNode(n) {
  const t = n?.catalog_item?.type ?? n?._selected?.type ?? n?.type
  return t === 'material'
}

/** Flatten per render (non muta mai) */
const flatRows = computed(() => {
  const out = []
  const walk = (list, depth = 0) => {
    for (const n of (Array.isArray(list) ? list : [])) {
      const children = Array.isArray(n.children) ? n.children : []
      out.push({
        id: n.id,
        depth,
        childrenCount: children.length,
        collapsed: !!n._collapsed,

        // dati utili al render
        catalogType: n.catalog_item?.type || n._selected?.type || '',
        displayName: n.catalog_item?.name || n._selected?.name || '',
        selected:    n._selected ?? n.catalog_item ?? null,
        selectedRecipeId: n._selectedRecipeId ?? n.recipe_id ?? null,
        weightNet: n.weight_net ?? null,

        // comodi per classi UI
        isComponent: isComponentNode(n),
        isMaterial:  isMaterialNode(n),
      })
      if (!n._collapsed && children.length) walk(children, depth + 1)
    }
  }
  walk(inputTree.value, 0)
  return out
})

/** Somma pesi materiali (foglie) per budget */
function sumMaterials(list) {
  return (list || []).reduce((acc, n) => {
    const self = isMaterialNode(n) ? (Number(n.weight_net) || 0) : 0
    const kids = n.children?.length ? sumMaterials(n.children) : 0
    return acc + self + kids
  }, 0)
}

/* ======================
 * Derived / UI metrics
 * ====================== */

const parentNet     = computed(() => Number(props.parentNet ?? 0))
const totalChildren = computed(() => sumMaterials(inputTree.value))
const residual      = computed(() => parentNet.value - totalChildren.value)
const progressValue = computed(() => {
  if (parentNet.value <= 0) return 0
  const pct = (totalChildren.value / parentNet.value) * 100
  return Math.max(0, Math.min(100, pct))
})

/** Mappa id → peso derivato (component = somma figli; materiale = proprio peso) */
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

  for (const root of inputTree.value) sumNode(root)
  return map
})

/* ======================
 * UI formatters
 * ====================== */
function formatKg(v) {
  const n = Number(v)
  return Number.isFinite(n) ? n.toFixed(3) : '0.000'
}

/* ======================
 * Emitters (patch-based)
 * ====================== */

/** Aggiungi root vuoto */
function emitAddRoot() {
  emit('explosion:add-root')
}

/** Aggiungi figlio ad un nodo (il parent validerà che sia un component) */
function emitAddChild(row) {
  emit('explosion:add-child', { parentId: row.id })
}

/** Rimuovi un nodo */
function emitRemove(row) {
  emit('explosion:remove', { id: row.id })
}

/** Toggle collapsed (persistito nel parent) */
function emitToggleCollapse(row) {
  emit('explosion:toggle-collapse', { id: row.id, collapsed: !row.collapsed })
}

/** Selezione da catalogo: invio patch + hint per normalizzare “materiale=leaf” */
function emitUpdateCatalog(row, picked) {
  const patch = {
    catalog_item_id: picked?.id ?? null,
    catalog_item: picked ? { ...picked } : null,
    _selected: picked || null,
  }
  // Hint: se diventa 'material' vogliamo che il parent garantisca "foglia" (children = []).
  const hints = picked?.type === 'material' ? { normalizeToMaterialLeaf: true } : undefined
  emit('explosion:update-node', { id: row.id, patch, hints })
}

/** Inserimento free-text: trattiamo come materiale “ad hoc” (id null) */
function emitFreeText(row, text) {
  const patch = {
    _selected:       text ? { id: null, name: text, type: 'material' } : null,
    catalog_item:    null,
    catalog_item_id: null,
  }
  const hints = text ? { normalizeToMaterialLeaf: true } : undefined
  emit('explosion:update-node', { id: row.id, patch, hints })
}

/** Input peso (solo materiali); il parent può comunque validare lato suo */
function onWeightInput(val, row) {
  const parsed = val === '' ? null : Math.max(0, Number(val))
  const weight_net = Number.isFinite(parsed) ? parsed : null
  emit('explosion:update-node', { id: row.id, patch: { weight_net } })
}

/** Cambio selezione ricetta (solo set id) */
function emitSetRecipe(row, e) {
  const rid = e?.target?.value ? Number(e.target.value) : null
  emit('explosion:set-recipe', { id: row.id, recipeId: rid })
}

/** Applica ricetta (il parent farà fetch e sostituirà i figli) */
function emitApplyRecipe(row) {
  if (!row.selectedRecipeId) return
  emit('explosion:apply-recipe', { id: row.id, recipeId: row.selectedRecipeId })
}
</script>
