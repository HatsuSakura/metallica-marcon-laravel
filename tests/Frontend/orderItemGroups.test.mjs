import test from 'node:test';
import assert from 'node:assert/strict';
import { groupOrderItems, groupOrderItemsByCer } from '../../resources/js/utils/orderItemGroups.js';
import { deliveryContainerSummary, materialWeightLabel } from '../../resources/js/utils/journeyContainers.js';

test('same CER retains distinct groups and their material rows', () => {
    const items = [
        { id: 1, cer_code_id: 1, cer_code: { code: '170405' }, order_item_group_id: 10, order_item_group: { label: 'Ferro pulito' } },
        { id: 2, cer_code_id: 1, cer_code: { code: '170405' }, order_item_group_id: 20, order_item_group: { label: 'Ferro misto' } },
        { id: 3, cer_code_id: 1, cer_code: { code: '170405' }, order_item_group_id: 10, order_item_group: { label: 'Ferro pulito' } },
        { id: 4, cer_code_id: 2, cer_code: { code: '170402' } },
    ];
    const sections = groupOrderItemsByCer(items);
    assert.equal(sections.length, 2);
    assert.equal(sections[0].groups.length, 2);
    assert.equal(sections[0].groups[0].groupLabel, 'Ferro pulito');
    assert.deepEqual(sections[0].groups[0].items.map(row => row.item.id), [1, 3]);
    assert.equal(sections[0].groups[1].groupLabel, 'Ferro misto');
});

test('editing preserves item references and indices, unassigned and default group labels', () => {
    const items = [{ cer_code_id: 1 }, { cer_code_id: null }, { cer_code_id: 1 }];
    const groups = groupOrderItems(items, [{ id: 1, code: '170405' }]);
    assert.equal(groups[0].key, 'cer:1|label:170405.1');
    assert.equal(groups[0].groupLabel, '170405.1');
    assert.equal(groups[0].items[1].item, items[2]);
    assert.equal(groups[0].items[1].index, 2);
    assert.equal(groups[1].isUnassigned, true);
});

test('explicit groups with identical labels remain separate by ID', () => {
    const groups = groupOrderItems([
        { cer_code_id: 1, order_item_group_id: 10, order_item_group_label: 'Gruppo' },
        { cer_code_id: 1, order_item_group_id: 20, order_item_group_label: 'Gruppo' },
    ], [{ id: 1, code: '170405' }]);
    assert.equal(groups.length, 2);
});

test('delivery uses ordered total of empty containers, independently of collected containers', () => {
    const summary = deliveryContainerSummary([{ holder_id: 1, holder: { name: 'Cassa ferro' }, filled_holders_count: 6, empty_holders_count: -2, total_holders_count: 4 }]);
    assert.equal(summary, '4 × Cassa ferro');
    assert.equal(deliveryContainerSummary([{ holder_id: 1, holder: { name: 'Cassa ferro' }, total_holders_count: 0 }]), '0 × Cassa ferro');
    assert.match(deliveryContainerSummary([{ holder_id: 1, holder: { name: 'Cassa ferro' } }]), /non disponibile/);
});

test('weight preserves zero and distinguishes declared weight from actual missing weight', () => {
    assert.equal(materialWeightLabel({ id: 1, weight_declared: 0 }), '0 kg');
    assert.equal(materialWeightLabel({ id: 1, weight_declared: 120 }, [], true), 'Peso ritirato non registrato');
    assert.equal(materialWeightLabel({ id: 1 }, [{ order_item_id: 1, total_weight_kg: '145.50' }], true), '145,5 kg ritirati');
});


test('actual deliveries remain independent from pickups and preserve explicit zero', () => {
    const holders = [{id:1,holder_id:1,holder:{name:'Cassa'},total_holders_count:6,actual_delivered:8}];
    assert.equal(deliveryContainerSummary(holders), '6 × Cassa');
    assert.equal(deliveryContainerSummary(holders, true), '8 × Cassa');
    assert.equal(deliveryContainerSummary([{...holders[0],actual_delivered:0}], true), '0 × Cassa');
    assert.match(deliveryContainerSummary([{...holders[0],actual_delivered:null}], true), /non registrata/);
});
