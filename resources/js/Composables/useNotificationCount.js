import {onMounted, onBeforeUnmount, ref, watch} from 'vue'
import axios from 'axios'

// Isolated GET client: background refresh must not activate the global loading overlay.
const client = axios.create()
export function useNotificationCount(user, initialCount) {
    const count = ref(Number(initialCount.value) || 0)
    let timer, request, generation = 0, lastRefresh = 0
    const refresh = async () => {
        if (!user.value?.id || document.hidden || request || Date.now() - lastRefresh < 2000) return
        lastRefresh = Date.now()
        const current = generation
        const pending = new AbortController()
        request = pending
        try {
            const response = await client.get('/api/notifications/count',{signal:pending.signal})
            if (current === generation) count.value = Math.max(0,Number(response.data.unreadCount) || 0)
        } catch { /* An expired session or offline connection must not interrupt current work. */ }
        finally { if (request === pending) request = null }
    }
    const synchronize = () => { generation++; count.value = Number(initialCount.value) || 0 }
    watch(initialCount,synchronize)
    watch(() => user.value?.id,() => {synchronize(); request?.abort(); request=null; lastRefresh=0; refresh()})
    onMounted(() => {
        timer = setInterval(refresh,20000)
        document.addEventListener('visibilitychange',refresh)
        window.addEventListener('focus',refresh)
        refresh()
    })
    onBeforeUnmount(() => {clearInterval(timer); generation++; request?.abort(); document.removeEventListener('visibilitychange',refresh); window.removeEventListener('focus',refresh)})
    return count
}
