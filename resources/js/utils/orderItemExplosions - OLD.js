// utils/orderIremExplosions.js
export function normalizeExplosionNode(n) {
  const id = n.catalog_item_id ?? n._selected?.id ?? n.catalog_item?.id ?? null
  if (!id) return null

  return {
    catalog_item_id: id,
    recipe_id: n._selectedRecipeId ?? n.recipe_id ?? null,
    weight_net: n.catalog_item?.type === 'material'
      ? (Number(n.weight_net) || 0)
      : null,
    notes: n.notes ?? null,
    children: normalizeExplosionsArray(n.children || []),
  }
}

export function normalizeExplosionsArray(arr) {
  return (arr || [])
    .map(normalizeExplosionNode)
    .filter(Boolean)
}
