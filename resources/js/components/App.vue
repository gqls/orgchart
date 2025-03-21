// resources/js/components/App.vue
<template>
  <div id="app">
    <header-component v-if="isAuthenticated" />
    <main>
      <router-view @login="login" @logout="logout" />
    </main>
  </div>
</template>

<script>
import HeaderComponent from './Header.vue';
import axios from 'axios';



export default {
  components: {
    HeaderComponent
  },

  data() {
    return {
      isAuthenticated: true,
      user: {
        id: 1,
        name: 'Admin User',
        email: 'admin@example.com'
      }
    };
  },

  created() {
    // Set a dummy token
    localStorage.setItem('token', 'dummy-token-for-development');

    this.isAuthenticated = true;
    this.user = JSON.parse(localStorage.getItem('user'));
  },

  methods: {
    async checkAuth() {
      try {
        const response = await axios.get('/api/user');
        this.isAuthenticated = true;
        this.user = response.data;
      } catch (error) {
        this.isAuthenticated = false;
        this.user = null;

        if (this.$route.meta.requiresAuth) {
          this.$router.push({ name: 'login' });
        }
      }
    },

    login(userData) {
      this.isAuthenticated = true;
      this.user = userData;
    },

    logout() {
      this.isAuthenticated = false;
      this.user = null;
      this.$router.push({ name: 'login' });
    }
  }
};
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --primary-color: #4caf50;
  --primary-dark: #2e7d32;
  --primary-light: #a5d6a7;
  --secondary-color: #2196f3;
  --secondary-dark: #0d47a1;
  --secondary-light: #bbdefb;
  --success-color: #4caf50;
  --warning-color: #ff9800;
  --danger-color: #f44336;
  --text-color: #333;
  --text-secondary: #666;
  --background-color: #f5f5f5;
  --card-background: #fff;
  --border-color: #ddd;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  font-size: 16px;
  line-height: 1.5;
  color: var(--text-color);
  background-color: var(--background-color);
}

#app {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

main {
  flex: 1;
}

h1, h2, h3, h4, h5, h6 {
  font-weight: 600;
  line-height: 1.2;
  margin-bottom: 0.5rem;
}

button, input, select, textarea {
  font-family: inherit;
  font-size: inherit;
  line-height: inherit;
}

