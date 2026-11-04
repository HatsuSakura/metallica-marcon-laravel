export const countValue = (raw, min = 0, max = null) => {
    if (raw == null || raw === '') return null;
    const value = Number(raw);
    if (!Number.isSafeInteger(value)) return null;
    return Math.max(min, max == null ? value : Math.min(max,value));
};
export const stepCount = (raw, direction, min = 0, max = null) => countValue((countValue(raw) ?? min) + direction, min, max);
