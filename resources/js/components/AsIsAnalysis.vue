// resources/js/components/AsIsAnalysis.vue
<template>
  <div class="as-is-analysis">
    <div class="metrics-tabs">
      <div
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="['tab', { active: activeTab === tab.id }]"
      >
        {{ tab.name }}
      </div>
    </div>

    <div class="metrics-content">
      <!-- Dashboard Overview -->
      <div v-if="activeTab === 'dashboard'" class="dashboard-view">
        <div class="key-metrics">
          <div class="metric-card" v-for="metric in keyMetrics" :key="metric.code">
            <div class="metric-header">
              <h3>{{ metric.name }}</h3>
              <div class="metric-trend" v-if="metric.trend" :class="getTrendClass(metric.trend)">
                <i :class="getTrendIcon(metric.trend)"></i>
                {{ formatTrendValue(metric.trend) }}
              </div>
            </div>
            <div class="metric-value">{{ formatMetricValue(metric) }}</div>
            <div class="metric-goal" v-if="metric.pivot && metric.pivot.goal">
              Goal: {{ formatMetricGoal(metric) }}
            </div>
          </div>
        </div>

        <div class="chart-container">
          <h3>Department Cost Breakdown</h3>
          <canvas ref="costByDeptChart"></canvas>
        </div>

        <div class="chart-container">
          <h3>FTE vs. Fully Loaded Cost Trends</h3>
          <canvas ref="fteCostTrendChart"></canvas>
        </div>
      </div>

      <!-- Position Analysis -->
      <div v-if="activeTab === 'position'" class="position-view">
        <div class="filter-row">
          <div class="filter-group">
            <label for="position-department-filter">Department:</label>
            <select id="position-department-filter" v-model="filters.department">
              <option value="">All Departments</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label for="position-function-filter">Function:</label>
            <select id="position-function-filter" v-model="filters.function">
              <option value="">All Functions</option>
              <option v-for="func in functions" :key="func" :value="func">
                {{ func }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label for="position-status-filter">Status:</label>
            <select id="position-status-filter" v-model="filters.status">
              <option value="">All Statuses</option>
              <option value="unchanged">Unchanged</option>
              <option value="new">New</option>
              <option value="changed">Changed</option>
              <option value="removed">Removed</option>
            </select>
          </div>
        </div>

        <div class="position-tracking">
          <div class="tracking-summary">
            <div class="summary-item">
              <div class="summary-title">Unchanged Positions</div>
              <div class="summary-value">{{ getStatusCount('unchanged') }}</div>
            </div>
            <div class="summary-item new">
              <div class="summary-title">New Positions</div>
              <div class="summary-value">{{ getStatusCount('new') }}</div>
            </div>
            <div class="summary-item changed">
              <div class="summary-title">Changed Positions</div>
              <div class="summary-value">{{ getStatusCount('changed') }}</div>
            </div>
            <div class="summary-item removed">
              <div class="summary-title">Removed Positions</div>
              <div class="summary-value">{{ getStatusCount('removed') }}</div>
            </div>
          </div>

          <div class="position-diagram">
            <canvas ref="positionTrackingChart"></canvas>
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
              <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="position in filteredPositions" :key="position.id" :class="position.pivot ? position.pivot.status : ''">
              <td>{{ position.title }}</td>
              <td>{{ position.name || 'Vacant' }}</td>
              <td>{{ getDepartmentName(position.department_id) }}</td>
              <td>{{ position.function || '-' }}</td>
              <td>{{ position.grade || '-' }}</td>
              <td>{{ formatCurrency(position.fully_loaded_cost) }}</td>
              <td>
                  <span
                      class="status-badge"
                      :class="position.pivot ? position.pivot.status : ''"
                  >
                    {{ position.pivot ? position.pivot.status : 'unchanged' }}
                  </span>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Spans & Layers -->
      <div v-if="activeTab === 'spans'" class="spans-view">
        <div class="spans-summary">
          <div class="summary-card">
            <h3>Average Span of Control</h3>
            <div class="summary-content">
              <div class="summary-main">{{ getMetricValue('avg_span').toFixed(2) }}</div>
              <div class="summary-details">
                <div>Total People: {{ getMetricValue('headcount') }}</div>
                <div>Total Managers: {{ getMetricValue('total_managers') }}</div>
              </div>
            </div>
          </div>

          <div class="summary-card">
            <h3>Organizational Layers</h3>
            <div class="summary-content">
              <div class="summary-main">{{ getMetricValue('total_layers') }}</div>
              <div class="summary-details">
                <div>Roles per Layer (avg): {{ (getMetricValue('headcount') / getMetricValue('total_layers')).toFixed(1) }}</div>
              </div>
            </div>
          </div>

          <div class="summary-card">
            <h3>Layer Efficiency</h3>
            <div class="summary-content">
              <div class="summary-main">{{ ((getMetricValue('headcount') - getMetricValue('total_managers')) / getMetricValue('headcount') * 100).toFixed(1) }}%</div>
              <div class="summary-details">
                <div>Individual Contributors: {{ getMetricValue('headcount') - getMetricValue('total_managers') }}</div>
              </div>
            </div>
          </div>
        </div>

        <div class="spans-charts">
          <div class="chart-container">
            <h3>Spans & Layers Structure</h3>
            <canvas ref="spansLayersChart" height="300"></canvas>
          </div>

          <div class="chart-container">
            <h3>Summary of Spans and Layers</h3>
            <table class="spans-table">
              <thead>
              <tr>
                <th>Layer</th>
                <th>No. of Roles</th>
                <th>Avg. Span</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="(layer, index) in layerData" :key="index">
                <td>{{ index + 1 }}</td>
                <td>{{ layer.roles }}</td>
                <td>{{ layer.avgSpan.toFixed(2) }}</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Cost Analysis -->
      <div v-if="activeTab === 'cost'" class="cost-view">
        <div class="cost-summary">
          <div class="summary-card">
            <h3>Total Fully Loaded Cost</h3>
            <div class="summary-value">{{ formatCurrency(getMetricValue('total_fully_loaded_cost')) }}</div>
            <div class="summary-comparison" v-if="metrics.find(m => m.code === 'total_fully_loaded_cost').pivot.goal">
              vs Goal: {{ formatCurrency(metrics.find(m => m.code === 'total_fully_loaded_cost').pivot.goal) }}
              <span :class="getGoalClass('total_fully_loaded_cost')">
                {{ getGoalDifference('total_fully_loaded_cost') }}
              </span>
            </div>
          </div>

          <div class="summary-card">
            <h3>Average Cost per FTE</h3>
            <div class="summary-value">{{ formatCurrency(getMetricValue('avg_cost_per_fte')) }}</div>
          </div>
        </div>

        <div class="chart-container">
          <h3>Cost Distribution by Department</h3>
          <canvas ref="costByDepartmentChart"></canvas>
        </div>

        <div class="chart-container">
          <h3>Cost Distribution by Function</h3>
          <canvas ref="costByFunctionChart"></canvas>
        </div>

        <div class="chart-container">
          <h3>Cost Distribution by Grade</h3>
          <canvas ref="costByGradeChart"></canvas>
        </div>
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
      required: true
    },
    scenario: {
      type: Object,
      required: true
    }
  },

  data() {
    return {
      activeTab: 'dashboard',
      tabs: [
        { id: 'dashboard', name: 'Dashboard' },
        { id: 'position', name: 'Position Tracking' },
        { id: 'spans', name: 'Spans & Layers' },
        { id: 'cost', name: 'Cost Analysis' }
      ],
      positions: [],
      departments: [],
      functions: [],
      metrics: [],
      filters: {
        department: '',
        function: '',
        status: ''
      },
      charts: {},
      layerData: []
    };
  },

  computed: {
    keyMetrics() {
      return this.metrics.filter(metric =>
          ['headcount', 'total_fully_loaded_cost', 'avg_span', 'total_managers'].includes(metric.code)
      );
    },

    filteredPositions() {
      return this.positions.filter(position => {
        // Department filter
        if (this.filters.department && position.department_id != this.filters.department) {
          return false;
        }

        // Function filter
        if (this.filters.function && position.function !== this.filters.function) {
          return false;
        }

        // Status filter
        if (this.filters.status && (!position.pivot || position.pivot.status !== this.filters.status)) {
          return false;
        }

        return true;
      });
    }
  },

  watch: {
    scenario: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.loadData();
        }
      }
    },

    activeTab(newTab) {
      this.$nextTick(() => {
        this.initCharts();
      });
    }
  },

  methods: {

    async loadData() {
      try {
        // Fetch positions
        const positionsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.scenario.id}/positions`
        );
        this.positions = positionsResponse.data;

        // Fetch departments
        const departmentsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/departments`
        );
        this.departments = departmentsResponse.data;

        // Extract unique functions
        this.functions = [...new Set(this.positions.filter(p => p.function).map(p => p.function))];

        // Fetch metrics
        const metricsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.scenario.id}/metrics`
        );
        this.metrics = metricsResponse.data;

        // Calculate layer data for spans & layers view
        this.calculateLayerData();

        // Initialize charts after data is loaded
        this.$nextTick(() => {
          this.initCharts();
        });

      } catch (error) {
        console.error('Error loading analysis data:', error);
      }
    },

    calculateLayerData() {
      const maxLayers = this.getMetricValue('total_layers');
      this.layerData = [];

      // This would normally come from the backend
      // Mocking the data for demonstration
      for (let i = 0; i < maxLayers; i++) {
        const layer = {
          roles: Math.round(this.getMetricValue('headcount') * Math.pow(0.7, i)),
          avgSpan: i === maxLayers - 1 ? 0 : 2 + Math.random() * 3
        };
        this.layerData.push(layer);
      }
    },

    initCharts() {
      // Clear existing charts
      Object.values(this.charts).forEach(chart => {
        if (chart) chart.destroy();
      });

      // Initialize only charts for the active tab
      switch (this.activeTab) {
        case 'dashboard':
          this.initDashboardCharts();
          break;
        case 'position':
          this.initPositionCharts();
          break;
        case 'spans':
          this.initSpansCharts();
          break;
        case 'cost':
          this.initCostCharts();
          break;
      }
    },

    initDashboardCharts() {
      // Department Cost Breakdown
      const deptCostCtx = this.$refs.costByDeptChart?.getContext('2d');
      if (deptCostCtx) {
        const deptData = this.getDepartmentCostData();

        this.charts.deptCost = new Chart(deptCostCtx, {
          type: 'bar',
          data: {
            labels: deptData.labels,
            datasets: [{
              label: 'Department Cost',
              data: deptData.values,
              backgroundColor: '#4caf50',
              borderColor: '#2e7d32',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: (value) => this.formatCurrency(value, false)
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: (context) => {
                    return `${context.dataset.label}: ${this.formatCurrency(context.raw)}`;
                  }
                }
              }
            }
          }
        });
      }

      // FTE vs Cost Trend
      const trendCtx = this.$refs.fteCostTrendChart?.getContext('2d');
      if (trendCtx) {
        // Mock data for trend charts - would come from backend in real implementation
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const fteData = [100, 105, 102, 98, 100, this.getMetricValue('headcount')];
        const costData = [10000000, 10500000, 10200000, 9800000, 10000000, this.getMetricValue('total_fully_loaded_cost')];

        this.charts.trend = new Chart(trendCtx, {
          type: 'line',
          data: {
            labels: months,
            datasets: [
              {
                label: 'FTE',
                data: fteData,
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25, 118, 210, 0.1)',
                yAxisID: 'y',
                tension: 0.1
              },
              {
                label: 'Fully Loaded Cost',
                data: costData,
                borderColor: '#e64a19',
                backgroundColor: 'rgba(230, 74, 25, 0.1)',
                yAxisID: 'y1',
                tension: 0.1
              }
            ]
          },
          options: {
            responsive: true,
            interaction: {
              mode: 'index',
              intersect: false,
            },
            scales: {
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                  display: true,
                  text: 'FTE'
                }
              },
              y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: {
                  drawOnChartArea: false,
                },
                title: {
                  display: true,
                  text: 'Cost'
                },
                ticks: {
                  callback: (value) => this.formatCurrency(value, false)
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: (context) => {
                    if (context.dataset.yAxisID === 'y1') {
                      return `${context.dataset.label}: ${this.formatCurrency(context.raw)}`;
                    }
                    return `${context.dataset.label}: ${context.raw}`;
                  }
                }
              }
            }
          }
        });
      }
    },

    initPositionCharts() {
      // Position Tracking Chart
      const trackingCtx = this.$refs.positionTrackingChart?.getContext('2d');
      if (trackingCtx) {
        const statusData = {
          unchanged: this.getStatusCount('unchanged'),
          new: this.getStatusCount('new'),
          changed: this.getStatusCount('changed'),
          removed: this.getStatusCount('removed')
        };

        this.charts.positionTracking = new Chart(trackingCtx, {
          type: 'pie',
          data: {
            labels: ['Unchanged', 'New', 'Changed', 'Removed'],
            datasets: [{
              data: [statusData.unchanged, statusData.new, statusData.changed, statusData.removed],
              backgroundColor: [
                '#9e9e9e',
                '#4caf50',
                '#ff9800',
                '#f44336'
              ],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'right'
              },
              tooltip: {
                callbacks: {
                  label: (context) => {
                    const label = context.label || '';
                    const value = context.raw || 0;
                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${label}: ${value} (${percentage}%)`;
                  }
                }
              }
            }
          }
        });
      }
    },

    initSpansCharts() {
      // Spans & Layers Chart
      const spansCtx = this.$refs.spansLayersChart?.getContext('2d');
      if (spansCtx) {
        const data = this.layerData.map((layer, index) => ({
          layer: index + 1,
          roles: layer.roles,
          avgSpan: layer.avgSpan
        }));

        this.charts.spansLayers = new Chart(spansCtx, {
          type: 'bar',
          data: {
            labels: data.map(d => `Layer ${d.layer}`),
            datasets: [{
              label: 'Roles',
              data: data.map(d => d.roles),
              backgroundColor: '#1976d2',
              borderColor: '#0d47a1',
              borderWidth: 1,
              yAxisID: 'y'
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Number of Roles'
                }
              }
            }
          }
        });
      }
    },

    initCostCharts() {
      // Cost by Department Chart
      const deptCtx = this.$refs.costByDepartmentChart?.getContext('2d');
      if (deptCtx) {
        const deptData = this.getDepartmentCostData();

        this.charts.costByDept = new Chart(deptCtx, {
          type: 'bar',
          data: {
            labels: deptData.labels,
            datasets: [{
              label: 'Total Cost',
              data: deptData.values,
              backgroundColor: '#4caf50',
              borderColor: '#2e7d32',
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  callback: (value) => this.formatCurrency(value, false)
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: (context) => {
                    return `${context.dataset.label}: ${this.formatCurrency(context.raw)}`;
                  }
                }
              }
            }
          }
        });
      }

      // Cost by Function Chart
      const funcCtx = this.$refs.costByFunctionChart?.getContext('2d');
      if (funcCtx) {
        const funcData = this.getFunctionCostData();

        this.charts.costByFunc = new Chart(funcCtx, {
          type: 'pie',
          data: {
            labels: funcData.labels,
            datasets: [{
              data: funcData.values,
              backgroundColor: [
                '#1976d2',
                '#e53935',
                '#43a047',
                '#fb8c00',
                '#8e24aa',
                '#00acc1'
              ],
              borderWidth: 1
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'right'
              },
              tooltip: {
                callbacks: {
                  label: (context) => {
                    const label = context.label || '';
                    const value = context.raw || 0;
                    const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                    const percentage = ((value / total) * 100).toFixed(1);
                    return `${label}: ${this.formatCurrency(value)} (${percentage}%)`;
                  }
                }
              }
            }
          }
        });
      }

      // Cost by Grade Chart
      const gradeCtx = this.$refs.costByGradeChart?.getContext('2d');
      if (gradeCtx) {
        const gradeData = this.getGradeCostData();

        this.charts.costByGrade = new Chart(gradeCtx, {
          type: 'horizontalBar',
          data: {
            labels: gradeData.labels,
            datasets: [{
              label: 'Cost by Grade',
              data: gradeData.values,
              backgroundColor: '#673ab7',
              borderColor: '#4527a0',
              borderWidth: 1
            }]
          },
          options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
              x: {
                beginAtZero: true,
                ticks: {
                  callback: (value) => this.formatCurrency(value, false)
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: (context) => {
                    return `${context.dataset.label}: ${this.formatCurrency(context.raw)}`;
                  }
                }
              }
            }
          }
        });
      }
    },

    getStatusCount(status) {
      return this.positions.filter(p => p.pivot && p.pivot.status === status).length;
    },

    getDepartmentName(departmentId) {
      if (!departmentId) return '-';
      const department = this.departments.find(d => d.id === departmentId);
      return department ? department.name : '-';
    },

    getMetricValue(code) {
      const metric = this.metrics.find(m => m.code === code);
      return metric && metric.pivot ? parseFloat(metric.pivot.value) : 0;
    },

    getDepartmentCostData() {
      const departments = {};

      this.positions.forEach(position => {
        if (position.fully_loaded_cost && position.department_id) {
          if (!departments[position.department_id]) {
            departments[position.department_id] = 0;
          }
          departments[position.department_id] += parseFloat(position.fully_loaded_cost);
        }
      });

      const labels = [];
      const values = [];

      Object.entries(departments).forEach(([deptId, cost]) => {
        const department = this.departments.find(d => d.id === parseInt(deptId));
        if (department) {
          labels.push(department.name);
          values.push(cost);
        }
      });

      return { labels, values };
    },

    getFunctionCostData() {
      const functions = {};

      this.positions.forEach(position => {
        if (position.fully_loaded_cost) {
          const func = position.function || 'No Function';
          if (!functions[func]) {
            functions[func] = 0;
          }
          functions[func] += parseFloat(position.fully_loaded_cost);
        }
      });

      const labels = [];
      const values = [];

      Object.entries(functions).forEach(([func, cost]) => {
        labels.push(func);
        values.push(cost);
      });

      return { labels, values };
    },

    getGradeCostData() {
      const grades = {};

      this.positions.forEach(position => {
        if (position.fully_loaded_cost && position.grade) {
          if (!grades[position.grade]) {
            grades[position.grade] = 0;
          }
          grades[position.grade] += parseFloat(position.fully_loaded_cost);
        }
      });

      const labels = [];
      const values = [];

      Object.entries(grades).forEach(([grade, cost]) => {
        labels.push(grade);
        values.push(cost);
      });

      return { labels, values };
    },

    getGoalDifference(metricCode) {
      const metric = this.metrics.find(m => m.code === metricCode);
      if (!metric || !metric.pivot || !metric.pivot.goal) return '';

      const value = parseFloat(metric.pivot.value);
      const goal = parseFloat(metric.pivot.goal);
      const diff = value - goal;
      const percentage = (diff / goal * 100).toFixed(1);

      return `${diff > 0 ? '+' : ''}${this.formatCurrency(diff)} (${percentage}%)`;
    },

    getGoalClass(metricCode) {
      const metric = this.metrics.find(m => m.code === metricCode);
      if (!metric || !metric.pivot || !metric.pivot.goal) return '';

      const value = parseFloat(metric.pivot.value);
      const goal = parseFloat(metric.pivot.goal);

      // Assuming lower costs are better
      if (metricCode.includes('cost')) {
        return value <= goal ? 'goal-good' : 'goal-bad';
      }

      // For other metrics, higher values are better
      return value >= goal ? 'goal-good' : 'goal-bad';
    },

    formatMetricValue(metric) {
      if (metric.format === 'currency' || metric.code.includes('cost')) {
        return this.formatCurrency(metric.pivot.value);
      } else if (metric.format === 'percentage' || metric.code.includes('percentage')) {
        return `${metric.pivot.value}%`;
      } else if (metric.format === 'number' || !isNaN(metric.pivot.value)) {
        return new Intl.NumberFormat('en-US').format(metric.pivot.value);
      }

      return metric.pivot.value;
    },

    formatMetricGoal(metric) {
      // Create a copy with the goal as the value
      const metricWithGoalAsValue = {
        ...metric,
        pivot: {
          ...metric.pivot,
          value: metric.pivot.goal
        }
      };

      return this.formatMetricValue(metricWithGoalAsValue);
    },

    formatCurrency(value, includeSymbol = true) {
      if (!value) return includeSymbol ? '$0' : '0';

      const options = {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
      };

      if (includeSymbol) {
        options.style = 'currency';
        options.currency = 'USD';
      }

      return new Intl.NumberFormat('en-US', options).format(value);
    },

    formatTrendValue(trend) {
      if (trend.percentage) {
        return `${trend.percentage > 0 ? '+' : ''}${trend.percentage.toFixed(1)}%`;
      }
      return trend.value > 0 ? `+${trend.value}` : `${trend.value}`;
    },

    getTrendClass(trend) {
      // For cost metrics, lower is better
      if (trend.metricCode && trend.metricCode.includes('cost')) {
        return trend.value < 0 ? 'trend-good' : trend.value > 0 ? 'trend-bad' : '';
      }

      // For other metrics, higher is better
      return trend.value > 0 ? 'trend-good' : trend.value < 0 ? 'trend-bad' : '';
    },

    getTrendIcon(trend) {
      if (trend.value > 0) return 'fas fa-arrow-up';
      if (trend.value < 0) return 'fas fa-arrow-down';
      return 'fas fa-equals';
    }
  }
};
</script>