a {
  color: var(--primary-color);
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

.btn {
  display: inline-block;
  font-weight: 500;
  text-align: center;
  vertical-align: middle;
  cursor: pointer;
  user-select: none;
  padding: 0.5rem 1rem;
  font-size: 1rem;
  line-height: 1.5;
  border-radius: 0.25rem;
  transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

.btn-primary {
  color: #fff;
  background-color: var(--primary-color);
  border: 1px solid var(--primary-dark);
}

.btn-primary:hover {
  background-color: var(--primary-dark);
  text-decoration: none;
}

.btn-secondary {
  color: var(--text-color);
  background-color: #fff;
  border: 1px solid var(--border-color);
}

.btn-secondary:hover {
  background-color: var(--background-color);
  text-decoration: none;
}

.btn-danger {
  color: #fff;
  background-color: var(--danger-color);
  border: 1px solid #c62828;
}

.btn-danger:hover {
  background-color: #c62828;
  text-decoration: none;
}

.container {
  width: 100%;
  padding-right: 15px;
  padding-left: 15px;
  margin-right: auto;
  margin-left: auto;
}

@media (min-width: 576px) {
  .container {
    max-width: 540px;
  }
}

@media (min-width: 768px) {
  .container {
    max-width: 720px;
  }
}

@media (min-width: 992px) {
  .container {
    max-width: 960px;
  }
}

@media (min-width: 1200px) {
  .container {
    max-width: 1140px;
  }
}

.card {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: var(--card-background);
  background-clip: border-box;
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
}

.card-header {
  padding: 1rem;
  margin-bottom: 0;
  background-color: rgba(0, 0, 0, 0.03);
  border-bottom: 1px solid var(--border-color);
}

.card-body {
  flex: 1 1 auto;
  min-height: 1px;
  padding: 1.25rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: inline-block;
  margin-bottom: 0.5rem;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.5;
  color: var(--text-color);
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid var(--border-color);
  border-radius: 0.25rem;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  border-color: var(--primary-light);
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25);
}

.alert {
  position: relative;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1rem;
  border: 1px solid transparent;
  border-radius: 0.25rem;
}

.alert-success {
  color: #155724;
  background-color: #d4edda;
  border-color: #c3e6cb;
}

.alert-warning {
  color: #856404;
  background-color: #fff3cd;
  border-color: #ffeeba;
}

.alert-danger {
  color: #721c24;
  background-color: #f8d7da;
  border-color: #f5c6cb;
}

.table {
  width: 100%;
  margin-bottom: 1rem;
  color: var(--text-color);
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 0.75rem;
  vertical-align: top;
  border-top: 1px solid var(--border-color);
}

.table thead th {
  vertical-align: bottom;
  border-bottom: 2px solid var(--border-color);
  background-color: rgba(0, 0, 0, 0.03);
}

.table tbody + tbody {
  border-top: 2px solid var(--border-color);
}

.badge {
  display: inline-block;
  padding: 0.25em 0.4em;
  font-size: 75%;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.25rem;
}

.badge-primary {
  color: #fff;
  background-color: var(--primary-color);
}

.badge-secondary {
  color: #fff;
  background-color: var(--text-secondary);
}

.badge-success {
  color: #fff;
  background-color: var(--success-color);
}

.badge-warning {
  color: #212529;
  background-color: var(--warning-color);
}

.badge-danger {
  color: #fff;
  background-color: var(--danger-color);
}

.text-center {
  text-align: center;
}

.text-right {
  text-align: right;
}

.text-muted {
  color: var(--text-secondary);
}

.m-0 { margin: 0 !important; }
.mt-0 { margin-top: 0 !important; }
.mr-0 { margin-right: 0 !important; }
.mb-0 { margin-bottom: 0 !important; }
.ml-0 { margin-left: 0 !important; }

.m-1 { margin: 0.25rem !important; }
.mt-1 { margin-top: 0.25rem !important; }
.mr-1 { margin-right: 0.25rem !important; }
.mb-1 { margin-bottom: 0.25rem !important; }
.ml-1 { margin-left: 0.25rem !important; }

.m-2 { margin: 0.5rem !important; }
.mt-2 { margin-top: 0.5rem !important; }
.mr-2 { margin-right: 0.5rem !important; }
.mb-2 { margin-bottom: 0.5rem !important; }
.ml-2 { margin-left: 0.5rem !important; }

.m-3 { margin: 1rem !important; }
.mt-3 { margin-top: 1rem !important; }
.mr-3 { margin-right: 1rem !important; }
.mb-3 { margin-bottom: 1rem !important; }
.ml-3 { margin-left: 1rem !important; }

.m-4 { margin: 1.5rem !important; }
.mt-4 { margin-top: 1.5rem !important; }
.mr-4 { margin-right: 1.5rem !important; }
.mb-4 { margin-bottom: 1.5rem !important; }
.ml-4 { margin-left: 1.5rem !important; }

.m-5 { margin: 3rem !important; }
.mt-5 { margin-top: 3rem !important; }
.mr-5 { margin-right: 3rem !important; }
.mb-5 { margin-bottom: 3rem !important; }
.ml-5 { margin-left: 3rem !important; }

.p-0 { padding: 0 !important; }
.pt-0 { padding-top: 0 !important; }
.pr-0 { padding-right: 0 !important; }
.pb-0 { padding-bottom: 0 !important; }
.pl-0 { padding-left: 0 !important; }

.p-1 { padding: 0.25rem !important; }
.pt-1 { padding-top: 0.25rem !important; }
.pr-1 { padding-right: 0.25rem !important; }
.pb-1 { padding-bottom: 0.25rem !important; }
.pl-1 { padding-left: 0.25rem !important; }

.p-2 { padding: 0.5rem !important; }
.pt-2 { padding-top: 0.5rem !important; }
.pr-2 { padding-right: 0.5rem !important; }
.pb-2 { padding-bottom: 0.5rem !important; }
.pl-2 { padding-left: 0.5rem !important; }

.p-3 { padding: 1rem !important; }
.pt-3 { padding-top: 1rem !important; }
.pr-3 { padding-right: 1rem !important; }
.pb-3 { padding-bottom: 1rem !important; }
.pl-3 { padding-left: 1rem !important; }

.p-4 { padding: 1.5rem !important; }
.pt-4 { padding-top: 1.5rem !important; }
.pr-4 { padding-right: 1.5rem !important; }
.pb-4 { padding-bottom: 1.5rem !important; }
.pl-4 { padding-left: 1.5rem !important; }

.p-5 { padding: 3rem !important; }
.pt-5 { padding-top: 3rem !important; }
.pr-5 { padding-right: 3rem !important; }
.pb-5 { padding-bottom: 3rem !important; }
.pl-5 { padding-left: 3rem !important; }

.d-flex { display: flex !important; }
.flex-row { flex-direction: row !important; }
.flex-column { flex-direction: column !important; }
.justify-content-start { justify-content: flex-start !important; }
.justify-content-end { justify-content: flex-end !important; }
.justify-content-center { justify-content: center !important; }
.justify-content-between { justify-content: space-between !important; }
.justify-content-around { justify-content: space-around !important; }
.align-items-start { align-items: flex-start !important; }
.align-items-end { align-items: flex-end !important; }
.align-items-center { align-items: center !important; }
.align-items-baseline { align-items: baseline !important; }
.align-items-stretch { align-items: stretch !important; }
.flex-grow-0 { flex-grow: 0 !important; }
.flex-grow-1 { flex-grow: 1 !important; }
.flex-shrink-0 { flex-shrink: 0 !important; }
.flex-shrink-1 { flex-shrink: 1 !important; }
.flex-wrap { flex-wrap: wrap !important; }
.flex-nowrap { flex-wrap: nowrap !important; }
</style>