/** ================================
 *  Explosion reducers (pure)
 *  Schema nodo normalizzato suggerito:
 *  {
 *    id: number|string,                      // anche temp id negativo lato FE
 *    parent_explosion_id: number|string|null,
 *    catalog_item_id: number|null,
 *    catalog_item: { id, name, type }|null,  // opzionale ma utile per UI
 *    _selected: { id, name, type }|null,     // solo UI; tolto in normalizzazione API
 *    weight_net: number|null,                // SOLO per 'material'
 *    notes: string|null,
 *    recipe_id: number|null,                 // collegamento ricetta
 *    _selectedRecipeId: number|null,         // solo UI; tolto in normalizzazione API
 *    explosion_source: 'ad_hoc'|'recipe',    // tracciamento origine
 *    _collapsed: boolean,                    // stato UI del toggle
 *    children: ExplosionNode[]
 *  }
 *  ================================= */

/** Deep clone safe per strutture semplici */
export function cloneTree(tree) {
  return JSON.parse(JSON.stringify(Array.isArray(tree) ? tree : []))
}

/** Factory di temp-id (negativi) per nuovi nodi FE */
export function makeTempIdFactory(start = -1) {
  let next = start
  return () => next--           // ogni call → -1, -2, -3...
}

/** Walk utility */
function walk(list, fn, parent = null) {
  for (let i = 0; i < list.length; i++) {
    const n = list[i]
    fn(n, parent, i, list)
    if (Array.isArray(n.children) && n.children.length) {
      walk(n.children, fn, n)
    }
  }
}

/** Trova nodo + parent + index (prima occorrenza) */
export function findNode(tree, id) {
  let hit = null
  walk(tree, (n, parent, index, siblings) => {
    if (hit) return
    if (String(n.id) === String(id)) {
      hit = { node: n, parent, index, siblings }
    }
  })
  return hit // { node, parent, index, siblings } | null
}

/** Crea un nodo “vuoto” */
export function makeNode({ id, parentId = null, catalog = null, source = 'ad_hoc' } = {}) {
  const ci = catalog ? { ...catalog } : null
  return {
    id,
    parent_explosion_id: parentId,
    catalog_item_id: ci?.id ?? null,
    catalog_item: ci,
    _selected: ci,
    weight_net: null,
    notes: null,
    recipe_id: null,
    _selectedRecipeId: null,
    explosion_source: source,
    _collapsed: false,
    children: [],
  }
}

/** Aggiunge un root */
export function addRoot(tree, nextId) {
  const t = cloneTree(tree)
  t.push(makeNode({ id: nextId() }))
  return t
}

/** Aggiunge un figlio al parentId (se non esiste → no-op) */
export function addChild(tree, parentId, nextId) {
  const t = cloneTree(tree)
  const hit = findNode(t, parentId)
  if (!hit) return t

  hit.node.children = hit.node.children || []
  hit.node.children.push(makeNode({ id: nextId(), parentId: hit.node.id }))
  hit.node._collapsed = false
  return t
}

/** Rimuove nodo (e subtree) */
export function removeNode(tree, id) {
  const t = cloneTree(tree)
  const hit = findNode(t, id)
  if (!hit) return t
  hit.siblings.splice(hit.index, 1)
  return t
}

/** Aggiorna un nodo con una patch parziale */
export function updateNode(tree, id, patch = {}, hints = {}) {
  const t = cloneTree(tree)
  const hit = findNode(t, id)
  if (!hit) return t

  Object.assign(hit.node, patch)

  // hint di normalizzazione: materiale -> foglia
  if (hints.normalizeToMaterialLeaf) {
    hit.node.children = []
    hit.node._collapsed = false
    // opzionale: se forziamo materiale, weight_net resta come da patch
  }
  return t
}

/** Toggle collapsed persistito */
export function toggleCollapse(tree, id, collapsed) {
  return updateNode(tree, id, { _collapsed: !!collapsed })
}

/** Converte rami recipe (array di item “ricetta”) in nodi albero FE */
export function buildNodesFromRecipeItems(items, parentId, nextId) {
  const src = Array.isArray(items) ? items : []
  return src.map(ri => {
    const id = nextId()
    const ci = ri.catalog_item || ri.catalogItem || null
    const node = {
      id,
      parent_explosion_id: parentId,
      catalog_item_id: ci?.id ?? null,
      catalog_item: ci ? { ...ci } : null,
      _selected: ci ? { ...ci } : null,
      weight_net: ri.weight_net ?? null,
      notes: ri.notes ?? null,
      recipe_id: ri.recipe_id ?? null,
      _selectedRecipeId: ri.recipe_id ?? null,
      explosion_source: 'recipe',
      _collapsed: false,
      children: [],
    }
    if (Array.isArray(ri.children) && ri.children.length) {
      node.children = buildNodesFromRecipeItems(ri.children, id, nextId)
    }
    return node
  })
}

/** Applica ricetta su nodo: rimpiazza SOLO i figli recipe, preserva i figli ad_hoc */
export function applyRecipeAt(tree, id, recipeItems, nextId) {
  const t = cloneTree(tree)
  const hit = findNode(t, id)
  if (!hit) return t

  const current = Array.isArray(hit.node.children) ? hit.node.children : []
  const keepAdHoc = current.filter(ch => ch.explosion_source !== 'recipe')
  const built = buildNodesFromRecipeItems(recipeItems, hit.node.id, nextId)

  hit.node.children = keepAdHoc.concat(built)
  hit.node._collapsed = false
  return t
}

/** Util: true se ci sono root */
export function hasExplodedChildren(tree) {
  return Array.isArray(tree) && tree.length > 0
}

/** (Opzionale) Normalizzatore per API: ripulisce chiavi UI e garantisce schema */
export function normalizeForApi(tree) {
  const walkNorm = (arr) => (arr || []).map(n => ({
    catalog_item_id: n.catalog_item_id ?? n.catalog_item?.id ?? null,
    recipe_id: n.recipe_id ?? n._selectedRecipeId ?? null,
    weight_net: (n.catalog_item?.type ?? n._selected?.type) === 'material'
      ? (Number(n.weight_net) || 0)
      : null,
    notes: n.notes ?? null,
    // UI-only keys eliminate
    children: walkNorm(n.children || []),
  }))
  return walkNorm(tree)
}
