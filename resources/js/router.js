const { createRouter, createWebHistory } = require('vue-router');
const Dashboard = require('./components/Dashboard.vue').default;
const Login = require('./components/Login.vue').default;
const Register = require('./components/Register.vue').default;
const OrganizationsList = require('./components/OrganizationsList.vue').default;
const OrganizationCreate = require('./components/OrganizationCreate.vue').default;
const axios = require('axios');

// Create router
const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            redirect: '/dashboard'
        },
        {
            path: '/login',
            component: Login,
            name: 'login',
            meta: { requiresAuth: false }
        },
        {
            path: '/register',
            component: Register,
            name: 'register',
            meta: { requiresAuth: false }
        },
        {
            path: '/dashboard',
            component: Dashboard,
            name: 'dashboard',
            meta: { requiresAuth: true }
        },
        {
            path: '/organizations',
            component: OrganizationsList,
            name: 'organizations.index',
            meta: { requiresAuth: true }
        },
        {
            path: '/organizations/create',
            component: OrganizationCreate,
            name: 'organizations.create',
            meta: { requiresAuth: true }
        },
        {
            path: '/organizations/:id/dashboard',
            component: Dashboard,
            name: 'organizations.dashboard',
            meta: { requiresAuth: true },
            props: true
        }
    ]
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        try {
            // Check if user is authenticated
            const response = await axios.get('/api/user');
            if (response.status === 200) {
                next();
            } else {
                next({ name: 'login' });
            }
        } catch (error) {
            next({ name: 'login' });
        }
    } else {
        next();
    }
});

module.exports = router;