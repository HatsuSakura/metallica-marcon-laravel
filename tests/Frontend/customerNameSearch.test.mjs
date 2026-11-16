import assert from 'node:assert/strict'
import {matchesCustomerName, normalizeCustomerName} from '../../resources/js/utils/customerNameSearch.js'
assert.equal(normalizeCustomerName(' S.À.I.L. S.r.l. '), 'sailsrl')
for (const query of ['sail','SAIL','s.a.i.l.','sail srl','srl sail','  sail  ']) {
    assert.equal(matchesCustomerName('S.A.I.L. S.r.l.',query),true)
}
assert.equal(matchesCustomerName('F.lli Bianchi & C.','flli bianchi'),true)
assert.equal(matchesCustomerName('Èlite S.p.A.','elite spa'),true)
assert.equal(matchesCustomerName('Altra ditta','sail'),false)
assert.equal(matchesCustomerName(null,'sail'),false)
assert.equal(matchesCustomerName(null,''),true)
