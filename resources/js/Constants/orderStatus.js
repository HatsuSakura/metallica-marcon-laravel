export const ORDER_STATUS = Object.freeze({
  CREATED: 'creato',
  READY: 'pronto',
  PLANNED: 'pianificato',
  EXECUTED: 'eseguito',
  DOWNLOADED: 'scaricato',
  CLOSED: 'chiuso',
});

export const ORDER_DOCUMENTS_STATUS = Object.freeze({
  NOT_GENERATED: 'not_generated',
  GENERATING: 'generating',
  GENERATED: 'generated',
  FAILED: 'failed',
});

export function normalizeOrderStatus(status) {
  if (!status) return '';
  if (typeof status === 'string') return status;
  if (typeof status === 'object') return String(status.value ?? status.name ?? '').toLowerCase();
  return String(status).toLowerCase();
}

export function normalizeOrderDocumentsStatus(status) {
  if (!status) return '';
  if (typeof status === 'string') return status;
  if (typeof status === 'object') return String(status.value ?? status.name ?? '').toLowerCase();
  return String(status).toLowerCase();
}
