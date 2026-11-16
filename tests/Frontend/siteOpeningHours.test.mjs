import test from 'node:test'
import assert from 'node:assert/strict'
import {siteOpeningHours} from '../../resources/js/utils/siteOpeningHours.js'
const row = {position:7,orarioApM:'08:00',orarioChM:'12:00',orarioApP:'00:00',orarioChP:'00:00'}
test('Sunday maps to position 7 and closed periods remain readable',()=>{
 const site={timetable:{hours_json:JSON.stringify([row])}}
 assert.equal(siteOpeningHours(site,'2026-11-22T10:00:00'),'Mattina: 08:00–12:00 · Pomeriggio: Chiuso')
 assert.equal(siteOpeningHours(site,'2026-11-23T10:00:00'),'Orari non indicati per questo giorno')
})
test('missing or invalid schedules do not break the order form',()=>{
 assert.equal(siteOpeningHours({},'2026-11-22'),'Orari della sede non disponibili')
 for(const hours of ['{','null','{}']) assert.equal(siteOpeningHours({timetable:{hours_json:hours}},'2026-11-22'),'Orari della sede non disponibili')
 assert.equal(siteOpeningHours({timetable:{hours_json:'[]'}},null),'Seleziona una data di ritiro')
})
