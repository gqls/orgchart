// resources/js/components/Register.vue
<template>
  <div class="auth-container">
    <div class="auth-card">
      <div class="auth-header">
        <h1>Create an Account</h1>
        <p>Fill in the form below to create your Q5 OrgMaps account.</p>
      </div>

      <form @submit.prevent="register" class="auth-form">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input
              id="name"
              type="text"
              v-model="form.name"
              placeholder="Enter your full name"
              required
              class="form-control"
          />
          <div v-if="errors.name" class="error-message">{{ errors.name[0] }}</div>
        </div>

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
                placeholder="Create a password"
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
          <div class="password-requirements">
            <p>Password must contain at least 8 characters</p>
            <div v-if="errors.password" class="error-message">{{ errors.password[0] }}</div>
          </div>
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input
              id="password_confirmation"
              type="password"
              v-model="form.password_confirmation"
              placeholder="Confirm your password"
              required
              class="form-control"
          />
        </div>

        <div v-if="registerError" class="alert alert-danger">
          {{ registerError }}
        </div>

        <button type="submit" class="btn-submit" :disabled="loading">
          <span v-if="loading">
            <i class="fas fa-spinner fa-spin"></i> Creating Account...
          </span>
          <span v-else>Create Account</span>
        </button>
      </form>

      <div class="auth-footer">
        <p>Already have an account?
          <router-link to="/login">Log in</router-link>
        </p>
      </div>
    </div>

    <!-- Terms of Service Modal -->
    <div v-if="showTerms" class="modal-backdrop" @click.self="showTerms = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Terms of Service</h3>
          <button @click="showTerms = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <h4>1. Acceptance of Terms</h4>
          <p>By accessing or using Q5 OrgMaps, you agree to be bound by these Terms of Service and all applicable laws
            and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing
            this site.</p>

          <h4>2. Use License</h4>
          <p>Permission is granted to temporarily use Q5 OrgMaps for personal, non-commercial transitory viewing only.
            This is the grant of a license, not a transfer of title.</p>

          <h4>3. Disclaimer</h4>
          <p>The materials on Q5 OrgMaps are provided on an 'as is' basis. Q5 OrgMaps makes no warranties, expressed or
            implied, and hereby disclaims and negates all other warranties including, without limitation, implied
            warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of
            intellectual property or other violation of rights.</p>

          <h4>4. Limitations</h4>
          <p>In no event shall Q5 OrgMaps or its suppliers be liable for any damages arising out of the use or inability
            to use the materials on Q5 OrgMaps, even if Q5 OrgMaps or a Q5 OrgMaps authorized representative has been
            notified orally or in writing of the possibility of such damage.</p>
        </div>
        <div class="modal-footer">
          <button @click="showTerms = false" class="btn-secondary">Close</button>
        </div>
      </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div v-if="showPrivacy" class="modal-backdrop" @click.self="showPrivacy = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Privacy Policy</h3>
          <button @click="showPrivacy = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <h4>1. Information We Collect</h4>
          <p>We collect information that you provide directly to us, such as when you create or modify your account,
            request services, contact customer support, or otherwise communicate with us.</p>

          <h4>2. How We Use Information</h4>
          <p>We may use the information we collect to provide, maintain, and improve our services, process transactions,
            send communications, protect our services, and to comply with legal obligations.</p>

          <h4>3. Data Sharing</h4>
          <p>We do not share your personal information with third parties except as described in this privacy policy or
            when we have your permission.</p>

          <h4>4. Data Security</h4>
          <p>We take reasonable measures to help protect your personal information from loss, theft, misuse, and
            unauthorized access, disclosure, alteration, and destruction.</p>
        </div>
        <div class="modal-footer">
          <button @click="showPrivacy = false" class="btn-secondary">Close</button>
        </div>
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
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_id: '1',
        terms: true
      },
      roles: [],
      errors: {},
      registerError: null,
      loading: false,
      showPassword: false,
      showTerms: false,
      showPrivacy: false
    };
  },

  created() {
    //this.fetchRoles();
  },

  methods: {
    async fetchRoles() {
      try {
        const response = await axios.get('/api/roles');
        console.log('roles');
        console.log('roles', this.roles);
        this.roles = response.data;

        // Set default role if there's only one
        if (this.roles.length === 1) {
          this.form.role_id = 1;
        }
      } catch (error) {
        console.error('Error fetching roles:', error);
        if (error.response) {
          console.error('Error status:', error.response.status);
          console.error('Error data:', error.response.data);
        }
      }
    },

    async register() {
      this.loading = true;
      this.errors = {};
      this.registerError = null;

      try {
        const response = await axios.post('/api/register', this.form);
        this.form.role_id=1;
        if (response.data.access_token) {
          localStorage.setItem('token', response.data.access_token);
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.access_token}`;

          if (response.data.user) {
            localStorage.setItem('user', JSON.stringify(response.data.user));
            this.$emit('login', response.data.user);
            this.$router.push({name: 'dashboard'});
          } else {
            throw new Error('User data not received');
          }
        } else {
          throw new Error('Access token not received');
        }
      } catch (error) {
        console.error('Registration error:', error);
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {};
        } else if (error.response?.data?.message) {
          this.registerError = error.response.data.message;
        } else {
          this.registerError = 'An unexpected error occurred. Please try again.';
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
  max-width: 500px;
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

.password-requirements {
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #666;
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

.terms-checkbox {
  display: flex;
  align-items: flex-start;
}

.terms-checkbox input {
  margin-top: 0.25rem;
  margin-right: 0.5rem;
}

.terms-checkbox a {
  color: #4caf50;
  text-decoration: none;
}

.terms-checkbox a:hover {
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

/* Modal styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 1rem 0 0.5rem;
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #999;
}

.modal-body {
  padding: 1rem;
  flex: 1;
  overflow-y: auto;
}

.modal-body h4 {
  margin: 0;
  font-size: 1.25rem;
}

.modal-body p {
  margin-bottom: 1rem;
  line-height: 1.5;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: flex-end;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
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