// resources/js/components/Login.vue
<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <h1>Log in to Q5 OrgMaps</h1>
        <p>Welcome back. Please enter your credentials to continue.</p>
      </div>

      <form @submit.prevent="login" class="auth-form">
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
      showPassword: false
    };
  },

  methods: {
    async login() {
      this.loading = true;
      this.errors = {};
      this.loginError = null;

      try {
        const response = await axios.post('/api/login', this.form);

        // Store token and user info
        localStorage.setItem('token', response.data.access_token);
        localStorage.setItem('user', JSON.stringify(response.data.user));

        // Set axios default headers for future requests
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;

        // Emit login event
        this.$emit('login', response.data.user);

        // Redirect to dashboard
        this.$router.push({ name: 'dashboard' });
      } catch (error) {
        if (error.response) {
          if (error.response.status === 422) {
            // Validation errors
            this.errors = error.response.data.errors || {};
          } else if (error.response.status === 401) {
            // Invalid credentials
            this.loginError = error.response.data.message || 'Invalid email or password';
          } else {
            // Other errors
            this.loginError = 'An error occurred. Please try again.';
          }
        } else {
          this.loginError = 'Network error. Please check your connection.';
        }
      } finally {
        this.loading = false;
      }
    }
  }
};
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