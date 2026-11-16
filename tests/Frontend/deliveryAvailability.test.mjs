import test from 'node:test'
import assert from 'node:assert/strict'
import {deliveryLimit,deliveryWithinLimit} from '../../resources/js/utils/deliveryAvailability.js'
test('three customers can share eighteen boxes without reserving future planned deliveries',()=>{
 const balance=quantity=>({groups:[{holder_id:1,quantity,missing:false}]})
 assert.equal(deliveryLimit(balance(18),1),18)
 assert.equal(deliveryLimit(balance(11),1,7),18)
 assert.equal(deliveryLimit(balance(5),1),5)
 assert.equal(deliveryWithinLimit(6,5),false)
 assert.equal(deliveryWithinLimit(5,5),true)
 assert.equal(deliveryWithinLimit(0,5),true)
 assert.equal(deliveryLimit(balance(18),2),0)
 assert.equal(deliveryWithinLimit(1,0),false)
 assert.equal(deliveryLimit({groups:[{holder_id:1,quantity:null,missing:true}]},1),null)
 assert.equal(deliveryWithinLimit(1,null),false)
})
