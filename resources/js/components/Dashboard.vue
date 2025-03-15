// resources/js/components/Dashboard.vue
<template>
  <div class="dashboard">
    <!-- empty dashboard section with demo info -->
    <div v-if="!organizationId" class="empty-dashboard">
      <div class="welcome-message">
        <h1>Welcome to OrgChart</h1>
        <p>Select an organization to get started or create a new one.</p>
      </div>

      <div v-if="loading" class="loading-indicator">
        <p>Loading your organizations...</p>
      </div>
      <div v-else-if="error" class="error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <p>{{ error }}</p>
      </div>
      <div v-else-if="organizations.length === 0" class="empty-state">
        <p>You don't have any organizations yet.</p>
        <router-link to="/organizations/create" class="btn-primary">
          <i class="fas fa-plus"></i> Create Your First Organization
        </router-link>
      </div>
      <div v-else class="organization-grid">
        <div v-for="org in organizations" :key="org.id" class="organization-card" @click="selectOrganization(org)">
          <div class="org-logo" :style="getOrgLogoStyle(org)">
            {{ org && org.name && !org.logo_path ? org.name.charAt(0) : '' }}
          </div>
          <div class="org-info">
            <h3>{{ org && org.name ? org.name : 'Unnamed Organisation' }}</h3>
            <p v-if="org && org.description" class="org-description">{{ org.description }}</p>
            <div v-if="org && org.name === 'Demo Organisation'" class="demo-badge">
              Demo
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Organization dashboard when an organization is selected -->
    <template v-else>
      <!-- Show error message if there is one -->
      <div v-if="error" class="error-container">
        <div class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          <p>{{ error }}</p>
          <button class="btn-primary" @click="goToDashboard">
            Return to Dashboard
          </button>
        </div>
      </div>
      <template v-else>
        <div class="dashboard-header">
          <h1>{{ organization.name }}</h1>
          <div class="dashboard-actions">
            <button @click="toggleSidebar" class="btn-toggle">
              <i class="fas fa-bars"></i>
            </button>
          </div>
        </div>

        <div class="main-container" :class="{ 'sidebar-open': sidebarOpen }">
          <sidebar
              :organization="organization"
              :open="sidebarOpen"
              @error="handleError"
          />

          <div class="content">
            <div v-if="dashboardMetrics.length > 0" class="metrics-overview">
              <div class="metric-card" v-for="metric in dashboardMetrics" :key="metric.id">
                <div class="metric-value" :class="getMetricClass(metric)">
                  {{ formatMetricValue(metric) }}
                  <span v-if="metric.trend" class="trend-indicator" :class="getTrendClass(metric.trend)">
                    <i :class="getTrendIcon(metric.trend)"></i>
                    {{ formatTrendValue(metric.trend) }}
                  </span>
                </div>
                <div class="metric-label">{{ metric.name }}</div>
              </div>
            </div>

            <div class="tab-container">
              <div class="tab-header">
                <div
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="['tab', { active: activeTab === tab.id }]"
                >
                  {{ tab.name }}
                </div>
              </div>

              <div class="tab-content">
                <div v-if="activeTab === 'as-is-analysis'" class="as-is-analysis">
                  <as-is-analysis :organization="organization" :scenario="currentScenario"/>
                </div>
                <div v-if="activeTab === 'to-be-modeling'" class="to-be-modeling">
                  <to-be-modeling :organization="organization" :scenario="currentScenario"/>
                </div>
                <div v-if="activeTab === 'org-chart'" class="org-chart">
                  <org-chart :organization="organization" :scenario="currentScenario"/>
                </div>
                <div v-if="activeTab === 'activity-analysis'" class="activity-analysis">
                  <activity-analysis :organization="organization" :scenario="currentScenario"/>
                </div>
                <div v-if="activeTab === 'hr-data'" class="hr-data">
                  <hr-data :organization="organization" :scenario="currentScenario"/>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </template>
  </div>
</template>

<script>
import Sidebar from './Sidebar.vue';
import AsIsAnalysis from './AsIsAnalysis.vue';
import ToBeModeling from './ToBeModeling.vue';
import OrgChart from './OrgChart.vue';
import ActivityAnalysis from './ActivityAnalysis.vue';
import HrData from './HrData.vue';
import Header from "./Header.vue";
import Login from "./Login.vue";
import OrganizationCreate from "./OrganizationCreate.vue";
import OrganizationsList from "./OrganizationsList.vue";
import Register from "./Register.vue";
import ThemeService from "../services/ThemeService";

