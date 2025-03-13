// resources/js/components/ScenarioDetail.vue
<template>
  <div class="scenario-detail">
    <div v-if="loading" class="loading-indicator">
      Loading scenario data...
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>
    <div v-else class="scenario-container">
      <div class="scenario-header">
        <h1>{{ scenario.name }}</h1>
        <div class="scenario-badges">
          <span v-if="scenario.is_current" class="badge current">Current</span>
          <span v-if="scenario.is_base" class="badge base">Base</span>
        </div>
      </div>

      <div class="scenario-description" v-if="scenario.description">
        <p>{{ scenario.description }}</p>
      </div>

      <div class="tabs">
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
        <!-- Positions Tab -->
        <div v-if="activeTab === 'positions'" class="positions-tab">
          <div class="filter-controls">
            <div class="filter-group">
              <label for="position-filter">Filter:</label>
              <input id="position-filter" v-model="filters.search" placeholder="Search positions...">
            </div>
            <div class="filter-group">
              <label for="department-filter">Department:</label>
              <select id="department-filter" v-model="filters.department">
                <option value="">All Departments</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="positions-table">
            <table>
              <thead>
              <tr>
                <th>Title</th>
                <th>Name</th>
                <th>Department</th>
                <th>Function</th>
                <th>Grade</th>
                <th>Cost</th>
                <th>Actions</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="position in filteredPositions" :key="position.id">
                <td>{{ position.title }}</td>
                <td>{{ position.name || 'Vacant' }}</td>
                <td>{{ getDepartmentName(position.department_id) }}</td>
                <td>{{ position.function || '-' }}</td>
                <td>{{ position.grade || '-' }}</td>
                <td>{{ formatCurrency(position.fully_loaded_cost) }}</td>
                <td class="actions">
                  <button @click="editPosition(position)" class="btn-action">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="confirmDeletePosition(position)" class="btn-action btn-danger">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Metrics Tab -->
        <div v-if="activeTab === 'metrics'" class="metrics-tab">
          <div class="metrics-grid">
            <div v-for="metric in metrics" :key="metric.id" class="metric-card">
              <div class="metric-header">
                <h3>{{ metric.name }}</h3>
              </div>
              <div class="metric-value">{{ formatMetricValue(metric) }}</div>
              <div class="metric-goal" v-if="metric.pivot && metric.pivot.goal">
                Goal: {{ formatMetricGoalValue(metric) }}
                <span :class="getGoalClass(metric)">
                  {{ getGoalDifference(metric) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Relationships Tab -->
        <div v-if="activeTab === 'relationships'" class="relationships-tab">
          <p>Reporting relationships visualization coming soon.</p>
        </div>

        <!-- Summary Tab -->
        <div v-if="activeTab === 'summary'" class="summary-tab">
          <div class="summary-grid">
            <div class="summary-card">
              <div class="summary-title">Total Positions</div>
              <div class="summary-value">{{ summary.position_count }}</div>
            </div>
            <div class="summary-card">
              <div class="summary-title">Departments</div>
              <div class="summary-value">{{ summary.department_count }}</div>
            </div>
            <div class="summary-card">
              <div class="summary-title">Total Cost</div>
              <div class="summary-value">{{ formatCurrency(summary.total_cost) }}</div>
            </div>
            <div class="summary-card">
              <div class="summary-title">Reporting Relationships</div>
              <div class="summary-value">{{ summary.relationship_count }}</div>
            </div>
          </div>

          <div class="scenario-metadata">
            <h3>Scenario Information</h3>
            <table>
              <tbody>
                <tr>
                  <td>Created By:</td>
                  <td>{{ scenario.user ? scenario.user.name : 'Unknown' }}</td>
                </tr>
                <tr>
                  <td>Created At:</td>
                  <td>{{ formatDate(scenario.created_at) }}</td>
                </tr>
                <tr>
                  <td>Updated At:</td>
                  <td>{{ formatDate(scenario.updated_at) }}</td>
                </tr>
                <tr>
                  <td>Is Current:</td>
                  <td>{{ scenario.is_current ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                  <td>Is Base Scenario:</td>
                  <td>{{ scenario.is_base ? 'Yes' : 'No' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    id: {
      type: [Number, String],
      required: true
    },
    scenarioId: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      scenario: {},
      positions: [],
      departments: [],
      metrics: [],
      relationships: [],
      summary: {
        position_count: 0,
        department_count: 0,
        total_cost: 0,
        relationship_count: 0
      },
      loading: true,
      error: null,
      activeTab: 'positions',
      tabs: [
        { id: 'positions', name: 'Positions' },
        { id: 'metrics', name: 'Metrics' },
        { id: 'relationships', name: 'Relationships' },
        { id: 'summary', name: 'Summary' }
      ],
      filters: {
        search: '',
        department: ''
      }
    };
  },
  computed: {
    filteredPositions() {
      let filtered = [...this.positions];

      if (this.filters.search) {
        const searchTerm = this.filters.search.toLowerCase();
        filtered = filtered.filter(p =>
            (p.title && p.title.toLowerCase().includes(searchTerm)) ||
            (p.name && p.name.toLowerCase().includes(searchTerm)) ||
            (p.function && p.function.toLowerCase().includes(searchTerm))
        );
      }

      if (this.filters.department) {
        filtered = filtered.filter(p => p.department_id === this.filters.department);
      }

      return filtered;
    }
  },
  created() {
    this.fetchData();
  },
  methods: {
    async fetchData() {
      this.loading = true;
      this.error = null;

      try {
        // Fetch detailed scenario information using the new endpoint
        const detailResponse = await axios.get(
            `/api/organizations/${this.id}/scenarios/${this.scenarioId}/detail`
        );

        // Extract data from the response
        const { scenario, summary } = detailResponse.data;
        this.scenario = scenario;
        this.positions = scenario.positions || [];
        this.metrics = scenario.metrics || [];
        this.relationships = scenario.relationships || [];

        // Store summary data
        this.summary = summary;

        // Fetch departments
        const departmentsResponse = await axios.get(
            `/api/organizations/${this.id}/departments`
        );
        this.departments = departmentsResponse.data;

      } catch (error) {
        console.error('Error fetching scenario data:', error);
        this.error = 'Failed to load scenario data. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    getDepartmentName(departmentId) {
      if (!departmentId) return '-';
      const department = this.departments.find(d => d.id === departmentId);
      return department ? department.name : '-';
    },

    formatCurrency(value) {
      if (!value) return '$0';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      }).format(value);
    },

    formatMetricValue(metric) {
      if (!metric || !metric.pivot) return 'N/A';

      if (metric.format === 'currency' || (metric.code && metric.code.includes('cost'))) {
        return this.formatCurrency(metric.pivot.value);
      } else if (metric.format === 'percentage') {
        return `${metric.pivot.value}%`;
      } else if (metric.code === 'avg_span') {
        return parseFloat(metric.pivot.value).toFixed(2);
      }

      return metric.pivot.value;
    },

    formatMetricGoalValue(metric) {
      if (!metric || !metric.pivot || metric.pivot.goal === undefined) return 'N/A';

      const metricCopy = Object.assign({}, metric);
      const pivotCopy = Object.assign({}, metric.pivot);
      pivotCopy.value = pivotCopy.goal
      metricCopy.pivot = pivotCopy;

      return this.formatMetricValue(metricCopy);
    },

    getGoalClass(metric) {
      if (!metric.pivot || metric.pivot.goal === undefined) return '';

      const value = parseFloat(metric.pivot.value);
      const goal = parseFloat(metric.pivot.goal);

      // For cost metrics, lower is better
      if (metric.code?.includes('cost')) {
        return value <= goal ? 'goal-positive' : 'goal-negative';
      }

      // For other metrics, higher is better
      return value >= goal ? 'goal-positive' : 'goal-negative';
    },

    getGoalDifference(metric) {
      if (!metric.pivot || metric.pivot.goal === undefined) return '';

      const value = parseFloat(metric.pivot.value);
      const goal = parseFloat(metric.pivot.goal);
      const diff = value - goal;

      if (metric.format === 'currency' || metric.code?.includes('cost')) {
        return `(${diff > 0 ? '+' : ''}${this.formatCurrency(diff)})`;
      }

      const percentage = (Math.abs(diff) / goal * 100).toFixed(1);
      return `(${diff > 0 ? '+' : ''}${diff} / ${percentage}%)`;
    },

    editPosition(position) {
      // Implement position editing (could open a modal)
      console.log('Edit position:', position);
    },

    confirmDeletePosition(position) {
      // Implement delete confirmation (could open a modal)
      console.log('Delete position:', position);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';

      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date);
    }
  }
};
</script>

<style scoped>
.scenario-detail {
  padding: 1rem;
}

.scenario-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.scenario-badges {
  display: flex;
  gap: 0.5rem;
}

.badge {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  border-radius: 1rem;
  font-weight: 600;
}

.badge.current {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.badge.base {
  background-color: #e3f2fd;
  color: #1565c0;
}

.scenario-description {
  margin-bottom: 2rem;
  padding: 1rem;
  background-color: #f5f5f5;
  border-radius: 0.5rem;
}

.tabs {
  display: flex;
  border-bottom: 1px solid #ddd;
  margin-bottom: 1.5rem;
}

.tab {
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-weight: 500;
}

.tab.active {
  border-bottom: 2px solid #4caf50;
  color: #4caf50;
}

.filter-controls {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 200px;
}

.filter-group label {
  margin-bottom: 0.25rem;
  font-weight: 500;
  font-size: 0.875rem;
}

.filter-group input,
.filter-group select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.positions-table {
  width: 100%;
  overflow-x: auto;
}

.positions-table table {
  width: 100%;
  border-collapse: collapse;
}

.positions-table th,
.positions-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.positions-table th {
  background-color: #f5f5f5;
  font-weight: 600;
}

.positions-table tr:hover {
  background-color: #f9f9f9;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  color: #555;
}

.btn-action:hover {
  color: #000;
}

.btn-action.btn-danger:hover {
  color: #f44336;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  padding: 1rem;
}

.metric-header h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.1rem;
  color: #555;
}

.metric-value {
  font-size: 2rem;
  font-weight: bold;
  margin: 0.5rem 0;
}

.metric-goal {
  font-size: 0.875rem;
  color: #666;
}

.goal-positive {
  color: #4caf50;
}

.goal-negative {
  color: #f44336;
}

.loading-indicator,
.error-message {
  text-align: center;
  padding: 2rem;
}

.error-message {
  color: #f44336;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  padding: 1.5rem;
  text-align: center;
}

.summary-title {
  font-size: 1rem;
  color: #555;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 2rem;
  font-weight: bold;
  color: #333;
}

.scenario-metadata {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  padding: 1.5rem;
}

.scenario-metadata h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.2rem;
  color: #555;
}

.scenario-metadata table {
  width: 100%;
  border-collapse: collapse;
}

.scenario-metadata td {
  padding: 0.75rem;
  border-bottom: 1px solid #eee;
}

.scenario-metadata tr:last-child td {
  border-bottom: none;
}

.scenario-metadata td:first-child {
  font-weight: 500;
  width: 40%;
}
</style>