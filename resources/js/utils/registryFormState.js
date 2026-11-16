// Empty optional fields have the same UI value whether stored as null or ''.
// Normalize known numeric fields only; identifiers such as VAT and user codes
// keep leading zeros. The server owns validation.
export function registrySnapshot(value) {
    const numericFields = new Set(['load_capacity', 'volume', 'equivalent_units', 'length', 'casse', 'spazi_casse', 'spazi_bancale', 'total_count', 'latitude', 'longitude', 'version'])
    const normalize = (entry, key) => {
        if (entry === null || entry === undefined) return ''
        if (Array.isArray(entry)) return entry.map(value => normalize(value))
        if (entry && typeof entry === 'object') return Object.fromEntries(Object.keys(entry).sort().map(key => [key, normalize(entry[key], key)]))
        if (numericFields.has(key) && String(entry).trim() !== '' && Number.isFinite(Number(entry))) return Number(entry)
        return entry
    }
    return JSON.stringify(normalize(value))
}
