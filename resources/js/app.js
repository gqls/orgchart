// resources/js/app.js

require('./bootstrap');

import { createApp } from 'vue';
import App from './components/App.vue';
import router from './router';
import axios from 'axios';

// Set up Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Create Vue app
const app = createApp(App);
app.use(router);
app.mount('#app');