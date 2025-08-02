const state = {
    messageQueue: [], // Queue of messages to display
    isDisplaying: false, // Track if we are currently displaying a message
};

const mutations = {
    ADD_MESSAGE_TO_QUEUE(state, message) {
        state.messageQueue.push(message);
    },
    REMOVE_MESSAGE_FROM_QUEUE(state) {
        state.messageQueue.shift(); // Remove the first message in the queue (automatic removal)
    },
    REMOVE_MESSAGE_BY_INDEX(state, index) {
        state.messageQueue.splice(index, 1); // Remove the specific message by index (manual removal)
    },
    SET_DISPLAYING_STATE(state, isDisplaying) {
        state.isDisplaying = isDisplaying; // Set whether messages are being displayed
    },
};

const actions = {
    async addMessageToQueue({ commit, state, dispatch }, message) {
        commit('ADD_MESSAGE_TO_QUEUE', message);

        // Start the display process if it's not already running
        if (!state.isDisplaying) {
            commit('SET_DISPLAYING_STATE', true);
            await dispatch('displayMessages'); // Dispatch the displayMessages action
        }
    },

    async displayMessages({ commit, state }) {
        // Iterate through the message queue one by one
        while (state.messageQueue.length > 0) {
            // Display the first message in the queue for 5 seconds
            await new Promise((resolve) => {
                setTimeout(() => {
                    commit('REMOVE_MESSAGE_FROM_QUEUE'); // Automatically remove the message after the timeout
                    resolve();
                }, 5000); // Display each message for 5 seconds
            });

            // Add a delay of 500ms between displaying messages
            if (state.messageQueue.length > 0) {
                await new Promise((resolve) => setTimeout(resolve, 500));
            }
        }

        // Reset displaying state once all messages are shown
        commit('SET_DISPLAYING_STATE', false);
    },

    removeMessageByIndex({ commit }, index) {
        commit('REMOVE_MESSAGE_BY_INDEX', index); // Manually remove the message by index
    },

    queueMessage({ dispatch }, { type, text }) {
        const message = { type, text }; // Message structure: {type: 'success' | 'error' | 'info' | 'warning', text: 'Message content'}
        dispatch('addMessageToQueue', message);
    },
};

export default {
    namespaced: true,
    state,
    mutations,
    actions,
};
