const quantity = value => {
    if (!['number', 'string'].includes(typeof value) || (typeof value === 'string' && !value.trim())) return null;
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed >= 0 ? parsed : null;
};

export const describeContainers = (items = [], census = [], completed = false) => {
    const actuals = new Map((Array.isArray(census) ? census : []).map(row => [String(row.order_item_id), quantity(row.actual_containers)]));
    const rows = (Array.isArray(items) ? items : []).map(item => {
        const bulk = item.is_bulk === true || item.is_bulk === 1 || item.is_bulk === '1';
        const planned = quantity(item.holder_quantity);
        const actual = item.id == null ? null : actuals.get(String(item.id)) ?? null;
        return {
            id: item.id,
            type: item.holder?.name || 'Contenitore non specificato',
            typeKey: item.holder_id == null ? `unknown:${item.id}` : String(item.holder_id),
            bulk,
            planned,
            actual,
            quantity: completed ? actual : planned,
            changed: completed && actual !== null && planned !== null && actual !== planned,
        };
    });
    const grouped = field => groupContainerRows(rows, field).map(group => group.bulk ? 'Sfuso' : group.missing
        ? `${group.type}: quantità ${completed && field !== 'planned' ? 'non registrata' : 'non disponibile'}`
        : `${group.quantity} × ${group.type}`).join(' · ');

    return {
        rows,
        groups: groupContainerRows(rows),
        label: completed ? 'Contenitori ritirati' : 'Contenitori da ritirare',
        summary: grouped('quantity'),
        plannedSummary: grouped('planned'),
        changed: rows.some(row => row.changed),
    };
};

export const groupContainerRows = (rows = [], field = 'quantity') => {
    const groups = new Map();
    for (const row of rows) {
        const key = row.bulk ? 'bulk' : row.typeKey;
        const group = groups.get(key) ?? { key, type: row.bulk ? 'Sfuso' : row.type, bulk: row.bulk, quantity: 0, missing: false };
        group.missing ||= !row.bulk && row[field] === null;
        if (row[field] !== null) group.quantity += row[field];
        groups.set(key, group);
    }
    return [...groups.values()];
};

export const describeDeliveryContainers = (holders, completed = false) => {
    const rows = Array.isArray(holders) ? holders : [];
    return describeContainers(rows.map(row => ({
        id: row.id, holder_id: row.holder_id, holder: row.holder, holder_quantity: row.total_holders_count,
    })), rows.map(row => ({ order_item_id: row.id, actual_containers: row.actual_delivered })), completed);
};

export const deliveryContainerSummary = (holders, completed = false) => describeDeliveryContainers(holders, completed).summary;

export const materialWeightLabel = (item, census = [], completed = false) => {
    const value = completed ? census.find(row => String(row.order_item_id) === String(item.id))?.total_weight_kg : item.weight_declared;
    if (!['number', 'string'].includes(typeof value) || (typeof value === 'string' && !value.trim())) return completed ? 'Peso ritirato non registrato' : 'Peso non indicato';
    const amount = Number(value);
    return Number.isFinite(amount) && amount >= 0
        ? `${new Intl.NumberFormat('it-IT', { maximumFractionDigits: 2 }).format(amount)} kg${completed ? ' ritirati' : ''}`
        : 'Peso non indicato';
};

export const stopContainerItems = stop => {
    const orders = stopContainerOrders(stop);
    const unique = new Map();
    for (const order of orders) for (const item of order?.items ?? []) unique.set(item.id ?? item, item);
    return [...unique.values()];
};

export const stopContainerOrders = stop => Array.isArray(stop?.orders) ? stop.orders
    : (stop?.stop_orders ?? stop?.stopOrders ?? []).map(link => link?.order ?? link).filter(Boolean);

export const uniqueContainerOrders = orders => [...new Map(orders.filter(Boolean).map(order => [order.id ?? order, order])).values()];

export const deliveryHoldersForOrders = orders => uniqueContainerOrders(orders).flatMap(order => order.holders ?? []);
