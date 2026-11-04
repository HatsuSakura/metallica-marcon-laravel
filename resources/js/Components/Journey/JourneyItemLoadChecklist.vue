<template>
    <label class="inline-flex min-h-[42px] cursor-pointer items-center gap-2 text-xs">
        <span :class="loaded ? 'font-normal opacity-70' : 'font-semibold'">Da ritirare</span>
        <input type="checkbox" class="toggle toggle-success" role="switch" :checked="loaded" :disabled="disabled" :aria-label="`Materiale CER ${cer || 'non indicato'} caricato`" @change="change" />
        <span :class="loaded ? 'font-bold' : 'font-normal opacity-70'">Caricato</span>
    </label>
</template>
<script setup>
const props = defineProps({loaded:Boolean,disabled:Boolean,cer:String})
const emit = defineEmits(['change'])
const change = event => {
    const loaded = event.target.checked
    event.target.checked = props.loaded
    emit('change', loaded)
}
</script>
