import dayjs from 'dayjs'

export function parseServerDateTime(value) {
  if (!value) return null

  if (value instanceof Date) {
    return Number.isNaN(value.getTime()) ? null : value
  }

  const stringValue = String(value).trim()
  if (stringValue === '') return null

  // Laravel/Inertia may serialize datetimes as ISO8601 with fractional seconds and trailing Z.
  // For this app we need to preserve the wall-clock datetime coming from the server, not reinterpret it in the browser timezone.
  const normalizedStringValue = stringValue
    .replace('T', ' ')
    .replace(/\.\d+Z?$/, '')
    .replace(/Z$/, '')

  const match = normalizedStringValue.match(
    /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/
  )

  if (match) {
    const [, year, month, day, hour = '00', minute = '00', second = '00'] = match

    return new Date(
      Number(year),
      Number(month) - 1,
      Number(day),
      Number(hour),
      Number(minute),
      Number(second),
      0
    )
  }

  const fallback = new Date(normalizedStringValue)
  return Number.isNaN(fallback.getTime()) ? null : fallback
}

export function formatServerDateTime(value) {
  return value ? dayjs(value).format('YYYY-MM-DD HH:mm:ss') : null
}
