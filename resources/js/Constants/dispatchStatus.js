export const DISPATCH_STATUS = Object.freeze({
  PENDING: 'pending',
  IN_PROGRESS: 'in_progress',
  ON_HOLD: 'on_hold',
  MANAGED: 'managed',
});

export const DISPATCH_STATUS_LABEL = Object.freeze({
  [DISPATCH_STATUS.PENDING]: 'Da gestire',
  [DISPATCH_STATUS.IN_PROGRESS]: 'In gestione',
  [DISPATCH_STATUS.ON_HOLD]: 'In attesa',
  [DISPATCH_STATUS.MANAGED]: 'Gestito',
});

export function normalizeDispatchStatus(status) {
  if (!status) return '';
  if (typeof status === 'string') return status;
  if (typeof status === 'object') return String(status.value ?? status.name ?? '').toLowerCase();
  return String(status).toLowerCase();
}

export function dispatchStatusLabel(status) {
  const value = normalizeDispatchStatus(status);
  return DISPATCH_STATUS_LABEL[value] ?? value ?? '-';
}

export function dispatchStatusBadgeClass(status) {
  const value = normalizeDispatchStatus(status);
  if (value === DISPATCH_STATUS.ON_HOLD) return 'badge-warning';
  if (value === DISPATCH_STATUS.IN_PROGRESS) return 'badge-info';
  if (value === DISPATCH_STATUS.MANAGED) return 'badge-success';
  return 'badge-neutral';
}
