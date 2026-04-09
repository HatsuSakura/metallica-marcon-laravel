export const DATE_TIME_PICKER_FLOW = ['calendar', 'time']

export const DATE_TIME_PICKER_CONFIG = {
  closeOnAutoApply: true,
  setDateOnMenuClose: true,
  tabOutClosesMenu: true,
}

export function roundDownDateToMinutes(date = new Date(), minutesStep = 5) {
  const rounded = new Date(date)
  const minutes = rounded.getMinutes()
  const flooredMinutes = Math.floor(minutes / minutesStep) * minutesStep

  rounded.setMinutes(flooredMinutes, 0, 0)

  return rounded
}

export function getRoundedDownTimeParts(date = new Date(), minutesStep = 5) {
  const rounded = roundDownDateToMinutes(date, minutesStep)

  return {
    hours: rounded.getHours(),
    minutes: rounded.getMinutes(),
  }
}
