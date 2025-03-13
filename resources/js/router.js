// resources/js/router.js
const { createRouter, createWebHistory } = require('vue-router');
const Dashboard = require('./components/Dashboard.vue').default;
const Login = require('./components/Login.vue').default;
const Register = require('./components/Register.vue').default;
const OrganizationsList = require('./components/OrganizationsList.vue').default;
const OrganizationCreate = require('./components/OrganizationCreate.vue').default;
const ScenarioDetail = require('./components/ScenarioDetail.vue').default;
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
            props: (route) => ({ organizationId: route.params.id })
        },
        // Add these new routes for organization features
        {
            path: '/organizations/:id/departments',
            component: () => import('./components/DepartmentList.vue'),
            name: 'organizations.departments',
            meta: { requiresAuth: true },
            props: true
        },
        {
            path: '/organizations/:id/positions',
            component: () => import('./components/PositionList.vue'),
            name: 'organizations.positions',
            meta: { requiresAuth: true },
            props: true
        },
        {
            path: '/organizations/:id/scenarios',
            component: () => import('./components/ScenarioList.vue'),
            name: 'organizations.scenarios',
            meta: { requiresAuth: true },
            props: true
        },
        {
            path: '/organizations/:id/scenarios/:scenarioId',
            component: () => import('./components/ScenarioDetail.vue'),
            name: 'organizations.scenarios.show',
            meta: { requiresAuth: true },
            props: true
        },
        {
            path: '/organizations/:id/scenarios/:scenarioId/detail',
            component: ScenarioDetail,
            name: 'organizations.scenarios.detail',
            meta: { requiresAuth: true },
            props: true
        }
    ]
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        try {
            const response = await axios.get('/api/user');
            if (response.status === 200 && response.data) {
                next();
            } else {
                next({
                    name: 'login',
                    query: { redirect: to.fullPath }
                });
            }
        } catch (error) {
            console.error('Authentication check failed:', error);
            localStorage.removeItem('token'); // Clear invalid token
            next({
                name: 'login',
                query: { redirect: to.fullPath }
            });
        }
    } else {
        next();
    }
});

module.exports = router;