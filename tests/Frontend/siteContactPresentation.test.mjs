import test from 'node:test';
import assert from 'node:assert/strict';
import { siteContactRows } from '../../resources/js/utils/siteContactPresentation.js';

test('missing, null and malformed contacts have safe placeholders', () => {
    for (const input of [undefined, null, [], {}, [null]]) {
        const [row] = siteContactRows(input);
        assert.equal(row.name, 'nessun referente');
        assert.equal(row.number, 'nessun numero');
        assert.equal(row.href, null);
    }
});

test('name and surname stay paired with their own callable mobile', () => {
    const rows = siteContactRows([
        { id: 1, name: ' Referente ', surname: ' Uno ', mobile: '+39 333 123-4567', phone: '0422 111111' },
        { id: 2, name: 'Referente', surname: 'Due', phone: '(0422) 222222' },
    ]);
    assert.equal(rows[0].name, 'Referente Uno');
    assert.equal(rows[0].href, 'tel:+393331234567');
    assert.equal(rows[0].number, '+39 333 123-4567');
    assert.equal(rows[1].href, 'tel:0422222222');
});

test('missing mobile falls back to phone without mixing contacts', () => {
    const rows = siteContactRows([
        { name: 'Solo nome', mobile: ' ', phone: '0422 123456' },
        { surname: 'Solo cognome', mobile: null, phone: null },
        { name: null, mobile: '3331234567' },
    ]);
    assert.equal(rows[0].href, 'tel:0422123456');
    assert.equal(rows[1].name, 'Solo cognome');
    assert.equal(rows[1].href, null);
    assert.equal(rows[2].name, 'nessun referente');
    assert.equal(rows[2].href, 'tel:3331234567');
});

test('invalid telephone values never become navigable links', () => {
    for (const mobile of ['javascript:alert(1)', 'non disponibile', '+', '12', { value: '3331234567' }]) {
        assert.equal(siteContactRows([{ mobile }])[0].href, null);
        assert.equal(siteContactRows([{ mobile, phone: '0422 123456' }])[0].href, 'tel:0422123456');
    }
});

 test('driver icons follow the saved category and retain a neutral fallback', () => {
    const rows = siteContactRows(['smaltimenti', 'segreteria', 'magazzino', 'inrtermediario', null, 'unknown'].map(role => ({name:'Referente',role})));
    assert.deepEqual(rows.map(row => row.roleIcon), ['user-tie','headset','warehouse','handshake','user','user']);
    assert.equal(rows[3].roleLabel, 'Intermediario');
 });

test('both numbers stay callable, mobile is primary, and duplicate numbers appear once', () => {
    const [row] = siteContactRows([{mobile:'333 1234567',phone:'0422 123456'}]);
    assert.deepEqual(row.numbers.map(number => number.href), ['tel:3331234567','tel:0422123456']);
    assert.equal(row.href, 'tel:3331234567');
    assert.deepEqual(row.numbers.map(number => number.icon), ['mobile-screen-button', 'phone']);
    assert.equal(siteContactRows([{mobile:'333 1234567',phone:'3331234567'}])[0].numbers.length, 1);
    assert.deepEqual(siteContactRows([])[0].numbers, []);
});
