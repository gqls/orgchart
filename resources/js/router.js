// resources/js/router.js
const { createRouter, createWebHistory } = require('vue-router');
const Dashboard = require('./components/Dashboard.vue').default;
const Login = require('./components/Login.vue').default;
const Register = require('./components/Register.vue').default;
const OrganizationsList = require('./components/OrganizationsList.vue').default;
const OrganizationCreate = require('./components/OrganizationCreate.vue').default;
const OrganizationSettings = require('./components/OrganizationSettings.vue').default;
const ScenarioDetail = require('./components/ScenarioDetail.vue').default;
const axios = require('axios');
const UserProfileSettings = require("./components/UserProfileSettings.vue");

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
/*        {
            path: '/forgot-password',
            component: () => import('./components/ForgotPassword.vue'),
            name: 'forgot-password',
            meta: { requiresAuth: false }
        },
        {
            path: '/profile',
            component: () => import('./components/UserProfile.vue'),
            name: 'profile',
            meta: { requiresAuth: true }
        },*/
        {
            path: '/settings',
            component: () => UserProfileSettings,
            name: 'settings',
            meta: { requiresAuth: true }
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
            props: route => {
                // Ensure ID is properly passed as a prop
                const id = Number(route.params.id) || null;
                return { organizationId: id };
            },
            // ensure the component is recreated when the route changes
            beforeRouteUpdate(to, from, next) {
                // This will be called when the route params change but the component stays the same
                console.log("Route is updating from", from.params.id, "to", to.params.id);
                next();
            }
        },
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
        },
        {
            path: '/organizations/:id/settings',
            component: OrganizationSettings,
            name: 'organizations.settings',
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