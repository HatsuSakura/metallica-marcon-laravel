import {stopContainerOrders} from './journeyContainers.js'

const ordersIn = journey => (journey?.stops || []).flatMap(stopContainerOrders)
export const materialCommandMessage = (operation, input, updatedJourney, previousJourney) => {
    const orders = ordersIn(updatedJourney)
    let item
    if (operation === 'add_item') {
        const previousIds = new Set(ordersIn(previousJourney).flatMap(order=>order.items || []).map(item=>String(item.id)))
        item = orders.find(order=>String(order.execution_order_id) === String(input.execution_order_id))?.items?.find(item=>!previousIds.has(String(item.id)))
    } else {
        item = orders.flatMap(order=>order.items || []).find(item=>String(item.id) === String(input.item_id))
    }
    item ??= ordersIn(previousJourney).flatMap(order => [...(order.items || []), ...(order.removed_items || [])]).find(row => String(row.id) === String(input.item_id))
    const code = (typeof item?.cer_code === 'string' ? item.cer_code : item?.cer_code?.code)?.toString().trim()
    const material = code ? `Materiale CER ${code}` : 'Materiale'
    if (operation === 'remove_item') return `${material} rimosso. Puoi ripristinarlo dalla lista dei materiali rimossi.`
    if (operation === 'restore_item') return `${material} ripristinato.`
    if (operation === 'hide_removed_item') return `${material} eliminato dalla lista.`
    return operation === 'add_item' ? `${material} aggiunto correttamente all’ordine.` : `${material} modificato correttamente.`
}
