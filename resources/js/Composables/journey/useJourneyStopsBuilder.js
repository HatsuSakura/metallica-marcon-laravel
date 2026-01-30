import { computed } from 'vue'

/**
 * V1: stops derivati dagli ordini selezionati (truck+trailer+fulfill).
 * - raggruppa per customer_id
 * - crea uno stop customer per customer (visit_index=1)
 * - sequence = 1..n (ordine stabile: per customer_id crescente)
 *
 * Se vuoi un ordine diverso (es. per distanza) lo faremo dopo.
 */
export function useJourneyStopsBuilder({ listMotrice, listRimorchio, listRiempimento }) {
  const selectedOrders = computed(() => {
    const all = [
      ...(listMotrice.value || []),
      ...(listRimorchio.value || []),
      ...(listRiempimento.value || []),
    ]

    // uniq per id
    const map = new Map()
    for (const o of all) {
      if (!o?.id) continue
      map.set(o.id, o)
    }
    return Array.from(map.values())
  })

  const stops = computed(() => {
    const groups = new Map()

    for (const o of selectedOrders.value) {
      const customerId = o.customer_id ?? o.customer?.id ?? null
      if (!customerId) continue
      if (!groups.has(customerId)) groups.set(customerId, [])
      groups.get(customerId).push(o.id)
    }

    // Ordine deterministico (per evitare “shuffle” casuali in UI)
    const customerIdsSorted = Array.from(groups.keys()).sort((a, b) => Number(a) - Number(b))

    let seq = 1
    const out = []
    for (const customer_id of customerIdsSorted) {
      const orderIds = groups.get(customer_id) || []
     out.push({
        kind: 'customer',
        customer_id,
        customer_visit_index: 1,
        sequence: seq,
        planned_sequence: seq,
        status: 'planned',
        orders: orderIds,
        orders_count: orderIds.length,
      })
      seq++
    }
    return out
  })

  return { selectedOrders, stops }
}
