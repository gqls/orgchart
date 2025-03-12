import { config } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';

// Create a mock router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div>Home</div>' } },
    { path: '/dashboard', component: { template: '<div>Dashboard</div>' } }
  ]
});

// Configure Vue Test Utils
config.global.plugins = [router];
config.global.mocks = {
  $route: {
    params: {}
  },
  $router: {
    push: jest.fn()
  }
};