export default {
  components: {
    Sidebar,
    AsIsAnalysis,
    ToBeModeling,
    OrgChart,
    ActivityAnalysis,
    HrData,
    Header,
    Login,
    OrganizationCreate,
    OrganizationsList,
    Register,
  },

  props: {
    organizationId: {
      type: [Number, String],
      required: false,
      default: null
    }
  },

  data() {
    return {
      organizations: [],
      organization: {},
      scenarios: [],
      currentScenario: null,
      dashboardMetrics: [],
      sidebarOpen: true,
      activeTab: 'as-is-analysis',
      tabs: [
        { id: 'hr-data', name: 'HR Data' },
        { id: 'org-chart', name: 'Org Chart' },
        { id: 'as-is-analysis', name: 'As Is Analysis' },
        { id: 'activity-analysis', name: 'Activity Analysis' },
        { id: 'to-be-modeling', name: 'To Be Modeling' },
      ],
      loading: false,
      error: null,
    };
  },

  watch: {
    organizationId: {
      immediate: true,
      handler(newVal, oldVal) {
        if (newVal && newVal !== oldVal) {
          console.log("organizationId changed from", oldVal, "to", newVal);
          this.loading = true;
          this.error = null;
          this.organization = {};
          this.fetchOrganization()
              .then(() => {
                if (this.organization && this.organization.id) {
                  return Promise.all([
                    this.fetchScenarios(),
                    this.fetchDepartments()
                  ]);
                }
              })
              .catch(error => {
                console.error("Error loading organization data:", error);
                this.error = "Error loading organization data. Please try again.";
              })
              .finally(() => {
                this.loading = false;
              });
        } else if (!newVal) {
          // If no organization ID, fetch the list of organizations
          this.fetchOrganizations();
        }
      }
    }
  },

  async created() {

    console.log("Component created with organizationId:", this.organizationId);
    if (this.organizationId) {
      try {
        this.loading = true;
        await this.fetchOrganization();

        // Only fetch dependent data after the organization is loaded
        if (this.organization && this.organization.id) {
          await this.fetchScenarios();
          await this.fetchDepartments();
        }
      } catch (error) {
        this.error = "Error loading organization data. Please try again.";
      } finally {
        this.loading = false;
      }
    } else {
      // If no organization ID, fetch the list of organizations
      console.log("No organisation Id, so fetching all organisations.")
      await this.fetchOrganizations();
    }
  },

  methods: {
    handleError(error) {
      this.error = error;
    },

    async checkAuth() {
      try {
        const response = await axios.get('/api/user');
        console.log('User authentication status:', response.data);
        return true;
      } catch (error) {
        console.error('Authentication check failed:', error);
        return false;
      }
    },

    async fetchOrganizations() {
      this.loading = true;
      this.error = null;

      const isAuthenticated = await this.checkAuth();
      if (!isAuthenticated) {
        this.error = 'You must be logged in to view organizations';
        this.loading = false;
        return;
      } else {
        console.log('you are logged in');
      }

      try {
        console.log('Fetching organizations...');
        const response = await axios.get('/api/organizations');
        console.log('Raw API response:', response);

        if (Array.isArray(response.data)) {
          console.log('Response is an array with length:', response.data.length);
          this.organizations = response.data;
        } else if (response.data && typeof response.data === 'object') {
          console.log('Response is an object with keys:', Object.keys(response.data));
          // Handle case where response might be wrapped
          this.organizations = Array.isArray(response.data.data) ? response.data.data : [response.data];
        } else {
          console.error('Unexpected API response format:', response.data);
          this.organizations = [];
          this.error = 'Received unexpected data format from the server';
        }

        console.log('Final organizations array:', this.organizations);
      } catch (error) {
        console.error('Error fetching organizations:', error);
        this.error = 'Failed to load your organizations. Please try again.';
        this.organizations = [];
      } finally {
        this.loading = false;
      }
    },

    async fetchOrganization() {

      console.log("Fetching organization data for ID:", this.organizationId);

      if (!this.organizationId) {
        console.error('No organization ID provided');
        this.error = "Invalid organization ID";
        return;
      }

      try {
        const response = await axios.get(`/api/organizations/${this.organizationId}`);
        if (response.data && typeof response.data === 'object') {
          this.organization = response.data;

          // Apply theming if the service exists
          if (typeof ThemeService !== 'undefined' && ThemeService.setTheme) {
            ThemeService.setTheme(this.organization);
          }
        } else {
          console.error('Invalid organization data received:', response.data);
          this.organization = {}; // Fallback to empty object
          this.error = "Invalid organization data received.";
        }
      } catch (error) {
        console.error('Error fetching organization:', error);
        this.organization = {}; // Fallback to empty object
        this.error = "Failed to load organization details.";
      } finally {
        this.loading = false;
      }
    },

    async fetchScenarios() {
      if (!this.organization || !this.organization.id) {
        console.error('Cannot fetch scenarios without a valid organization');
        return;
      }

      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/scenarios`);
        if (Array.isArray(response.data)) {
          this.scenarios = response.data;

          // Find current scenario safely
          const currentScenario = this.scenarios.find(s => s.is_current);
          if (currentScenario) {
            this.currentScenario = currentScenario;
            this.selectedScenarioId = currentScenario.id;
            await this.fetchDashboardMetrics();
          }
        } else {
          console.error('Invalid scenarios data received:', response.data);
          this.scenarios = []; // Fallback to empty array
        }
      } catch (error) {
        console.error('Error fetching scenarios:', error);
        this.scenarios = []; // Fallback to empty array
      }
    },

    async setCurrentScenario(scenarioId) {
      if (!this.organization || !this.organization.id) {
        console.error('Cannot set current scenario without a valid organization');
        return;
      }

      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/scenarios/${scenarioId}`);
        this.currentScenario = response.data;
        this.fetchDashboardMetrics();
      } catch (error) {
        console.error('Error setting current scenario:', error);
      }
    },

    async fetchDepartments() {
      if (!this.organization || !this.organization.id) {
        console.error('Cannot fetch departments without a valid organization');
        return;
      }

      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/departments`);
        this.departments = response.data;
      } catch (error) {
        console.error('Error fetching departments:', error);
        this.departments = [];
        throw error;
      }
    },

    async fetchDashboardMetrics() {
      if (!this.organization || !this.organization.id || !this.currentScenario || !this.currentScenario.id) {
        console.error('Cannot fetch metrics without a valid organization and scenario');
        this.dashboardMetrics = [];
        return;
      }

      try {
        const response = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.currentScenario.id}/metrics`
        );

        if (!Array.isArray(response.data)) {
          console.warn('Unexpected metrics data format:', response.data);
          this.dashboardMetrics = [];
          return;
        }

        this.dashboardMetrics = response.data;

        // Add trend data if we have a comparison scenario (e.g., previous month)
        if (this.scenarios.length > 1) {
          const baseScenario = this.scenarios.find(s => s.is_base);
          if (baseScenario && baseScenario.id !== this.currentScenario.id) {
            try {
              const comparisonResponse = await axios.get(
                  `/api/organizations/${this.organization.id}/compare-scenarios`,
                  {
                    params: {
                      scenario1_id: this.currentScenario.id,
                      scenario2_id: baseScenario.id
                    }
                  }
              );

              if (comparisonResponse.data && comparisonResponse.data.comparison) {
                const comparison = comparisonResponse.data.comparison;

                // Add trend data to metrics
                this.dashboardMetrics.forEach(metric => {
                  const compMetric = comparison.find(m => m.code === metric.code);
                  if (compMetric) {
                    metric.trend = {
                      value: compMetric.difference,
                      percentage: compMetric.difference_percentage
                    };
                  }
                });
              }
            } catch (error) {
              console.error('Error comparing scenarios:', error);
            }
          }
        }
      } catch (error) {
        console.error('Error fetching dashboard metrics:', error);
        this.dashboardMetrics = [];
      }
    },

    selectOrganization(org) {
      // Check if org exists and has an id before navigating
      if (org && org.id) {
        this.$router.push({ name: 'organizations.dashboard', params: { id: org.id } });
      } else {
        console.error('Cannot navigate to organization: Invalid organization or missing ID', org);
        // Optionally show error to user
        this.error = 'Unable to select organization. Please try again or contact support.';
      }
    },

    getOrgLogoStyle(org) {
      if (!org) return {};

      if (org.logo_path) {
        const logoUrl = org.logo_path.startsWith('http')
            ? org.logo_path
            : `/storage/${org.logo_path}`;
        return { backgroundImage: `url(${logoUrl})` };
      } else {
        return { backgroundColor: org.primary_color || '#4caf50' };
      }
    },

    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },

    formatMetricValue(metric) {
      if (!metric || !metric.pivot || metric.pivot.value === undefined || metric.pivot.value === null) return 'N/A';

      try {
        if (metric.format === 'currency') {
          return new Intl.NumberFormat('en-UK', { style: 'currency', currency: 'GBP' }).format(metric.pivot.value);
        } else if (metric.format === 'percentage') {
          return `${metric.pivot.value}%`;
        } else if (metric.format === 'number') {
          return new Intl.NumberFormat('en-UK').format(metric.pivot.value);
        }

        return metric.pivot.value.toString();
      } catch (e) {
        console.error('Error formatting metric value:', e);
        return 'Error';
      }
    },

    formatTrendValue(trend) {
      if (!trend || trend.percentage === undefined || trend.percentage === null) {
        return '';
      }

      try {
        if (typeof trend.percentage === 'number') {
          return `${trend.percentage.toFixed(1)}%`;
        } else if (typeof trend.value !== 'undefined') {
          return trend.value.toString();
        }
        return '';
      } catch (e) {
        console.error('Error formatting trend value:', e);
        return '';
      }
    },

    getMetricClass(metric) {
      if (!metric || !metric.pivot || !metric.pivot.goal) return '';

      try {
        const value = parseFloat(metric.pivot.value);
        const goal = parseFloat(metric.pivot.goal);

        if (isNaN(value) || isNaN(goal)) return '';

        if (value >= goal) return 'metric-success';
        if (value >= goal * 0.9) return 'metric-warning';
        return 'metric-danger';
      } catch (e) {
        console.error('Error determining metric class:', e);
        return '';
      }
    },

    getTrendClass(trend) {
      if (!trend) return '';

      if (trend.value > 0) return 'trend-up';
      if (trend.value < 0) return 'trend-down';
      return '';
    },

    getTrendIcon(trend) {
      if (!trend) return '';

      if (trend.value > 0) return 'fas fa-arrow-up';
      if (trend.value < 0) return 'fas fa-arrow-down';
      return 'fas fa-equals';
    },

    goToDashboard() {
      this.$router.push({ name: 'dashboard' });
    }
  }
};
</script>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

