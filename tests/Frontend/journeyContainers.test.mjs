import test from 'node:test';
import assert from 'node:assert/strict';
import { describeContainers, stopContainerItems, deliveryContainerSummary, deliveryHoldersForOrders } from '../../resources/js/utils/journeyContainers.js';

const box = (id, count) => ({ id, holder_id: 1, holder: { name: 'Cassa ferro' }, holder_quantity: count });

test('planned types aggregate consistently without treating actual as planned', () => {
    const items = [box(1, 3), box(2, 2), { id: 3, holder_id: 2, holder: { name: 'Big bag' }, holder_quantity: 1 }];
    const result = describeContainers(items, [{ order_item_id: 1, actual_containers: 8 }]);
    assert.equal(result.summary, '5 × Cassa ferro · 1 × Big bag');
    assert.equal(result.label, 'Contenitori da ritirare');
    assert.equal(result.changed, false);
});

test('completed uses recorded actual, preserves zero and permits comparison', () => {
    const items = [box(1, 6)];
    const result = describeContainers(items, [{ order_item_id: 1, actual_containers: 0 }], true);
    assert.equal(result.summary, '0 × Cassa ferro');
    assert.equal(result.plannedSummary, '6 × Cassa ferro');
    assert.equal(result.changed, true);
    assert.equal(result.label, 'Contenitori ritirati');
    assert.equal(items[0].holder_quantity, 6);
});

test('absent actual is never replaced by planned or zero; partial totals are not presented as complete', () => {
    assert.match(describeContainers([box(1, 6)], [], true).summary, /non registrata/);
    const partial = describeContainers([box(1, 6), box(2, 2)], [{ order_item_id: 1, actual_containers: 4 }], true);
    assert.match(partial.summary, /non registrata/);
    assert.doesNotMatch(partial.summary, /4 ×/);
    for (const value of [null, undefined, '', ' ', [], {}, false, -1, 'abc']) {
        assert.match(describeContainers([box(1, value)]).summary, /non disponibile/);
    }
});

test('bulk and missing type are distinct from known container quantities', () => {
    assert.equal(describeContainers([{ is_bulk: true }]).summary, 'Sfuso');
    assert.match(describeContainers([{ id: 1, holder_quantity: 2 }]).summary, /Contenitore non specificato/);
});

test('stop totals count a linked item once even when an order is repeated', () => {
    const order = { id: 1, items: [box(1, 2)] };
    assert.equal(stopContainerItems({ stop_orders: [{ order }, { order }] }).length, 1);
    assert.equal(stopContainerItems({ orders: [order] })[0].id, 1);
    assert.deepEqual(stopContainerItems(null), []);
});

test('trip delivery total remains independent of pickup and ignores repeated orders', () => {
    for (const delivery of [2, 5]) {
        const order = { id: 1, items: [box(1, 4)], holders: [{ id: 1, holder_id: 1, holder: { name: 'Cassa ferro' }, total_holders_count: delivery }] };
        assert.equal(describeContainers(stopContainerItems({ orders: [order, order] })).summary, '4 × Cassa ferro');
        assert.equal(deliveryContainerSummary(deliveryHoldersForOrders([order, order])), `${delivery} × Cassa ferro`);
    }
});
