import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './components/Dashboard.vue';
import Login from './components/Login.vue';
import Register from './components/Register.vue';
import OrganizationsList from './components/OrganizationsList.vue';
import OrganizationCreate from './components/OrganizationCreate.vue';
import axios from 'axios';

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

export default router;