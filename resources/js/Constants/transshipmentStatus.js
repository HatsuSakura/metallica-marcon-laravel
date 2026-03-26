export const TRANSHIPMENT_STATUS = Object.freeze({
  PROPOSED: 'proposed',
  APPROVED: 'approved',
  PLANNED: 'planned',
  IN_PROGRESS: 'in_progress',
  COMPLETED: 'completed',
  CANCELLED: 'cancelled',
});

export const TRANSHIPMENT_STATUS_LABEL = Object.freeze({
  [TRANSHIPMENT_STATUS.PROPOSED]: 'Proposto',
  [TRANSHIPMENT_STATUS.APPROVED]: 'Approvato',
  [TRANSHIPMENT_STATUS.PLANNED]: 'Pianificato',
  [TRANSHIPMENT_STATUS.IN_PROGRESS]: 'In trasferimento',
  [TRANSHIPMENT_STATUS.COMPLETED]: 'Completato',
  [TRANSHIPMENT_STATUS.CANCELLED]: 'Annullato',
});

export function normalizeTransshipmentStatus(status) {
  if (!status) return '';
  if (typeof status === 'string') return status;
  if (typeof status === 'object') return String(status.value ?? status.name ?? '').toLowerCase();
  return String(status).toLowerCase();
}

export function transshipmentStatusLabel(status) {
  const value = normalizeTransshipmentStatus(status);
  return TRANSHIPMENT_STATUS_LABEL[value] ?? value ?? '-';
}

export function isTransshipmentProposed(status) {
  return normalizeTransshipmentStatus(status) === TRANSHIPMENT_STATUS.PROPOSED;
}

export function isTransshipmentCancelled(status) {
  return normalizeTransshipmentStatus(status) === TRANSHIPMENT_STATUS.CANCELLED;
}

export function transshipmentBadgeClass(status) {
  const value = normalizeTransshipmentStatus(status);
  if (value === TRANSHIPMENT_STATUS.PROPOSED) return 'badge-warning';
  if (value === TRANSHIPMENT_STATUS.APPROVED) return 'badge-success';
  if (value === TRANSHIPMENT_STATUS.CANCELLED) return 'badge-error';
  return 'badge-info';
}
