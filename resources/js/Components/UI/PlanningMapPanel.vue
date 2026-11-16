<template>
    <section v-if="activated" v-show="open" ref="panel" class="planning-map border border-base-300 bg-base-100" :class="{'map-window': mode === 'window', 'map-split': mode === 'split', 'map-minimized': minimized}" :style="position" :role="mode === 'window' ? 'dialog' : 'region'" aria-label="Mappa pianificazione" @keydown.esc.stop.prevent="minimized = true">
        <header class="map-title flex items-center justify-between gap-2 border-b border-base-300 px-3 py-2" @pointerdown="startDrag">
            <h2 class="font-semibold">Mappa</h2>
            <div class="flex gap-2">
                <AppAction size="sm" :aria-label="minimized ? 'Ripristina mappa' : 'Minimizza mappa'" :aria-expanded="!minimized" @click="minimized = !minimized">{{ minimized ? 'Ripristina' : 'Minimizza' }}</AppAction>
                <AppAction size="sm" aria-label="Chiudi mappa" @click="$emit('close')">Chiudi</AppAction>
            </div>
        </header>
        <div v-show="!minimized" class="map-content"><slot /></div>
    </section>
</template>
<script setup>
import {computed, ref, watch} from 'vue'
import AppAction from './AppAction.vue'
const props = defineProps({open:Boolean, mode:String})
defineEmits(['close'])
const activated = ref(props.open)
const minimized = ref(false)
const panel = ref(null)
const point = ref(null)
let windowSize = null
watch(() => props.mode, (mode, previous) => {
    if (!panel.value) return
    if (previous === 'window') windowSize = {width:panel.value.style.width, height:panel.value.style.height}
    panel.value.style.width = mode === 'window' ? windowSize?.width || '' : ''
    panel.value.style.height = mode === 'window' ? windowSize?.height || '' : ''
})
watch(() => props.open, value => { if (value) { activated.value = true; minimized.value = false } })
const position = computed(() => props.mode === 'window' && point.value ? {left: `${point.value.x}px`, top: `${point.value.y}px`, right:'auto', bottom:'auto'} : {})
function startDrag(event) {
    if (props.mode !== 'window' || window.innerWidth < 768 || event.button !== 0 || event.target.closest('button')) return
    const box = panel.value.getBoundingClientRect()
    const start = {x:event.clientX, y:event.clientY}
    const handle = event.currentTarget
    handle.setPointerCapture(event.pointerId)
    const move = e => { point.value = {
        x:Math.max(0, Math.min(window.innerWidth - box.width, box.left + e.clientX - start.x)),
        y:Math.max(0, Math.min(window.innerHeight - 64, box.top + e.clientY - start.y)),
    } }
    const end = () => { handle.removeEventListener('pointermove', move); handle.removeEventListener('pointerup', end); handle.removeEventListener('pointercancel', end) }
    handle.addEventListener('pointermove', move)
    handle.addEventListener('pointerup', end)
    handle.addEventListener('pointercancel', end)
}
</script>
<style scoped>
.planning-map { display:flex; flex-direction:column; min-width:0; border-radius:.75rem; overflow:hidden; }
.map-window { position:fixed; z-index:40; right:1rem; bottom:1rem; width:min(48rem, calc(100vw - 2rem)); height:min(36rem, calc(100dvh - 7rem)); min-width:20rem; min-height:12rem; max-width:calc(100vw - 2rem); max-height:calc(100dvh - 2rem); resize:both; }
.map-window .map-title { cursor:move; touch-action:none; user-select:none; }
.map-content { flex:1; min-height:0; }
.map-split { width:50%; position:sticky; top:6rem; height:calc(100dvh - 7rem); }
.map-minimized { height:auto !important; min-height:0; resize:none; }
@media (max-width:1279px) {
    .map-split { width:100%; height:24rem; position:static; }
}
@media (max-width:767px) {
    .map-window { inset:4rem .5rem .5rem !important; width:auto !important; min-width:0; max-width:none; height:auto !important; max-height:none; resize:none; }
    .map-window.map-minimized { inset:auto .5rem .5rem !important; }
    .map-split { width:100%; height:24rem; position:static; }
}
</style>
