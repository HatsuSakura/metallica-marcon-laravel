import {ref} from 'vue'
import {router} from '@inertiajs/vue3'

export function useRegistryDelete() {
    const deleting = ref(null)
    const removeRecord = href => {
        if (deleting.value) return
        deleting.value = href
        router.delete(href, {preserveScroll: true, onFinish: () => { deleting.value = null }})
    }
    return {deleting, removeRecord}
}
