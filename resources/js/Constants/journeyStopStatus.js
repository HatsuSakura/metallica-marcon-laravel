export const JOURNEY_STOP_STATUS = Object.freeze({
  PLANNED: 'planned',
  IN_PROGRESS: 'in_progress',
  DONE: 'done',
  SKIPPED: 'skipped',
  CANCELLED: 'cancelled',
});

export function normalizeJourneyStopStatus(status) {
  if (!status) return '';
  if (typeof status === 'string') return status;
  if (typeof status === 'object') return String(status.value ?? status.name ?? '').toLowerCase();
  return String(status).toLowerCase();
}

export function journeyStopStatusLabel(status) {
  const value = normalizeJourneyStopStatus(status);
  if (value === JOURNEY_STOP_STATUS.IN_PROGRESS) return 'In corso';
  if (value === JOURNEY_STOP_STATUS.DONE) return 'Completata';
  if (value === JOURNEY_STOP_STATUS.SKIPPED) return 'Saltata';
  if (value === JOURNEY_STOP_STATUS.CANCELLED) return 'Annullata';
  return 'Pianificata';
}
