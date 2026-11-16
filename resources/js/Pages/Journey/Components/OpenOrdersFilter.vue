<template>
    <div class="flex items-center gap-3 pb-2">
        <h3 class="shrink-0 font-semibold">Ordini Aperti</h3>
        <div class="min-w-0 flex-1" style="max-width: 22rem">
            <label for="open-orders-customer-search" class="sr-only">Filtra ordini aperti per ragione sociale cliente</label>
            <input id="open-orders-customer-search" v-model="query" type="search" class="input input-bordered" placeholder="Filtra per Cliente" autocomplete="off" />
        </div>
    </div>
</template>
<script setup>
const query = defineModel({type:String, default:''})
</script>
