import {ref, watch} from 'vue'

export function usePlanningMapMode() {
    let initial = window.innerWidth >= 2200 ? 'split' : 'window'
    try {
        const saved = localStorage.getItem('planning-map-mode')
        if (['split', 'window'].includes(saved)) initial = saved
    } catch {}
    const mode = ref(initial)
    watch(mode, value => { try { localStorage.setItem('planning-map-mode', value) } catch {} })
    return mode
}
