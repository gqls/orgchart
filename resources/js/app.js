// resources/js/app.js

require('./bootstrap');
const { createApp } = require('vue');
const App = require('./components/App.vue').default;
const router = require('./router');
const axios = require('axios');

// Set up Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;


// create Vue app
const app = createApp(App)
app.use(router);
app.mount('#app');