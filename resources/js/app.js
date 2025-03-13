// resources/js/app.js

require('./bootstrap');
const { createApp } = require('vue');
const App = require('./components/App.vue').default;
const router = require('./router');
const axios = require('axios');

// Set up Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Error handler for network requests
axios.interceptors.response.use(
    response => response,
    error => {
        console.error('Axios Error:', error);
        // Redirect to login if unauthorized
        if (error.response && error.response.status === 401) {
            router.push('/login');
        }
        return Promise.reject(error);
    }
);

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    // Make sure the app container exists
    const appContainer = document.getElementById('app');
    if (appContainer) {
        // Create Vue app
        const app = createApp(App);

        // Register global components if needed
        // app.component('component-name', Component);

        // Install router
        app.use(router);

        // Mount app
        app.mount('#app');

        // Add error handling
        app.config.errorHandler = (err, vm, info) => {
            console.error('Vue Error:', err);
            console.error('Error Info:', info);
        };
    } else {
        console.error('App container not found');
    }
});