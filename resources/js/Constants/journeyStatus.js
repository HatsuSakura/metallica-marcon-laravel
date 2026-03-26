export const JOURNEY_STATUS = Object.freeze({
  CREATED: 'creato',
  ACTIVE: 'attivo',
  EXECUTED: 'eseguito',
  CLOSED: 'chiuso',
});

export function normalizeJourneyStatus(status) {
  if (!status) return '';
  if (typeof status === 'string') return status;
  if (typeof status === 'object') return String(status.value ?? status.name ?? '').toLowerCase();
  return String(status).toLowerCase();
}

export function isJourneyCreated(status) {
  return normalizeJourneyStatus(status) === JOURNEY_STATUS.CREATED;
}
