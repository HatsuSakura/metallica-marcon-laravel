export function floorToFiveMinutes(value = new Date()) {
    const result = new Date(value)
    if (Number.isNaN(result.getTime())) return null
    result.setMinutes(Math.floor(result.getMinutes() / 5) * 5, 0, 0)
    return result
}
