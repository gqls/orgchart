// resources/js/auth.js
const axios = require('axios');

// Create the auth object
const auth = {
    init() {
        this.setAuthHeader();
    },

    setAuthHeader() {
        const token = localStorage.getItem('token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            return true;
        }
        return false;
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