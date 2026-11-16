import dayjs from 'dayjs'

export function siteOpeningHours(site, date) {
    if (!site?.timetable?.hours_json) return 'Orari della sede non disponibili'
    if (!date || !dayjs(date).isValid()) return 'Seleziona una data di ritiro'
    let hours
    try { hours = typeof site.timetable.hours_json === 'string' ? JSON.parse(site.timetable.hours_json) : site.timetable.hours_json }
    catch { return 'Orari della sede non disponibili' }
    if (!Array.isArray(hours)) return 'Orari della sede non disponibili'
    const position = dayjs(date).day() || 7
    const day = hours.find(row => Number(row.position) === position)
    if (!day) return 'Orari non indicati per questo giorno'
    const display = value => value === '00:00' ? 'Chiuso' : value || 'Non indicato'
    const period = (start,end) => start === '00:00' && end === '00:00' ? 'Chiuso' : `${display(start)}–${display(end)}`
    return `Mattina: ${period(day.orarioApM,day.orarioChM)} · Pomeriggio: ${period(day.orarioApP,day.orarioChP)}`
}
