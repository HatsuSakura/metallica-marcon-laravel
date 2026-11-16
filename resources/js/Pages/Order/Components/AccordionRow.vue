<template>
    <section class="border-b border-base-300">
        <h2><button :id="`order-section-heading-${id}`" type="button" class="flex min-h-12 w-full items-center justify-between gap-3 py-3 text-left font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary" :aria-expanded="isOpen" :aria-controls="`order-section-${id}`" @click="isOpen = !isOpen"><span>{{ title }}</span><font-awesome-icon :icon="['fas',isOpen ? 'chevron-up' : 'chevron-down']" class="shrink-0" aria-hidden="true" /></button></h2>
        <div v-show="isOpen" :id="`order-section-${id}`" :aria-labelledby="`order-section-heading-${id}`" class="min-w-0 space-y-3 pb-4"><slot /></div>
    </section>
</template>
<script setup>
import {ref,onMounted,onBeforeUnmount} from 'vue'
const props = defineProps({id:{type:[String,Number],required:true},title:{type:String,required:true},initialOpen:Boolean})
const emit = defineEmits(['register','unregister'])
const isOpen = ref(props.initialOpen)
const controls = {openSection:()=>{isOpen.value=true},closeSection:()=>{isOpen.value=false}}
onMounted(()=>emit('register',controls))
onBeforeUnmount(()=>emit('unregister',controls))
</script>
