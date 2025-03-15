// resources/js/components/Login.vue
<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <h1>Log in to OrgChart</h1>
        <p>Welcome back. Please enter your credentials to continue.</p>
      </div>
      <button @click="testAuth">Test Authentication</button>

      <form @submit.prevent="login" class="auth-form">
        <button type="button" @click="debugAuthState" class="btn-secondary">
          Debug Auth State
        </button>

        <div class="form-group">
          <label for="email">Email</label>
          <input
              id="email"
              type="email"
              v-model="form.email"
              placeholder="Enter your email"
              required
              class="form-control"
          />
          <div v-if="errors.email" class="error-message">{{ errors.email[0] }}</div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-input">
            <input
                id="password"
                :type="showPassword ? 'text' : 'password'"
                v-model="form.password"
                placeholder="Enter your password"
                required
                class="form-control"
            />
            <button
                type="button"
                @click="showPassword = !showPassword"
                class="password-toggle"
            >
              <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
            </button>
          </div>
          <div v-if="errors.password" class="error-message">{{ errors.password[0] }}</div>
        </div>

        <div class="form-group remember-forgot">
          <label class="remember">
            <input type="checkbox" v-model="form.remember" />
            Remember me
          </label>
          <router-link to="/forgot-password" class="forgot-link">Forgot password?</router-link>
        </div>

        <div v-if="loginError" class="alert alert-danger">
          {{ loginError }}
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          <span v-if="loading">
            <i class="fas fa-spinner fa-spin"></i> Logging in...
          </span>
          <span v-else>Log In</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Don't have an account? <router-link to="/register">Sign up</router-link></p>
      </div>
      <div v-if="debugInfo" class="debug-info">
        <pre>{{ debugInfo }}</pre>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      form: {
        email: '',
        password: '',
        remember: false
      },
      errors: {},
      loginError: null,
      loading: false,
      showPassword: false,
      debugInfo: null
    };
  },

  methods: {
    async checkAuthStatus() {
      try {
        const response = await axios.get('/api/auth-debug');
        console.log('Auth check response:', response.data);
        this.debugInfo = response.data;
        console.log('Debug info login.vue:', this.debugInfo);
        alert('Authentication works! in check auth status');
      } catch (error) {
        console.error('Auth check failed in loginvue checkAuthStatus:', error);
      }
    },

    async debugAuthState() {
      console.group('Authentication Debug Information');

      // Check localStorage
      const token = localStorage.getItem('token');
      console.log('Token in localStorage:', token ? `${token.substring(0, 10)}...` : 'None');

      const user = localStorage.getItem('user');
      console.log('User in localStorage:', user ? 'Present' : 'None');

      // Check headers
      console.log('Current axios headers:', axios.defaults.headers.common);

      // Check CSRF token
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      console.log('CSRF meta tag present:', !!csrfMeta);
      console.log('CSRF token value:', csrfMeta ? csrfMeta.getAttribute('content') : 'None');

      try {
        // Test CSRF endpoint
        const csrfResponse = await axios.get('/sanctum/csrf-cookie');
        console.log('CSRF cookie response:', csrfResponse.status);
      } catch (error) {
        console.error('CSRF cookie request failed:', error.message);
      }

      console.groupEnd();
    },


    async testAuth() {
      try {
        const token = localStorage.getItem('token');
        console.log('Stored token:', token);

        // Check for the CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRF token:', csrfToken);

        // Log current stored user (if any)
        const storedUser = localStorage.getItem('user');
        console.log('Stored user:', storedUser);

        // Add more debugging info to headers
        if (token) {
          axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        } else {
          console.warn('No token found in localStorage - authentication will fail');
        }

        console.log('Headers before request:', axios.defaults.headers.common);

        const response = await axios.get('/api/user');
        console.log('Auth test response:', response.data);
        alert('Authentication works! testAuth');
      } catch (error) {
        console.error('Auth test failed in testAuth:', error);

        // Enhanced error logging
        if (error.response) {
          console.log('Error response status:', error.response.status);
          console.log('Error response data:', error.response.data);
          console.log('Error response headers:', error.response.headers);
        }

        alert('Authentication failed: in testAuth ' + error.message);
      }
    },

    async login() {
      try {
        this.loading = true;
        this.loginError = null;
        this.errors = {};

        // Clear any existing tokens first to avoid conflicts
        localStorage.removeItem('token');
        localStorage.removeItem('user');

        // Reset authorization header
        delete axios.defaults.headers.common['Authorization'];

        // Get CSRF cookie
        await axios.get('/sanctum/csrf-cookie');

        console.log('Login attempt with:', { email: this.form.email, password: '******' });

        // Attempt login
        const response = await axios.post('/api/login', this.form);
        console.log('Login response structure:', Object.keys(response.data));

        if (response.data.access_token) {
          console.log('Token received, length:', response.data.access_token.length);
          console.log('Token found:', response.data.access_token.substring(0, 10) + '...');
          console.log('User data:', response.data.user);

          // Store token
          localStorage.setItem('token', response.data.access_token);

          // Set axios default header
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;

          // Test auth with new token
          try {
            const userResponse = await axios.get('/api/user');
            console.log('User verification successful:', userResponse.data);
          } catch (verifyError) {
            console.error('Token verification failed:', verifyError);
            throw new Error('Authentication verification failed');
          }

          // Navigate to dashboard or intended route
          const redirect = this.$route.query.redirect || '/dashboard';
          this.$router.push(redirect);
        } else {
          throw new Error('No access token received');
        }
      } catch (error) {
        console.error('Login error:', error);

        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
        } else if (error.response?.data?.message) {
          console.error('Server error message:', error.response.data.message);
          this.loginError = error.response.data.message;
        } else {
          this.loginError = 'An unexpected error occurred in Login.vue. Please try again.';
        }

        console.log("calling checkAuthStatus in error part of catch in login() in login.vue");
        // Log additional debug info
        await this.checkAuthStatus();
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  padding: 2rem;
  background-color: #f5f5f5;
}

.auth-card {
  width: 100%;
  max-width: 450px;
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.auth-header {
  padding: 2rem;
  text-align: center;
}

.auth-header h1 {
  margin-bottom: 0.5rem;
  font-size: 1.5rem;
  font-weight: 600;
  color: #333;
}

.auth-header p {
  color: #666;
}

.auth-form {
  padding: 0 2rem 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  font-size: 1rem;
}

.form-control:focus {
  border-color: #4caf50;
  outline: none;
  box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.password-input {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
}

.remember-forgot {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.remember {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.forgot-link {
  color: #4caf50;
  text-decoration: none;
}

.forgot-link:hover {
  text-decoration: underline;
}

.btn-submit {
  width: 100%;
  padding: 0.75rem;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 0.25rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-submit:hover {
  background-color: #43a047;
}

.btn-submit:disabled {
  background-color: #a5d6a7;
  cursor: not-allowed;
}

.auth-footer {
  padding: 1.5rem 2rem;
  text-align: center;
  border-top: 1px solid #eee;
}

.auth-footer a {
  color: #4caf50;
  text-decoration: none;
  font-weight: 500;
}

.auth-footer a:hover {
  text-decoration: underline;
}

.alert {
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
  border-radius: 0.25rem;
}

.alert-danger {
  background-color: #ffebee;
  color: #c62828;
  border: 1px solid #ffcdd2;
}

.error-message {
  margin-top: 0.25rem;
  color: #c62828;
  font-size: 0.875rem;
}

@media (max-width: 576px) {
  .auth-container {
    padding: 1rem;
  }

  .auth-header,
  .auth-form,
  .auth-footer {
    padding: 1.5rem;
  }
}
</style>