// resources/js/app.js

require('./bootstrap');
const { createApp } = require('vue');
const App = require('./components/App.vue').default;
const router = require('./router');
const axios = require('axios');

// Import the auth module
const auth = require('./auth');

// Make sure it's defined before using it
if (auth) {
    console.log('Auth module loaded successfully');
    auth.init();
} else {
    console.error('Auth module failed to load');
}

// Set up Axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

// Error handler for network requests
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Clear invalid authentication
            localStorage.removeItem('token');
            localStorage.removeItem('user');
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