// resources/js/components/Sidebar.vue
<template>
  <div class="sidebar" :class="{ 'open': open }">
    <div class="sidebar-header">
      <div class="org-info" v-if="organization && organization.name">
        <div
            class="org-logo"
            :style="organization.logo_path ? { backgroundImage: `url(${organization.logo_path})` } : { backgroundColor: organization.primary_color || '#4caf50' }"
        >
          {{ !organization.logo_path ? organization.name.charAt(0) : '' }}
        </div>
        <div class="org-name">{{ organization.name }}</div>
      </div>
      <div class="org-info" v-else>
        <div class="org-logo" style="backgroundColor: '#4caf50'">?</div>
        <div class="org-name">Loading...</div>
      </div>
      <button @click="$emit('toggle')" class="btn-toggle-sidebar">
        <i class="fas fa-chevron-left"></i>
      </button>
    </div>

    <div class="sidebar-content">
      <div class="sidebar-section">
        <h3 class="section-title">Navigation</h3>
        <ul class="nav-list">
          <li class="nav-item">
            <router-link :to="`/organizations/${organization.id}/dashboard`" class="nav-link">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link :to="`/organizations/${organization.id}/settings`" class="nav-link">
              <i class="fas fa-cog"></i>
              <span>Settings</span>
            </router-link>
          </li>
        </ul>
      </div>

      <div class="sidebar-section">
        <h3 class="section-title">Scenarios</h3>
        <div v-if="!scenarios.length" class="empty-state">
          No scenarios created yet
        </div>
        <ul v-else class="scenario-list">
          <li
              v-for="scenario in scenarios"
              :key="scenario.id"
              class="scenario-item"
              :class="{ 'current': scenario.is_current, 'base': scenario.is_base }"
          >
            <div class="scenario-info" @click="selectScenario(scenario)">
              <div class="scenario-name">{{ scenario.name }}</div>
              <div class="scenario-badges">
                <span v-if="scenario.is_current" class="badge badge-primary">Current</span>
                <span v-if="scenario.is_base" class="badge badge-secondary">Base</span>
              </div>
            </div>
          </li>
        </ul>
        <button @click="createNewScenario" class="btn btn-secondary btn-block mt-2">
          <i class="fas fa-plus"></i> New Scenario
        </button>
      </div>

      <div class="sidebar-section">
        <h3 class="section-title">Departments</h3>
        <div v-if="!departments.length" class="empty-state">
          No departments created yet
        </div>
        <ul v-else class="department-list">
          <li
              v-for="department in departments"
              :key="department.id"
              class="department-item"
          >
            <div class="department-color" :style="{ backgroundColor: department.color || '#4caf50' }"></div>
            <div class="department-name">{{ department.name }}</div>
          </li>
        </ul>
        <button @click="createNewDepartment" class="btn btn-secondary btn-block mt-2">
          <i class="fas fa-plus"></i> New Department
        </button>
      </div>
    </div>

    <div class="sidebar-footer">
      <div class="user-info">
        <div class="user-avatar">
          {{ user ? user.name.charAt(0) : '' }}
        </div>
        <div class="user-name">{{ user ? user.name : '' }}</div>
      </div>
      <button @click="logout" class="btn-logout">
        <i class="fas fa-sign-out-alt"></i>
      </button>
    </div>

    <!-- New Scenario Modal -->
    <div v-if="showNewScenarioModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Create New Scenario</h3>
          <button @click="cancelNewScenario" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="scenario-name">Scenario Name:</label>
            <input id="scenario-name" v-model="newScenario.name" type="text">
          </div>

          <div class="form-group">
            <label for="scenario-description">Description:</label>
            <textarea id="scenario-description" v-model="newScenario.description" rows="3"></textarea>
          </div>

          <div class="form-group">
            <label for="base-scenario">Base Scenario:</label>
            <select id="base-scenario" v-model="newScenario.baseScenarioId">
              <option v-for="scenario in scenarios" :key="scenario.id" :value="scenario.id">
                {{ scenario.name }}
              </option>
            </select>
            <div class="form-help">New scenario will copy structure and data from the selected base scenario</div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="saveNewScenario" class="btn-primary" :disabled="!newScenario.name">Create Scenario</button>
          <button @click="cancelNewScenario" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- New Department Modal -->
    <div v-if="showNewDepartmentModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Create New Department</h3>
          <button @click="cancelNewDepartment" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="department-name">Department Name:</label>
            <input id="department-name" v-model="newDepartment.name" type="text">
          </div>

          <div class="form-group">
            <label for="department-code">Department Code:</label>
            <input id="department-code" v-model="newDepartment.code" type="text">
          </div>

          <div class="form-group">
            <label for="department-description">Description:</label>
            <textarea id="department-description" v-model="newDepartment.description" rows="3"></textarea>
          </div>

          <div class="form-group">
            <label for="department-color">Color:</label>
            <input id="department-color" v-model="newDepartment.color" type="color">
          </div>
        </div>

        <div class="modal-footer">
          <button @click="saveNewDepartment" class="btn-primary" :disabled="!newDepartment.name">Create Department</button>
          <button @click="cancelNewDepartment" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ToBeModeling from './ToBeModeling.vue';
