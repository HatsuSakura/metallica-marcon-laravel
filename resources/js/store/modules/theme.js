const state = {
  theme: localStorage.getItem('theme') || 'lighttheme', // Default to light theme
};
const mutations = {
  SET_THEME(state, theme) {
    state.theme = theme;
    localStorage.setItem('theme', theme); // Persist theme to localStorage
  },
};
const actions = {
  toggleTheme({ commit }, isDarkMode) {
    const theme = isDarkMode ? 'darktheme' : 'lighttheme';
    commit('SET_THEME', theme);
  },
};
  
export default {
  namespaced: true,
  state,
  mutations,
  actions,
};