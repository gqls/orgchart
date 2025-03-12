// resources/js/components/Header.vue
<template>
  <header class="app-header">
    <div class="container">
      <div class="header-content">
        <div class="brand">
          <router-link to="/dashboard" class="logo">
            <img src="/img/orgchartlogo.jpeg" alt="OrgChart Logo" class="logo-img" />
            <span class="logo-text">OrgChart</span>
          </router-link>
          <div class="tagline">The art and science of organizational health</div>
        </div>

        <nav class="main-nav">
          <ul class="nav-list">
            <li class="nav-item">
              <router-link to="/dashboard" class="nav-link">
                <i class="fas fa-home"></i> Home
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/organizations" class="nav-link">
                <i class="fas fa-building"></i> Organizations
              </router-link>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link" @click.prevent="toggleDropdown">
                <i class="fas fa-user-circle"></i> {{ user ? user.name : 'Account' }}
                <i class="fas fa-chevron-down"></i>
              </a>
              <div class="dropdown-menu" v-show="dropdownOpen">
                <router-link to="/profile" class="dropdown-item">
                  <i class="fas fa-user"></i> Profile
                </router-link>
                <router-link to="/settings" class="dropdown-item">
                  <i class="fas fa-cog"></i> Settings
                </router-link>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item" @click.prevent="logout">
                  <i class="fas fa-sign-out-alt"></i> Logout
                </a>
              </div>
            </li>
          </ul>
        </nav>

        <div class="mobile-menu-toggle" @click="toggleMobileMenu">
          <i class="fas fa-bars"></i>
        </div>
      </div>
    </div>

    <div class="mobile-menu" v-show="mobileMenuOpen">
      <ul class="mobile-nav-list">
        <li class="mobile-nav-item">
          <router-link to="/dashboard" class="mobile-nav-link" @click="closeMobileMenu">
            <i class="fas fa-home"></i> Home
          </router-link>
        </li>
        <li class="mobile-nav-item">
          <router-link to="/organizations" class="mobile-nav-link" @click="closeMobileMenu">
            <i class="fas fa-building"></i> Organizations
          </router-link>
        </li>
        <li class="mobile-nav-item">
          <router-link to="/profile" class="mobile-nav-link" @click="closeMobileMenu">
            <i class="fas fa-user"></i> Profile
          </router-link>
        </li>
        <li class="mobile-nav-item">
          <router-link to="/settings" class="mobile-nav-link" @click="closeMobileMenu">
            <i class="fas fa-cog"></i> Settings
          </router-link>
        </li>
        <li class="mobile-nav-item">
          <a href="#" class="mobile-nav-link" @click.prevent="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </header>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      user: null,
      dropdownOpen: false,
      mobileMenuOpen: false
    };
  },

  created() {
    this.fetchUser();
    document.addEventListener('click', this.closeDropdown);
  },

  beforeUnmount() {
    document.removeEventListener('click', this.closeDropdown);
  },

  methods: {
    async fetchUser() {
      try {
        const response = await axios.get('/api/user');
        this.user = response.data;
      } catch (error) {
        console.error('Error fetching user data:', error);
      }
    },

    toggleDropdown(event) {
      event.stopPropagation();
      this.dropdownOpen = !this.dropdownOpen;
    },

    closeDropdown(event) {
      if (this.dropdownOpen && !event.target.closest('.nav-item')) {
        this.dropdownOpen = false;
      }
    },

    toggleMobileMenu() {
      this.mobileMenuOpen = !this.mobileMenuOpen;
      document.body.style.overflow = this.mobileMenuOpen ? 'hidden' : '';
    },

    closeMobileMenu() {
      this.mobileMenuOpen = false;
      document.body.style.overflow = '';
    },

    async logout() {
      try {
        await axios.post('/api/logout');
        this.$emit('logout');
        this.$router.push({ name: 'login' });
      } catch (error) {
        console.error('Error logging out:', error);
      }
    }
  }
};
</script>

<style scoped>
.app-header {
  background-color: var(--card-background);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
}

.brand {
  display: flex;
  flex-direction: column;
}

.logo {
  display: flex;
  align-items: center;
  text-decoration: none;
  color: var(--text-color);
}

.logo-img {
  height: 32px;
  margin-right: 0.5rem;
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
}

.tagline {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.main-nav {
  display: none;
}

.nav-list {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
}

.nav-item {
  position: relative;
  margin-left: 1.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 0.5rem 0;
  color: var(--text-color);
  text-decoration: none;
  font-weight: 500;
}

.nav-link i {
  margin-right: 0.5rem;
}

.nav-link .fa-chevron-down {
  margin-left: 0.5rem;
  font-size: 0.75rem;
}

.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  width: 200px;
  background-color: var(--card-background);
  border-radius: 0.25rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 10;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: var(--text-color);
  text-decoration: none;
}

.dropdown-item:hover {
  background-color: var(--background-color);
}

.dropdown-item i {
  margin-right: 0.5rem;
  width: 16px;
  text-align: center;
}

.dropdown-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 0.5rem 0;
}

.mobile-menu-toggle {
  display: block;
  font-size: 1.5rem;
  cursor: pointer;
}

.mobile-menu {
  position: fixed;
  top: 64px;
  left: 0;
  width: 100%;
  height: calc(100vh - 64px);
  background-color: var(--card-background);
  z-index: 99;
  overflow-y: auto;
}

.mobile-nav-list {
  list-style: none;
  margin: 0;
  padding: 1rem;
}

.mobile-nav-item {
  margin-bottom: 1rem;
}

.mobile-nav-link {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  color: var(--text-color);
  text-decoration: none;
  font-weight: 500;
  border-radius: 0.25rem;
}

.mobile-nav-link:hover {
  background-color: var(--background-color);
}

.mobile-nav-link i {
  margin-right: 0.75rem;
  width: 24px;
  text-align: center;
}

@media (min-width: 768px) {
  .main-nav {
    display: block;
  }

  .mobile-menu-toggle {
    display: none;
  }
}
</style>