import ActivityAnalysis from "./ActivityAnalysis.vue";
import AsIsAnalysis from "./AsIsAnalysis.vue";
import Header from "./Header.vue";
import HrData from "./HrData.vue";
import Login from "./Login.vue";
import OrganizationCreate from "./OrganizationCreate.vue";
import OrganizationsList from "./OrganizationsList.vue";
import Register from "./Register.vue";
import OrgChart from "./OrgChart.vue";

export default {
  components: {
    ToBeModeling,
    ActivityAnalysis,
    AsIsAnalysis,
    Header,
    HrData,
    Login,
    OrganizationCreate,
    OrganizationsList,
    OrgChart,
    Register,
  },

  props: {
    organization: {
      type: Object,
      required: true,
      default: () => ({id: null,})
    },
    open: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      user: null,
      scenarios: [],
      departments: [],
      showNewScenarioModal: false,
      showNewDepartmentModal: false,
      newScenario: {
        name: '',
        description: '',
        baseScenarioId: null
      },
      newDepartment: {
        name: '',
        code: '',
        description: '',
        color: '#4caf50'
      }
    };
  },

  created() {
    this.fetchUserData();
    this.fetchScenarios();
    this.fetchDepartments();
  },

  methods: {
    async fetchUserData() {
      try {
        const response = await axios.get('/api/user');
        this.user = response.data;
      } catch (error) {
        console.error('Error fetching user data:', error);
      }
    },

    async fetchScenarios() {
      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/scenarios`);
        this.scenarios = response.data;

        // Set default base scenario for new scenarios
        if (this.scenarios.length > 0) {
          const baseScenario = this.scenarios.find(s => s.is_base) || this.scenarios.find(s => s.is_current);
          if (baseScenario) {
            this.newScenario.baseScenarioId = baseScenario.id;
          } else {
            this.newScenario.baseScenarioId = this.scenarios[0].id;
          }
        }
      } catch (error) {
        console.error('Error fetching scenarios:', error);
      }
    },

    async fetchDepartments() {
      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/departments`);
        this.departments = response.data;
      } catch (error) {
        console.error('Error fetching departments:', error);
      }
    },

    selectScenario(scenario) {
      this.$emit('select-scenario', scenario);
    },

    createNewScenario() {
      this.showNewScenarioModal = true;
    },

    async saveNewScenario() {
      try {
        const response = await axios.post(`/api/organizations/${this.organization.id}/scenarios`, {
          name: this.newScenario.name,
          description: this.newScenario.description,
          base_scenario_id: this.newScenario.baseScenarioId
        });

        // Add the new scenario to the list
        this.scenarios.push(response.data);

        // Clean up and hide modal
        this.newScenario = {
          name: '',
          description: '',
          baseScenarioId: null
        };
        this.showNewScenarioModal = false;

        // Emit event to parent for potential scenario change
        this.$emit('scenario-created', response.data);

      } catch (error) {
        console.error('Error creating scenario:', error);
      }
    },

    cancelNewScenario() {
      this.showNewScenarioModal = false;
      this.newScenario = {
        name: '',
        description: '',
        baseScenarioId: null
      };
    },

    createNewDepartment() {
      this.showNewDepartmentModal = true;
    },

    async saveNewDepartment() {
      try {
        const response = await axios.post(`/api/organizations/${this.organization.id}/departments`, {
          name: this.newDepartment.name,
          code: this.newDepartment.code,
          description: this.newDepartment.description,
          color: this.newDepartment.color
        });

        // Add the new department to the list
        this.departments.push(response.data);

        // Clean up and hide modal
        this.newDepartment = {
          name: '',
          code: '',
          description: '',
          color: '#4caf50'
        };
        this.showNewDepartmentModal = false;

        // Emit event to parent
        this.$emit('department-created', response.data);

      } catch (error) {
        console.error('Error creating department:', error);
      }
    },

    cancelNewDepartment() {
      this.showNewDepartmentModal = false;
      this.newDepartment = {
        name: '',
        code: '',
        description: '',
        color: '#4caf50'
      };
    },

    async logout() {
      try {
        await axios.post('/api/logout');
        this.$router.push('/login');
      } catch (error) {
        console.error('Error logging out:', error);
      }
    }
  }
};
</script>

