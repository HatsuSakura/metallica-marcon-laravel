export const availableContainers = (balance, holderId) => {
    if (!balance) return null
    const group = balance.groups?.find(row => row.holder_id != null && String(row.holder_id) === String(holderId))
    if (!group) return 0
    if (group.missing || group.quantity == null) return null
    return Number(group.quantity)
}
// A saved delivery is already deducted from the residual: add it back while editing that row.
export const deliveryLimit = (balance, holderId, savedDelivered = null) => {
    const available = availableContainers(balance, holderId)
    return available == null ? null : Math.max(0, available + Number(savedDelivered ?? 0))
}
export const deliveryWithinLimit = (quantity, limit) => {
    if (quantity === '' || quantity == null) return false
    const value = Number(quantity)
    return Number.isSafeInteger(value) && value >= 0 && (limit == null ? value === 0 : value <= limit)
}
