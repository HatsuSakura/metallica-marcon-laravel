// /resources/js/Composables/journey/useJourneyLookups.js
import { computed } from 'vue'

export function useJourneyLookups(ordersRef) {
  const customerById = computed(() => {
    const m = new Map()

    for (const o of (ordersRef.value || [])) {
      const cid = o?.customer_id ?? o?.customer?.id
      const cust = o?.customer
      if (!cid || !cust) continue

      const prev = m.get(cid)
      if (!prev || Object.keys(prev).length < Object.keys(cust).length) {
        m.set(cid, cust)
      }
    }

    return m
  })

  const siteByCustomerId = computed(() => {
    const m = new Map()

    for (const o of (ordersRef.value || [])) {
      const cid = o?.customer_id ?? o?.customer?.id
      const site = o?.site
      if (!cid || !site) continue

      const prev = m.get(cid)
      // preferisci site “più completo”
      if (!prev || Object.keys(prev).length < Object.keys(site).length) {
        m.set(cid, o.site)
      }
    }

    return m
  })

  const siteById = computed(() => {
    const m = new Map()
    for (const o of (ordersRef.value || [])) {
        const s = o?.site
        if (s?.id && !m.has(s.id)) m.set(s.id, s)
    }
    return m
    })


  return { customerById, siteByCustomerId, siteById }

}
