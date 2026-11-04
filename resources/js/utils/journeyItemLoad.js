export const loadTotal = item => {
    const value = item.load_total ?? item.actual_containers ?? item.holder_quantity;
    if (value === '' || value == null) return null;
    const total = Number(value);
    return Number.isInteger(total) && total >= 0 ? total : null;
};
export const splitDraft = item => {
    const total = loadTotal(item) ?? 0;
    const allocations = item.load_allocations ?? [];
    if (allocations.length) return Object.fromEntries(['vehicle', 'trailer'].map(location => [location, allocations.find(row => row.cargo_location === location)?.containers ?? 0]));
    return item.cargo_location === 'trailer' ? {vehicle:0,trailer:total} : {vehicle:total,trailer:0};
};
export const changeSplit = (draft, location, raw, total) => {
    const quantity = raw === '' || raw == null ? null : Number(raw);
    if (total == null || quantity == null || !Number.isInteger(quantity)) return {...draft,[location]:null};
    const value = Math.max(0,Math.min(total,quantity));
    return {[location]:value,[location === 'vehicle' ? 'trailer' : 'vehicle']:total-value};
};
export const validSplit = (draft,total) => total > 1 && [draft.vehicle,draft.trailer].every(value => Number.isInteger(value) && value >= 0) && draft.vehicle + draft.trailer === total;
