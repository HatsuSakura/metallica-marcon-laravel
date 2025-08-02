// resources/js/store/index.js
import { createStore } from 'vuex';
import createPersistedState from 'vuex-persistedstate';
import flash from './modules/flash';
import theme from './modules/theme';

const store = createStore({
    state() {
        return {
            currentSite: null, // Initial state for the current site
        };
    },
    mutations: {
        /*
        setCurrentSite(state, site) {
            state.currentSite = site; // Update the current site
        },
        */
        SET_CURRENT_SITE(state, site) {
            state.currentSite = site; // Store serialized site object
        },
    },
    actions: {
        setCurrentSite({ commit }, site) {
            //commit('setCurrentSite', site); // Action to set the current site
            const sanitizedSite = JSON.parse(JSON.stringify(site));
            commit('SET_CURRENT_SITE', sanitizedSite);
        },
    },
    getters: {
        currentSite: (state) => state.currentSite, // Getter to access currentSite
    },
    modules: {
        flash,
        theme,
    },
    plugins: [createPersistedState({
        storage: window.sessionStorage, // Use session storage instead of local storage
        paths: ['currentSite'] // Persist only the `currentSite` state
    })],
    
});

export default store;
