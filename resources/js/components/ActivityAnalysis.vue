// resources/js/components/ActivityAnalysis.vue
<template>
  <div class="activity-analysis">
    <div class="analysis-header">
      <h2 class="text-xl font-semibold mb-4">Activity Analysis</h2>
      <div class="filter-controls">
        <div class="date-range">
          <label for="date-range">Time Period:</label>
          <select id="date-range" v-model="dateRange" class="form-control">
            <option value="last-week">Last 7 Days</option>
            <option value="last-month">Last 30 Days</option>
            <option value="last-quarter">Last 90 Days</option>
            <option value="custom">Custom Range</option>
          </select>
        </div>
        <div v-if="dateRange === 'custom'" class="custom-range">
          <div class="date-input">
            <label for="start-date">From:</label>
            <input type="date" id="start-date" v-model="startDate" class="form-control" />
          </div>
          <div class="date-input">
            <label for="end-date">To:</label>
            <input type="date" id="end-date" v-model="endDate" class="form-control" />
          </div>
          <button @click="fetchActivityData" class="btn-primary">Apply</button>
        </div>
      </div>
    </div>

    <div class="activity-summary">
      <div class="summary-card">
        <h3>Total Changes</h3>
        <div class="summary-value">{{ totalChanges }}</div>
        <div class="summary-trend" :class="getChangesTrendClass()">
          <i :class="getChangesTrendIcon()"></i>
          {{ changesTrend }}% vs previous period
        </div>
      </div>
      <div class="summary-card">
        <h3>Active Users</h3>
        <div class="summary-value">{{ activeUsers }}</div>
        <div class="summary-trend" :class="getUsersTrendClass()">
          <i :class="getUsersTrendIcon()"></i>
          {{ usersTrend }}% vs previous period
        </div>
      </div>
      <div class="summary-card">
        <h3>Most Active Department</h3>
        <div class="summary-value">{{ mostActiveDepartment }}</div>
        <div class="summary-detail">{{ mostActiveDepartmentChanges }} changes</div>
      </div>
    </div>

    <div class="chart-container">
      <h3>Activity Over Time</h3>
      <canvas ref="activityChart"></canvas>
    </div>

    <div class="activity-logs">
      <h3>Recent Activity</h3>
      <div class="filter-row">
        <div class="filter-group">
          <label for="user-filter">User:</label>
          <select id="user-filter" v-model="userFilter" class="form-control">
            <option value="">All Users</option>
            <option v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </option>
          </select>
        </div>
        <div class="filter-group">
          <label for="action-filter">Action:</label>
          <select id="action-filter" v-model="actionFilter" class="form-control">
            <option value="">All Actions</option>
            <option value="create">Create</option>
            <option value="update">Update</option>
            <option value="delete">Delete</option>
          </select>
        </div>
      </div>

      <table class="activity-table">
        <thead>
        <tr>
          <th>Timestamp</th>
          <th>User</th>
          <th>Action</th>
          <th>Description</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(log, index) in filteredLogs" :key="index">
          <td>{{ formatDate(log.created_at) }}</td>
          <td>{{ log.user ? log.user.name : 'System' }}</td>
          <td>
              <span class="action-badge" :class="getActionClass(log.action)">
                {{ capitalizeFirst(log.action) }}
              </span>
          </td>
          <td>{{ getActivityDescription(log) }}</td>
        </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="totalPages > 1">
        <button @click="prevPage" :disabled="currentPage === 1" class="btn-secondary">Previous</button>
        <span>Page {{ currentPage }} of {{ totalPages }}</span>
        <button @click="nextPage" :disabled="currentPage === totalPages" class="btn-secondary">Next</button>
      </div>
    </div>
  </div>
</template>

<script>
import Chart from 'chart.js/auto';

