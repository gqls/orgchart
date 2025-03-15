// resources/js/auth.js
const axios = require('axios');

// Create the auth object
const auth = {
        init() {
            // Check for existing token
            const token = localStorage.getItem('token');
            if (token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            }

            // Add response interceptor for 401 errors
            axios.interceptors.response.use(
                response => response,
                error => {
                    if (error.response && error.response.status === 401) {
                        this.logout();
                        window.location = '/login';
                    }
                    return Promise.reject(error);
                }
            );
        },

    setAuthHeader() {
        const token = localStorage.getItem('token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }

        // Add response interceptor for 401 errors
        axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response && error.response.status === 401) {
                    this.logout();
                    window.location = '/login';
                }
                return Promise.reject(error);
            }
        );
        return true;
    },

    async login(credentials) {
        try {
            // Get CSRF cookie first
            await axios.get('/sanctum/csrf-cookie');

            const response = await axios.post('/api/login', credentials);
            if (response.data.access_token) {
                localStorage.setItem('token', response.data.access_token);
                localStorage.setItem('user', JSON.stringify(response.data.user));
                axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;
                return response.data;
            }
            throw new Error('Login failed');
        } catch (error) {
            throw error;
        }
    },

    async check() {
        if (!this.setAuthHeader()) return false;

        try {
            const response = await axios.get('/api/user');
            return !!response.data;
        } catch (error) {
            this.logout();
            return false;
        }
    },

    getUser() {
        const userData = localStorage.getItem('user');
        if (userData) {
            try {
                return JSON.parse(userData);
            } catch (e) {
                return null;
            }
        }
        return null;
    },

    logout() {
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        delete axios.defaults.headers.common['Authorization'];
    }
};

// Export the auth object directly
module.exports = auth;