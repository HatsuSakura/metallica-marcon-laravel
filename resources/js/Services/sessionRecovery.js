import {reactive} from 'vue'
import axios from 'axios'
import {router} from '@inertiajs/vue3'

export const SESSION_IDLE_CHECK_MS = 5 * 60 * 1000
export const isSessionFailure = error => [401,419].includes(error?.response?.status) || error?.sessionRecovery === true
export const sessionState = reactive({mode:'ready', ownerId:null, message:'', fingerprint:null, localWarningConfirmed:false, notice:''})
let internalRefresh=false, authChannel
let verifying, lastVerified = Date.now(), resumeHandler, installed = false
let flushDrafts = async () => {}
const currentPath = () => location.pathname + location.search + location.hash
const browserRequest = config => {
    const url = new URL(config?.url || '/', location.origin)
    return url.origin === location.origin && !url.pathname.startsWith('/api/v1/') && !config?.headers?.Authorization && !config?.headers?.authorization
}
export const setDraftFlusher = callback => {flushDrafts=callback}
export const setResumeHandler = callback => {resumeHandler=callback;return()=>{if(resumeHandler===callback)resumeHandler=null}}
export const setSessionIdentity = (ownerId, fingerprint) => {
    if (sessionState.ownerId !== ownerId) {
        sessionState.ownerId = ownerId; sessionState.mode='ready'; sessionState.message='';sessionState.notice='';sessionState.localWarningConfirmed=false; lastVerified=Date.now()
        const change={ownerId,nonce:Math.random()}
        try{authChannel?.postMessage(change);localStorage.setItem('metallica-session-owner',JSON.stringify(change))}catch{}
    }
    if (fingerprint) sessionState.fingerprint=fingerprint
}
const recoveryError = () => Object.assign(new Error('Verifica la sessione prima di continuare.'), {sessionRecovery:true})
export const expireSession = () => {
    sessionState.notice=''
    sessionState.mode='expired'
    sessionState.message='La sessione è scaduta o è cambiata. Accedi nuovamente per continuare.'
    // Local persistence is independent of cookies/session state. A failed flush is surfaced by the draft host.
    void flushDrafts().catch(()=>{})
}
const reloadPageData = () => new Promise((resolve,reject) => {
    let succeeded=false, cancelToken
    internalRefresh=true
    const finish=error=>{clearTimeout(timeout);internalRefresh=false;error ? reject(error) : resolve()}
    const timeout=setTimeout(()=>{cancelToken?.cancel();finish(new Error('Aggiornamento non disponibile. Riprova.'))},15000)
    try {
        router.reload({preserveState:true,preserveScroll:true,onCancelToken:token=>{cancelToken=token},onSuccess:()=>{succeeded=true},onFinish:()=>finish(succeeded || sessionState.mode==='expired' ? null : new Error('Aggiornamento non riuscito. Riprova.'))})
    } catch(error) {finish(error)}
})
export const verifySession = async () => {
    if (!sessionState.ownerId || sessionState.mode==='expired') return false
    if (verifying) return verifying
    verifying=(async()=>{
        sessionState.mode='checking';sessionState.message='Verifica della sessione…'
        try {
            const {data}=await axios.get('/session/status',{sessionProbe:true,timeout:15000,headers:{Accept:'application/json'}})
            if(sessionState.mode==='expired')return false
            if (!data.authenticated || Number(data.userId)!==Number(sessionState.ownerId)) {expireSession();return false}
            sessionState.fingerprint=data.csrfFingerprint
            await flushDrafts().catch(()=>{})
            // Read fresh server state before allowing further edits. Do not replay the failed operation.
            await (resumeHandler ? resumeHandler() : reloadPageData())
            if(sessionState.mode==='expired' || !sessionState.ownerId)return false
            sessionState.mode='ready';sessionState.message='';lastVerified=Date.now();return true
        } catch(error) {
            if ([401,419].includes(error?.response?.status)) expireSession()
            else {sessionState.mode='unavailable';sessionState.message=error?.response ? 'Verifica temporaneamente non disponibile. Riprova prima di continuare.' : 'Connessione non disponibile. Le modifiche restano sul dispositivo: riprova quando torna la rete.'}
            return false
        } finally {verifying=null}
    })()
    return verifying
}
export const goToSessionLogin = async () => {
    try {await flushDrafts()} catch {
        if(!sessionState.localWarningConfirmed){sessionState.localWarningConfirmed=true;sessionState.message='Non è stato possibile conservare tutte le modifiche. Copia i dati necessari prima di accedere nuovamente, oppure continua senza recupero locale.';return}
    }
    // Full navigation gets a fresh CSRF cookie; login then follows the server-validated intended path.
    location.assign(sessionState.ownerId ? '/session/login?return_to='+encodeURIComponent(currentPath()) : '/login')
}
export const installSessionRecovery = () => {
    if (installed) return
    installed=true
    axios.interceptors.request.use(async config => {
        if (!browserRequest(config) || config.sessionProbe || !sessionState.ownerId) return config
        config.headers=config.headers || {};config.headers['X-Session-User']=String(sessionState.ownerId)
        if (sessionState.mode!=='ready' && !config.sessionRefresh) throw recoveryError()
        if (!['get','head','options'].includes((config.method||'get').toLowerCase()) && Date.now()-lastVerified>=SESSION_IDLE_CHECK_MS) {
            if(await verifySession())sessionState.notice='Dati aggiornati. Ripeti l’operazione per confermarla.'
            // The click used old page data. It must be explicitly repeated after refresh.
            throw recoveryError()
        }
        return config
    })
    axios.interceptors.response.use(response=>{
        if (response.config && browserRequest(response.config) && !response.config.sessionProbe) {lastVerified=Date.now();if(!['get','head','options'].includes((response.config.method||'get').toLowerCase()))sessionState.notice=''}
        return response
    },error=>{
        if (error.config && browserRequest(error.config) && !error.config.sessionProbe && [401,419].includes(error?.response?.status)) {
            error.sessionRecovery=true
            error.response.data={message:'La sessione è scaduta. Accedi nuovamente per continuare.'}
            expireSession()
        }
        return Promise.reject(error)
    })
    router.on('success',()=>{sessionState.notice=''})
    router.on('invalid',event=>{
        if ([401,419].includes(event.detail.response?.status)) {event.preventDefault();expireSession()}
    })
    router.on('before',event=>{
        if (!sessionState.ownerId) return
        event.detail.visit.headers=event.detail.visit.headers || {};event.detail.visit.headers['X-Session-User']=String(sessionState.ownerId)
        const method=(event.detail.visit.method||'get').toLowerCase()
        if(internalRefresh && method==='get')return
        if (sessionState.mode!=='ready' || (method!=='get' && Date.now()-lastVerified>=SESSION_IDLE_CHECK_MS)) {
            event.preventDefault()
            if (sessionState.mode==='ready') void verifySession().then(ok=>{if(ok)sessionState.notice='Dati aggiornati. Ripeti l’operazione per confermarla.'})
        }
    })
    router.on('exception',event=>{if(navigator.onLine===false){event.preventDefault();sessionState.mode='unavailable';sessionState.message='Connessione non disponibile. Le modifiche restano sul dispositivo: riprova quando torna la rete.'}})
    const identityChanged=change=>{if(sessionState.ownerId!=null && change && change.ownerId!==undefined && String(change.ownerId)!==String(sessionState.ownerId))expireSession()}
    try{authChannel=new BroadcastChannel('metallica-session-owner');authChannel.onmessage=event=>identityChanged(event.data)}catch{}
    window.addEventListener('storage',event=>{if(event.key==='metallica-session-owner'){try{identityChanged(JSON.parse(event.newValue))}catch{}}})
    const resume=()=>{if(document.visibilityState==='visible' && sessionState.ownerId && sessionState.mode!=='expired' && Date.now()-lastVerified>=SESSION_IDLE_CHECK_MS)void verifySession()}
    window.addEventListener('focus',resume)
    window.addEventListener('online',()=>{if(sessionState.mode==='unavailable')void verifySession();else resume()})
    document.addEventListener('visibilitychange',()=>{if(document.hidden)void flushDrafts().catch(()=>{});else resume()})
    window.addEventListener('pagehide',()=>{void flushDrafts().catch(()=>{})})
}