export default {
  props: {
    organization: {
      type: Object,
      required: true,
      default: () => ({})
    },
    scenario: {
      type: Object,
      required: true,
      default: () => ({})
    }
  },

  data() {
    return {
      dateRange: 'last-month',
      startDate: this.getDefaultStartDate(),
      endDate: this.getDefaultEndDate(),
      activityLogs: [],
      users: [],
      totalChanges: 0,
      changesTrend: 0,
      activeUsers: 0,
      usersTrend: 0,
      mostActiveDepartment: 'None',
      mostActiveDepartmentChanges: 0,
      userFilter: '',
      actionFilter: '',
      currentPage: 1,
      itemsPerPage: 10,
      chart: null
    };
  },

  computed: {
    filteredLogs() {
      let filtered = this.activityLogs;

      if (this.userFilter) {
        filtered = filtered.filter(log => log.user_id === this.userFilter);
      }

      if (this.actionFilter) {
        filtered = filtered.filter(log => log.action === this.actionFilter);
      }

      // Pagination
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return filtered.slice(start, end);
    },

    totalPages() {
      return Math.ceil(this.activityLogs.length / this.itemsPerPage);
    }
  },

  watch: {
    dateRange(newVal) {
      if (newVal !== 'custom') {
        this.updateDateRange();
        this.fetchActivityData();
      }
    },

    organization: {
      immediate: true,
      handler() {
        this.fetchActivityData();
        this.fetchUsers();
      }
    },

    scenario: {
      handler() {
        this.fetchActivityData();
      }
    }
  },

  methods: {
    getDefaultStartDate() {
      const date = new Date();
      date.setDate(date.getDate() - 30);
      return date.toISOString().split('T')[0];
    },

    getDefaultEndDate() {
      return new Date().toISOString().split('T')[0];
    },

    updateDateRange() {
      const now = new Date();
      let start = new Date();

      switch (this.dateRange) {
        case 'last-week':
          start.setDate(now.getDate() - 7);
          break;
        case 'last-month':
          start.setDate(now.getDate() - 30);
          break;
        case 'last-quarter':
          start.setDate(now.getDate() - 90);
          break;
      }

      this.startDate = start.toISOString().split('T')[0];
      this.endDate = now.toISOString().split('T')[0];
    },

    async fetchActivityData() {
      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/activity-logs`, {
          params: {
            start_date: this.startDate,
            end_date: this.endDate,
            scenario_id: this.scenario ? this.scenario.id : null
          }
        });

        this.activityLogs = response.data.logs;
        this.totalChanges = response.data.summary.total_changes;
        this.changesTrend = response.data.summary.change_percentage;
        this.activeUsers = response.data.summary.active_users;
        this.usersTrend = response.data.summary.users_percentage;
        this.mostActiveDepartment = response.data.summary.most_active_department;
        this.mostActiveDepartmentChanges = response.data.summary.department_changes;

        this.$nextTick(() => {
          this.initChart(response.data.chart_data);
        });
      } catch (error) {
        console.error('Error fetching activity data:', error);
      }
    },

    async fetchUsers() {
      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/users`);
        this.users = response.data;
      } catch (error) {
        console.error('Error fetching users:', error);
      }
    },

    initChart(chartData) {
      const ctx = this.$refs.activityChart.getContext('2d');

      // Destroy previous chart if exists
      if (this.chart) {
        this.chart.destroy();
      }

      this.chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: chartData.labels,
          datasets: [
            {
              label: 'Changes',
              data: chartData.changes,
              borderColor: '#4caf50',
              backgroundColor: 'rgba(76, 175, 80, 0.1)',
              tension: 0.1,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0
              }
            }
          }
        }
      });
    },

    formatDate(dateStr) {
      const date = new Date(dateStr);
      return new Intl.DateTimeFormat('en-US', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }).format(date);
    },

    getActionClass(action) {
      switch (action) {
        case 'create':
          return 'create';
        case 'update':
          return 'update';
        case 'delete':
          return 'delete';
        default:
          return '';
      }
    },

    getActivityDescription(log) {
      let entity = log.loggable_type.split('\\').pop();

      switch (log.action) {
        case 'create':
          return `Created a new ${entity.toLowerCase()}`;
        case 'update':
          return `Updated ${entity.toLowerCase()} details`;
        case 'delete':
          return `Deleted a ${entity.toLowerCase()}`;
        default:
          return `Action on ${entity.toLowerCase()}`;
      }
    },

    capitalizeFirst(str) {
      if (!str) return '';
      return str.charAt(0).toUpperCase() + str.slice(1);
    },

    getChangesTrendClass() {
      return this.changesTrend >= 0 ? 'trend-up' : 'trend-down';
    },

    getChangesTrendIcon() {
      return this.changesTrend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
    },

    getUsersTrendClass() {
      return this.usersTrend >= 0 ? 'trend-up' : 'trend-down';
    },

    getUsersTrendIcon() {
      return this.usersTrend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
    },

    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--;
      }
    },

    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++;
      }
    }
  }
};
</script>

<style scoped>
.activity-analysis {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.analysis-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.filter-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.custom-range {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.activity-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.summary-card {
  background-color: white;
  border-radius: 0.5rem;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
}

.summary-card h3 {
  font-size: 1rem;
  color: #666;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 2rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
}

.summary-trend {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.trend-up {
  color: #4caf50;
}

.trend-down {
  color: #f44336;
}

.chart-container {
  background-color: white;
  border-radius: 0.5rem;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
}

.chart-container h3 {
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.activity-logs {
  background-color: white;
  border-radius: 0.5rem;
  padding: 1.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
}

.activity-logs h3 {
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.filter-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.activity-table {
  width: 100%;
  border-collapse: collapse;
}

.activity-table th,
.activity-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.activity-table th {
  font-weight: 600;
  color: #666;
}

.action-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.action-badge.create {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.action-badge.update {
  background-color: #e3f2fd;
  color: #1565c0;
}

.action-badge.delete {
  background-color: #ffebee;
  color: #c62828;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
}

@media (max-width: 768px) {
  .filter-controls,
  .custom-range,
  .filter-row {
    flex-direction: column;
    align-items: flex-start;
  }

  .activity-table {
    display: block;
    overflow-x: auto;
  }
}
</style>