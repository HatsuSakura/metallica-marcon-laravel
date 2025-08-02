import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import LoginLayout from './Layouts/LoginLayout.vue';
import { ZiggyVue } from 'ziggy'
import { GoogleMap } from 'vue3-google-map';
import '../css/tailwind.css'
import FontAwesomeIcon from './fontawesome';
import DataTablesLib from 'datatables.net'; 
import DataTable from 'datatables.net-vue3';
import store from './store'; // Import the Vuex store
import Vue3FormWizard from 'vue3-form-wizard'
import 'vue3-form-wizard/dist/style.css'
import axios from 'axios'
import { setupAxiosInterceptors } from '@/Composables/useLoadingOverlay.js' 

setupAxiosInterceptors(axios)

DataTable.use(DataTablesLib); // The DataTable component doesn't have datatables.net core included, so we need to tell it to use the DataTablesLib library

createInertiaApp({
  resolve: async (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    const page = await pages[`./Pages/${name}.vue`]
      // Check if the page is the login page and set its layout to LoginLayout
      if (name === 'Auth/Login') {  // Update this to match your login page path
        page.default.layout = LoginLayout;
      } else {
        page.default.layout = page.default.layout || MainLayout;
      }
    return page
  },
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) });
      app.use(plugin);
      app.use(ZiggyVue);
      app.use(GoogleMap, {
          // Optional: Pass any Google Maps options
          load: {
              key: 'AIzaSyB_Q0-uG59EtZ6VpSc77FVuSBvIgpg_79Q', // non funziona passata qui!
              libraries: 'places', // Necessary if using specific Google Maps features
          },
      });
      app.use(store); // Add the Vuex store
      app.use(Vue3FormWizard);

      // Apply the theme from Vuex state
      const theme = store.state.theme.theme; // Fetch the current theme
      document.documentElement.setAttribute('data-theme', theme); // Apply it to DOM


      app.component('DataTable', DataTable); // We register components globally so we can use them anywhere on our application.
      app.component('font-awesome-icon', FontAwesomeIcon);
      app.mount(el);
  },
})