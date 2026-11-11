// Keep the existing dispatch contract for all application pages.
export const createFlashModule = ({schedule = setTimeout, cancel = clearTimeout} = {}) => {
    let sequence = 0
    const timers = new Map()
    const forget = id => { if (timers.has(id)) cancel(timers.get(id)); timers.delete(id) }
    return {
        namespaced: true,
        state: () => ({messageQueue: []}),
        mutations: {
            ADD_MESSAGE_TO_QUEUE(state, message) { state.messageQueue.push(message) },
            REMOVE_MESSAGE_BY_ID(state, id) { state.messageQueue = state.messageQueue.filter(message => message.id !== id) },
        },
        actions: {
            queueMessage({commit, dispatch}, input) {
                if (!input?.text) return null
                const type = ['success','error','warning','info'].includes(input.type) ? input.type : 'info'
                const message = {...input, type, id: ++sequence}
                commit('ADD_MESSAGE_TO_QUEUE', message)
                const duration = input.duration ?? (type === 'error' || input.action ? 0 : 3000)
                if (duration > 0) timers.set(message.id, schedule(() => dispatch('removeMessage', message.id), duration))
                return message.id
            },
            addMessageToQueue({dispatch}, message) { return dispatch('queueMessage', message) },
            removeMessage({commit}, id) { forget(id); commit('REMOVE_MESSAGE_BY_ID', id) },
            removeMessageByIndex({state, dispatch}, index) {
                const message = state.messageQueue[index]
                if (message) return dispatch('removeMessage', message.id)
            },
            async runAction({state, dispatch}, id) {
                const message = state.messageQueue.find(message => message.id === id)
                if (!message?.action || message.action.disabled?.()) return
                const result = await message.action.run()
                if (result === false) return
                return dispatch('removeMessage', id)
            },
        },
    }
}
export default createFlashModule()
