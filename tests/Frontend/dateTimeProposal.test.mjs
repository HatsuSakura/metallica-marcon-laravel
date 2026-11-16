import assert from 'node:assert/strict'
import {floorToFiveMinutes} from '../../resources/js/utils/dateTimeProposal.js'
for (const [minute,expected] of [[0,0],[4,0],[5,5],[24,20],[25,25],[59,55]]) {
    const original = new Date(2026,10,16,17,minute,47,123)
    const actual = floorToFiveMinutes(original)
    assert.equal(actual.getMinutes(),expected)
    assert.equal(actual.getHours(),17)
    assert.equal(actual.getSeconds(),0)
    assert.equal(actual.getMilliseconds(),0)
    assert.equal(original.getMinutes(),minute)
    assert.equal(original.getSeconds(),47)
}
assert.equal(floorToFiveMinutes('invalid'),null)