/* Empty Dashboard Styles */
.empty-dashboard {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
  width: 100%;
}

.welcome-message {
  text-align: center;
  margin-bottom: 3rem;
}

.welcome-message h1 {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.welcome-message p {
  font-size: 1.1rem;
  color: var(--text-secondary);
}

.organization-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  width: 100%;
}

.organization-card {
  display: flex;
  background-color: white;
  border-radius: 0.75rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.organization-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.org-logo {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: bold;
  color: white;
  background-size: cover;
  background-position: center;
  margin-right: 1rem;
  flex-shrink: 0;
}

.org-info {
  flex: 1;
}

.org-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.2rem;
}

.org-description {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  background-color: #f9f9f9;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  width: 100%;
}

.empty-state p {
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
  color: var(--text-secondary);
}

/* Organization Dashboard Styles */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
  background-color: #fff;
  border-bottom: 1px solid #ddd;
}

.main-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.content {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
}

.metrics-overview {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.metric-card {
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.metric-value {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.metric-success {
  color: #4caf50;
}

.metric-warning {
  color: #ff9800;
}

.metric-danger {
  color: #f44336;
}

.metric-label {
  font-size: 0.875rem;
  color: #666;
}

.trend-indicator {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  margin-left: 0.5rem;
}

.trend-up {
  color: #4caf50;
}

.trend-down {
  color: #f44336;
}

.loading-indicator {
  text-align: center;
  padding: 2rem;
  color: var(--text-secondary);
}

.error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f5f5f5;
}

.error-message {
  text-align: center;
  background-color: white;
  padding: 2rem;
  border-radius: 0.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  max-width: 500px;
}

.error-message i {
  font-size: 3rem;
  color: #f44336;
  margin-bottom: 1rem;
}

.error-message p {
  margin-bottom: 1.5rem;
  color: #333;
  font-size: 1.1rem;
}

.error-message button {
  margin-top: 1rem;
}

.demo-badge {
  display: inline-block;
  background-color: #3498db;
  color: white;
  font-size: 0.7rem;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  margin-top: 0.5rem;
}

.organization-card.demo-card {
  border: 2px dashed #3498db;
  position: relative;
}

.demo-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: rgba(52, 152, 219, 0.1);
  padding: 0.5rem;
  font-size: 0.8rem;
  color: #666;
  border-top: 1px solid rgba(52, 152, 219, 0.3);
}


.tab-container {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.tab-header {
  display: flex;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.tab {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.tab:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.tab.active {
  background-color: #fff;
  border-bottom: 2px solid #4caf50;
  font-weight: bold;
}

.tab-content {
  padding: 1rem;
  min-height: 400px;
}

.sidebar-open .content {
  margin-left: 250px;
}

.btn-primary {
  display: inline-block;
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 0.25rem;
  font-weight: 500;
  cursor: pointer;
  text-decoration: none;
  font-size: 1rem;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: #388e3c;
  text-decoration: none;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
  background-color: #fff;
  border-bottom: 1px solid #ddd;
}

.dashboard-logo {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}

.header-logo {
  height: 40px;
  width: auto;
}

/* Make sure the header has position relative for absolute positioning of logo */
.dashboard-header {
  position: relative;
}

@media (max-width: 768px) {
  .sidebar-open .content {
    margin-left: 0;
  }

  .organization-grid {
    grid-template-columns: 1fr;
  }
}
</style>