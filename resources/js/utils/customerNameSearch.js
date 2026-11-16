export const normalizeCustomerName = value => String(value ?? '')
    .normalize('NFKD').replace(/\p{M}/gu, '').toLowerCase().replace(/[^\p{L}\p{N}]/gu, '')

export function matchesCustomerName(name, query) {
    const normalized = normalizeCustomerName(name)
    const terms = String(query ?? '').trim().split(/\s+/).map(normalizeCustomerName).filter(Boolean)
    return terms.every(term => normalized.includes(term))
}