<style scoped>
.as-is-analysis {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.metrics-tabs {
  display: flex;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.tab {
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.tab:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.tab.active {
  border-bottom: 2px solid #4caf50;
  color: #4caf50;
}

.metrics-content {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}

/* Dashboard View */
.key-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.metric-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  padding: 1.5rem;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.metric-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #555;
}

.metric-trend {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.trend-good {
  color: #4caf50;
}

.trend-bad {
  color: #f44336;
}

.metric-value {
  font-size: 2rem;
  font-weight: bold;
  color: #333;
  margin: 1rem 0 0.5rem;
}

.metric-goal {
  font-size: 0.875rem;
  color: #666;
}

.chart-container {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.chart-container h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.1rem;
  color: #555;
}

/* Position View */
.filter-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 500;
}

.filter-group select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  background-color: #fff;
}

.position-tracking {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  margin-bottom: 2rem;
}

.tracking-summary {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.summary-item {
  background-color: #f5f5f5;
  border-radius: 0.5rem;
  padding: 1rem;
  text-align: center;
}

.summary-item.new {
  background-color: rgba(76, 175, 80, 0.1);
  border: 1px solid rgba(76, 175, 80, 0.3);
}

.summary-item.changed {
  background-color: rgba(255, 152, 0, 0.1);
  border: 1px solid rgba(255, 152, 0, 0.3);
}

.summary-item.removed {
  background-color: rgba(244, 67, 54, 0.1);
  border: 1px solid rgba(244, 67, 54, 0.3);
}

.summary-title {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 1.5rem;
  font-weight: bold;
}

.positions-table {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  overflow: hidden;
}

.positions-table table {
  width: 100%;
  border-collapse: collapse;
}

.positions-table th {
  text-align: left;
  padding: 1rem;
  background-color: #f5f5f5;
  font-weight: 600;
  border-bottom: 1px solid #ddd;
}

.positions-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #eee;
}

.positions-table tr:last-child td {
  border-bottom: none;
}

.positions-table tr.new {
  background-color: rgba(76, 175, 80, 0.05);
}

.positions-table tr.changed {
  background-color: rgba(255, 152, 0, 0.05);
}

.positions-table tr.removed {
  background-color: rgba(244, 67, 54, 0.05);
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  text-transform: capitalize;
  font-weight: 500;
}

.status-badge.unchanged {
  background-color: #e0e0e0;
  color: #616161;
}

.status-badge.new {
  background-color: #c8e6c9;
  color: #2e7d32;
}

.status-badge.changed {
  background-color: #ffe0b2;
  color: #e65100;
}

.status-badge.removed {
  background-color: #ffcdd2;
  color: #c62828;
}

/* Spans View */
.spans-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  padding: 1.5rem;
}

.summary-card h3 {
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.1rem;
  color: #555;
}

.summary-content {
  padding: 0.5rem 0;
}

.summary-main {
  font-size: 2rem;
  font-weight: bold;
  color: #333;
  margin-bottom: 0.5rem;
}

.summary-details {
  font-size: 0.875rem;
  color: #666;
}

.spans-charts {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

.spans-table {
  width: 100%;
  border-collapse: collapse;
}

.spans-table th {
  text-align: left;
  padding: 0.5rem;
  font-weight: 600;
  border-bottom: 1px solid #ddd;
}

.spans-table td {
  padding: 0.5rem;
  border-bottom: 1px solid #eee;
}

/* Cost View */
.cost-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.summary-value {
  font-size: 2rem;
  font-weight: bold;
  color: #333;
  margin: 1rem 0 0.5rem;
}

.summary-comparison {
  font-size: 0.875rem;
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.goal-good {
  color: #4caf50;
}

.goal-bad {
  color: #f44336;
}

@media (max-width: 768px) {
  .position-tracking,
  .spans-charts {
    grid-template-columns: 1fr;
  }

  .tracking-summary {
    grid-template-columns: 1fr 1fr;
  }
}
</style>