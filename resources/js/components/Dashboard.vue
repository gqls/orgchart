// resources/js/components/Dashboard.vue
<template>
  <div class="dashboard">
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
        <sidebar :organization="organization" :open="sidebarOpen"/>

        <div class="content">
          <div class="metrics-overview">
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
      required: true
    }
  },

  data() {
    return {
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
      error: null,
    };
  },

  async created() {
    try {
      // Verify the organizationId exists before making the API call
      if (this.organizationId) {
        await this.fetchOrganization();
        await this.fetchScenarios();
      } else {
        this.error = "No organization ID provided. Please select an organization from the dashboard.";
        console.error('No organization ID provided');
      }
    } catch (error) {
      this.error = "Error loading organization data. Please try again.";
      console.error('Error initializing Dashboard:', error);
    }
  },

  methods: {
    async fetchOrganization() {
      try {
        const response = await axios.get(`/api/organizations/${this.organizationId}`);
        if (response.data && typeof response.data === 'object') {
          this.organization = response.data;
        } else {
          console.error('Invalid organization data received:', response.data);
          this.organization = {}; // Fallback to empty object
        }
      } catch (error) {
        console.error('Error fetching organization:', error);
        this.organization = {}; // Fallback to empty object
      }
    },

    async fetchScenarios() {
      try {
        const response = await axios.get(`/api/organizations/${this.organizationId}/scenarios`);
        if (Array.isArray(response.data)) {
          this.scenarios = response.data;

          // Find current scenario safely
          const currentScenario = this.scenarios.find(s => s.is_current);
          if (currentScenario) {
            this.currentScenario = currentScenario;
            this.selectedScenarioId = currentScenario.id;
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
      try {
        const response = await axios.get(`/api/organizations/${this.organizationId}/scenarios/${scenarioId}`);
        this.currentScenario = response.data;
        this.fetchDashboardMetrics();
      } catch (error) {
        console.error('Error setting current scenario:', error);
      }
    },

    async fetchDashboardMetrics() {
      if (!this.currentScenario) return;

      try {
        const response = await axios.get(`/api/organizations/${this.organizationId}/scenarios/${this.currentScenario.id}/metrics`);
        this.dashboardMetrics = response.data;

        // Add trend data if we have a comparison scenario (e.g., previous month)
        if (this.scenarios.length > 1) {
          const baseScenario = this.scenarios.find(s => s.is_base);
          if (baseScenario && baseScenario.id !== this.currentScenario.id) {
            const comparisonResponse = await axios.get(`/api/organizations/${this.organizationId}/compare-scenarios`, {
              params: {
                scenario1_id: this.currentScenario.id,
                scenario2_id: baseScenario.id
              }
            });

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
        }
      } catch (error) {
        console.error('Error fetching dashboard metrics:', error);
      }
    },

    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },

    formatMetricValue(metric) {
      if (metric.format === 'currency') {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(metric.pivot.value);
      } else if (metric.format === 'percentage') {
        return `${metric.pivot.value}%`;
      } else if (metric.format === 'number') {
        return new Intl.NumberFormat('en-US').format(metric.pivot.value);
      }

      return metric.pivot.value;
    },

    formatTrendValue(trend) {
      if (trend.percentage) {
        return `${trend.percentage.toFixed(1)}%`;
      }
      return trend.value;
    },

    getMetricClass(metric) {
      if (!metric.pivot.goal) return '';

      const value = metric.pivot.value;
      const goal = metric.pivot.goal;

      if (value >= goal) return 'metric-success';
      if (value >= goal * 0.9) return 'metric-warning';
      return 'metric-danger';
    },

    getTrendClass(trend) {
      if (trend.value > 0) return 'trend-up';
      if (trend.value < 0) return 'trend-down';
      return '';
    },

    getTrendIcon(trend) {
      if (trend.value > 0) return 'fas fa-arrow-up';
      if (trend.value < 0) return 'fas fa-arrow-down';
      return 'fas fa-equals';
    },

    goToDashboard() {
      this.$router.push({ name: 'dashboard' })
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
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

@media (max-width: 768px) {
  .sidebar-open .content {
    margin-left: 0;
  }
}
</style>