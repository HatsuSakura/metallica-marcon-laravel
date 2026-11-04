import { journeyStopStatusLabel } from '@/Constants/journeyStopStatus'

export const stopOrders = (stop) => {
    if (!stop) return []
    if (Array.isArray(stop.orders)) return stop.orders
    const stopOrdersList = stop.stop_orders ?? stop.stopOrders
    if (!Array.isArray(stopOrdersList)) return []
    return stopOrdersList.map((so) => so?.order ?? so).filter(Boolean)
}

export const stopOrdersCount = (stop) => stopOrders(stop).length

export const stopSites = (stop) => {
    const sites = stopOrders(stop).map(order => order.site).filter(Boolean)
    const unique = [...new Map(sites.map(site => [site.id ?? site.address ?? site, site])).values()]
    return unique.length ? unique : [null]
}

export const stopTitle = (stop) => {
    if (!stop) return '-'
    if (stop.kind === 'technical') {
        return stop.technical_action?.label ?? stop.technical_action?.name ?? stop.description ?? 'Sosta tecnica'
    }
    const orders = stopOrders(stop)
    const names = [stop.customer?.company_name, ...orders.map(order => order.customer?.company_name)]
    return names.find(name => typeof name === 'string' && name.trim())?.trim() || 'Ragione sociale non disponibile'
}

export const stopSubtitle = (stop) => {
    if (!stop) return null
    if (stop.address_text) return stop.address_text
    const firstOrder = stopOrders(stop)[0]
    return firstOrder?.site?.address ?? null
}

export const stopStatusLabel = (status) => journeyStopStatusLabel(status)
