import assert from 'node:assert/strict'
import {journeyVehicleConfiguration as config} from '../../resources/js/utils/journeyVehicleConfiguration.js'
const vehicles = [{id:1,type:'motrice',has_trailer:true},{id:2,type:'sponda',has_trailer:true},{id:3,type:'furgone',has_trailer:true},{id:4,type:'motrice',has_trailer:false}]
const cargos = [{id:1,name:'Lungo',is_long:true},{id:2,name:'Corto',is_long:false},{id:3,name:'Sponda',is_long:false},{id:4,name:'Furgone',is_long:false}]
const trailers = [{id:1}]
const check = form => config(form, vehicles, cargos, trailers)
assert.equal(check({}).ready,false)
assert.equal(check({vehicle_id:4,vehicle_cargo_id:1}).ready,true)
assert.equal(check({vehicle_id:2,vehicle_cargo_id:3}).ready,true)
assert.equal(check({vehicle_id:3,vehicle_cargo_id:4}).ready,true)
assert.equal(check({vehicle_id:2}).trailerAllowed,false)
assert.equal(check({vehicle_id:3}).trailerAllowed,false)
for (const [truck,trailer,ready] of [[1,2,true],[2,1,true],[1,1,false],[2,2,true]]) {
    assert.equal(check({vehicle_id:1,vehicle_cargo_id:truck,trailer_id:1,trailer_cargo_id:trailer}).ready,ready)
}
assert.equal(check({vehicle_id:1,vehicle_cargo_id:1,trailer_id:1}).ready,false)
assert.equal(check({vehicle_id:2,vehicle_cargo_id:3,trailer_id:1,trailer_cargo_id:1}).ready,false)
assert.equal(check({vehicle_id:4,vehicle_cargo_id:1,trailer_id:1,trailer_cargo_id:2}).ready,false)
assert.equal(check({vehicle_id:1,vehicle_cargo_id:3}).ready,false)
assert.equal(check({vehicle_id:1,vehicle_cargo_id:1,trailer_cargo_id:2}).ready,false)