<style scoped>
.sidebar {
  display: flex;
  flex-direction: column;
  width: 280px;
  height: 100%;
  background-color: #fff;
  border-right: 1px solid #ddd;
  box-shadow: 1px 0 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
}

.sidebar.open {
  transform: translateX(0);
}

.sidebar:not(.open) {
  transform: translateX(-280px);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #eee;
}

.org-info {
  display: flex;
  align-items: center;
  max-width: 210px;
}

.org-logo {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  color: white;
  background-size: cover;
  background-position: center;
  margin-right: 10px;
}

.org-name {
  font-weight: 600;
  font-size: 1rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-toggle-sidebar {
  background: none;
  border: none;
  font-size: 1rem;
  cursor: pointer;
  color: #666;
  padding: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.btn-toggle-sidebar:hover {
  background-color: #f5f5f5;
}

.sidebar-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

.sidebar-section {
  margin-bottom: 24px;
}

.section-title {
  font-size: 0.8rem;
  text-transform: uppercase;
  color: #666;
  margin-bottom: 8px;
  font-weight: 600;
}

.nav-list, .scenario-list, .department-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  margin-bottom: 2px;
}

.nav-link {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  border-radius: 4px;
  color: #333;
  text-decoration: none;
  transition: background-color 0.2s;
}

.nav-link i {
  margin-right: 10px;
  width: 16px;
  text-align: center;
}

.nav-link:hover, .nav-link.active {
  background-color: #f5f5f5;
}

.nav-link.active {
  color: #4caf50;
  font-weight: 500;
}

.scenario-item, .department-item {
  margin-bottom: 6px;
  border-radius: 4px;
  overflow: hidden;
  border: 1px solid #eee;
  transition: all 0.2s;
}

.scenario-item:hover, .department-item:hover {
  border-color: #ddd;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.scenario-item.current {
  border-color: #4caf50;
}

.scenario-item.base {
  border-color: #2196f3;
}

.scenario-info {
  padding: 8px 12px;
  cursor: pointer;
}

.scenario-name {
  font-weight: 500;
  margin-bottom: 2px;
}

.scenario-badges {
  display: flex;
  gap: 6px;
}

.badge {
  font-size: 0.65rem;
  padding: 2px 6px;
  border-radius: 10px;
  font-weight: 600;
}

.badge-primary {
  background-color: #e8f5e9;
  color: #4caf50;
}

.badge-secondary {
  background-color: #e3f2fd;
  color: #2196f3;
}

.department-item {
  display: flex;
  align-items: center;
  padding: 8px 12px;
}

.department-color {
  width: 12px;
  height: 12px;
  border-radius: 3px;
  margin-right: 10px;
}

.empty-state {
  padding: 12px;
  text-align: center;
  color: #777;
  font-style: italic;
  font-size: 0.9rem;
  background-color: #f9f9f9;
  border-radius: 4px;
}

.btn-block {
  width: 100%;
  text-align: center;
}

.mt-2 {
  margin-top: 10px;
}

.sidebar-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-top: 1px solid #eee;
}

.user-info {
  display: flex;
  align-items: center;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: #f0f0f0;
  color: #666;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  margin-right: 10px;
}

.user-name {
  font-size: 0.9rem;
  max-width: 170px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-logout {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  padding: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.btn-logout:hover {
  background-color: #f5f5f5;
  color: #f44336;
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  width: 100%;
  max-width: 400px;
  max-height: 90vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
  color: #666;
}

.modal-body {
  padding: 16px;
  flex: 1;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #4caf50;
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.form-help {
  margin-top: 4px;
  font-size: 0.8rem;
  color: #777;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px;
  border-top: 1px solid #eee;
}

.btn-primary,
.btn-secondary {
  padding: 8px 16px;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-primary {
  background-color: #4caf50;
  color: #fff;
  border: none;
}

.btn-primary:hover {
  background-color: #3d8b40;
}

.btn-primary:disabled {
  background-color: #a5d6a7;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
}

@media (max-width: 768px) {
  .sidebar {
    width: 240px;
  }

  .sidebar:not(.open) {
    transform: translateX(-240px);
  }

  .org-info {
    max-width: 170px;
  }

  .user-name {
    max-width: 130px;
  }
}
</style>