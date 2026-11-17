<template>
    <div class="flex items-center gap-3 pb-2">
        <h3 class="shrink-0 font-semibold"><button type="button" class="flex min-h-12 items-center gap-2" :aria-expanded="open" aria-controls="open-orders-list" @click="open = !open"><font-awesome-icon :icon="['fas', open ? 'chevron-up' : 'chevron-down']" aria-hidden="true" />Ordini Aperti</button></h3>
        <div class="min-w-0 flex-1" style="max-width: 22rem">
            <label for="open-orders-customer-search" class="sr-only">Filtra ordini aperti per ragione sociale cliente</label>
            <input id="open-orders-customer-search" v-model="query" type="search" class="input input-bordered" placeholder="Filtra per Cliente" autocomplete="off" @input="open = true" />
        </div>
    </div>
</template>
<script setup>
const query = defineModel({type:String, default:''})
const open = defineModel('open',{type:Boolean,default:true})
</script>
