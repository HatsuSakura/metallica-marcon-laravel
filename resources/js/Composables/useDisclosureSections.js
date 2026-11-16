import {onBeforeUnmount} from 'vue'

// Presentation only: no domain writes or form values are owned here.
export function useDisclosureSections() {
    const sections = new Set()
    const registerSection = section => { if (section) sections.add(section) }
    const unregisterSection = section => sections.delete(section)
    const openAll = () => sections.forEach(section => section.openSection())
    const closeAll = () => sections.forEach(section => section.closeSection())
    onBeforeUnmount(() => sections.clear())
    return {registerSection, unregisterSection, openAll, closeAll}
}
