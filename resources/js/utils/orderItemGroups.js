/** Stable grouping for both order editing and the driver read view. */
export const groupOrderItems = (items = [], cerList = []) => {
    const cerById = new Map(cerList.map(cer => [Number(cer.id), cer]));
    const groups = new Map();
    items.forEach((item, index) => {
        const cerId = Number(item.cer_code_id || item.cer_code?.id || 0);
        const cer = cerById.get(cerId) ?? item.cer_code ?? item.cerCode;
        const isUnassigned = !cerId && !cer?.code;
        const cerKey = cerId || cer?.code || 'unassigned';
        const groupLabel = item.order_item_group_label || item.order_item_group?.label || (cer ? `${cer.code}.1` : 'Gruppo 1');
        const groupId = item.order_item_group_id ?? item.order_item_group?.id;
        const key = isUnassigned ? 'unassigned' : `cer:${cerKey}|${groupId ? `id:${groupId}` : `label:${groupLabel}`}`;
        if (!groups.has(key)) groups.set(key, {
            key, cerKey, isUnassigned, cerCode: cer?.code ?? '-',
            groupLabel: isUnassigned ? 'Assegna CER agli item' : groupLabel,
            items: [],
        });
        groups.get(key).items.push({ item, index });
    });
    return [...groups.values()];
};

export const groupOrderItemsByCer = (items = []) => {
    const sections = new Map();
    for (const group of groupOrderItems(items)) {
        if (!sections.has(group.cerKey)) sections.set(group.cerKey, {
            key: group.cerKey, code: group.cerCode, unassigned: group.isUnassigned, groups: [],
        });
        sections.get(group.cerKey).groups.push(group);
    }
    return [...sections.values()];
};
