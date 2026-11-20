const labels = {attivo:'In corso',eseguito:'Terminato',chiuso:'Chiuso',received:'Ricevuto',weighed:'Pesato',classified:'Classificato',done:'Conclusa',skipped:'Saltata',cancelled:'Annullata',planned:'Da iniziare',in_progress:'In corso',vehicle:'Motrice',trailer:'Rimorchio',fulfill:'Riempimento',traffic:'Traffico',over_capacity:'Capacità esaurita',customer_closed:'Cliente chiuso',customer_refused:'Rifiuto del cliente',vehicle_issue:'Problema al mezzo',other:'Altro'}
export function notificationValue(value) {
    if (value === null || value === undefined || value === '') return '—'
    if (typeof value === 'boolean') return value ? 'Sì' : 'No'
    if (typeof value === 'number') return new Intl.NumberFormat('it-IT', {maximumFractionDigits: 3}).format(value)
    return labels[value] || String(value)
}
export function notificationDate(value) {
    return value ? new Intl.DateTimeFormat('it-IT',{dateStyle:'short',timeStyle:'short',timeZone:'Europe/Rome'}).format(new Date(value)) : '—'
}

const quantityPairs = {holder_quantity:'actual_containers', weight_declared:'actual_weight_kg', total_holders_count:'actual_delivered'}
const sameQuantity = (a,b) => a === b || (a !== null && b !== null && a !== '' && b !== '' && Number.isFinite(Number(a)) && Number.isFinite(Number(b)) && Number(a) === Number(b))
export function notificationChanges(changes = []) {
    const omitted = new Set()
    changes.forEach((change,index) => {
        const actual = quantityPairs[change.field]
        if (!actual) return
        // Older persisted notifications have no record_key: pair only within the adjacent field group.
        const match = changes.findIndex((candidate,j) => j > index && candidate.field === actual &&
            candidate.label === change.label && candidate.execution_order_id === change.execution_order_id &&
            (change.record_key ? candidate.record_key === change.record_key : j <= index + 3 && !changes.slice(index+1,j).some(row => row.field === change.field)) &&
            sameQuantity(change.before,candidate.before) && sameQuantity(change.after,candidate.after))
        if (match >= 0) omitted.add(index)
    })
    return changes.filter((change,index) => !omitted.has(index)).map(change => {
        if (!['actual_containers','actual_weight_kg','actual_delivered','holder_quantity','weight_declared','total_holders_count'].includes(change.field)) return change
        const numeric = value => value === null || value === undefined || value === '' ? value : Number.isFinite(Number(value)) ? Number(value) : value
        return {...change,before:numeric(change.before),after:numeric(change.after)}
    })
}
export function notificationDelta(change) {
    if (!['actual_containers','actual_weight_kg','actual_delivered','holder_quantity','weight_declared','total_holders_count'].includes(change.field) || change.before === null || change.after === null) return ''
    const delta = Number(change.after)-Number(change.before)
    if (!Number.isFinite(delta) || delta === 0) return ''
    return `${delta > 0 ? '+' : ''}${notificationValue(delta)}`
}
