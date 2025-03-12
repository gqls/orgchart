// resources/js/components/ToBeModeling.vue
<template>
  <div class="to-be-modeling">
    <div class="scenario-controls">
      <div class="scenario-selector">
        <label for="scenario-select">Current Scenario:</label>
        <select id="scenario-select" v-model="selectedScenarioId" @change="loadScenario">
          <option v-for="scenario in scenarios" :key="scenario.id" :value="scenario.id">
            {{ scenario.name }}
          </option>
        </select>

        <button @click="createNewScenario" class="btn-create">
          <i class="fas fa-plus"></i> New Scenario
        </button>
      </div>

      <div class="scenario-actions">
        <button @click="compareScenarios" class="btn-action">
          <i class="fas fa-columns"></i> Compare
        </button>
        <button @click="makeCurrentScenario" class="btn-action" :disabled="!canSetAsCurrent">
          <i class="fas fa-check-circle"></i> Set as Current
        </button>
      </div>
    </div>

    <div class="scenario-metrics">
      <div class="metrics-header">
        <h3>Scenario Metrics</h3>
        <div v-if="isComparing" class="comparison-labels">
          <div class="comparison-label current">{{ currentScenario ? currentScenario.name : 'Current' }}</div>
          <div class="comparison-label comparison">{{ comparisonScenario ? comparisonScenario.name : 'Comparison' }}</div>
        </div>
      </div>

      <div class="metrics-grid">
        <div
            v-for="metric in keyMetrics"
            :key="metric.code"
            class="metric-card"
            :class="{ 'metric-comparing': isComparing }"
        >
          <div class="metric-header">
            <h4>{{ metric.name }}</h4>
          </div>

          <div class="metric-content">
            <div class="metric-current">
              <div class="metric-value">{{ formatMetricValue(metric) }}</div>
              <div class="metric-goal" v-if="metric.pivot && metric.pivot.goal">
                Goal: {{ formatMetricGoal(metric) }}
              </div>
            </div>

            <div v-if="isComparing" class="metric-comparison">
              <div class="metric-value">
                {{ formatComparisonValue(metric) }}
              </div>
              <div class="metric-difference" :class="getDifferenceClass(metric)">
                {{ calculateDifference(metric) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modeling-tabs">
      <div
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="['tab', { active: activeTab === tab.id }]"
      >
        {{ tab.name }}
      </div>
    </div>

    <div class="modeling-content">
      <!-- Department Modeling -->
      <div v-if="activeTab === 'department'" class="department-modeling">
        <div class="department-header">
          <h3>Department Structure Modeling</h3>
          <div class="department-actions">
            <button @click="addDepartment" class="btn-add" v-if="!isComparing">
              <i class="fas fa-plus"></i> Add Department
            </button>
          </div>
        </div>

        <div class="department-grid">
          <div
              v-for="department in departments"
              :key="department.id"
              class="department-card"
              :class="{ 'is-editing': editingDepartment && editingDepartment.id === department.id }"
          >
            <div class="card-header" :style="{ backgroundColor: department.color || '#1976d2' }">
              <h4>{{ department.name }}</h4>
              <div class="card-actions" v-if="!isComparing">
                <i class="fas fa-edit" @click="editDepartment(department)"></i>
                <i class="fas fa-trash" @click="deleteDepartment(department)"></i>
              </div>
            </div>

            <div class="card-content">
              <div class="department-stats">
                <div class="stat-item">
                  <div class="stat-label">Positions:</div>
                  <div class="stat-value">{{ getDepartmentPositionCount(department) }}</div>
                </div>
                <div class="stat-item">
                  <div class="stat-label">Total Cost:</div>
                  <div class="stat-value">{{ formatCurrency(getDepartmentCost(department)) }}</div>
                </div>
              </div>

              <div v-if="isComparing" class="comparison-stats">
                <div class="stat-item">
                  <div class="stat-label">Comparison Positions:</div>
                  <div class="stat-value">{{ getDepartmentPositionCount(department, true) }}</div>
                  <div class="stat-diff" :class="getPositionDiffClass(department)">
                    {{ getPositionDiff(department) }}
                  </div>
                </div>
                <div class="stat-item">
                  <div class="stat-label">Comparison Cost:</div>
                  <div class="stat-value">{{ formatCurrency(getDepartmentCost(department, true)) }}</div>
                  <div class="stat-diff" :class="getCostDiffClass(department)">
                    {{ getCostDiff(department) }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="editingDepartment && editingDepartment.id === department.id" class="department-edit-form">
              <div class="form-group">
                <label for="name">Department Name:</label>
                <input id="name" v-model="editingDepartment.name" type="text">
              </div>

              <div class="form-group">
                <label for="color">Color:</label>
                <input id="color" v-model="editingDepartment.color" type="color">
              </div>

              <div class="form-actions">
                <button @click="saveDepartment" class="btn-save">Save</button>
                <button @click="cancelEdit" class="btn-cancel">Cancel</button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cost Modeling -->
      <div v-if="activeTab === 'cost'" class="cost-modeling">
        <div class="cost-controls">
          <div class="cost-summary">
            <div class="summary-card">
              <h3>Total Fully Loaded Cost</h3>
              <div class="summary-value">{{ formatCurrency(getMetricValue('total_fully_loaded_cost')) }}</div>
              <div class="summary-comparison" v-if="metrics.find(m => m.code === 'total_fully_loaded_cost')?.pivot?.goal">
                vs Goal: {{ formatCurrency(metrics.find(m => m.code === 'total_fully_loaded_cost').pivot.goal) }}
                <span :class="getGoalClass('total_fully_loaded_cost')">
                  {{ getGoalDifference('total_fully_loaded_cost') }}
                </span>
              </div>
            </div>

            <div class="summary-card" v-if="isComparing">
              <h3>Cost Comparison</h3>
              <div class="comparison-summary">
                <div class="comparison-item">
                  <div class="comparison-label">{{ currentScenario ? currentScenario.name : 'Current' }}:</div>
                  <div class="comparison-value">{{ formatCurrency(getMetricValue('total_fully_loaded_cost')) }}</div>
                </div>
                <div class="comparison-item">
                  <div class="comparison-label">{{ comparisonScenario ? comparisonScenario.name : 'Comparison' }}:</div>
                  <div class="comparison-value">{{ formatCurrency(getComparisonMetricValue('total_fully_loaded_cost')) }}</div>
                </div>
                <div class="comparison-diff" :class="getTotalCostDiffClass()">
                  {{ getTotalCostDiff() }}
                </div>
              </div>
            </div>
          </div>

          <div class="cost-actions">
            <button @click="applyAcrossTheBoard" class="btn-action" v-if="!isComparing">
              <i class="fas fa-percentage"></i> Apply Across-the-Board Change
            </button>
          </div>
        </div>

        <div class="cost-charts">
          <div class="chart-container">
            <h3>Department Cost Comparison</h3>
            <canvas ref="departmentCostChart"></canvas>
          </div>

          <div class="chart-container">
            <h3>Function Cost Comparison</h3>
            <canvas ref="functionCostChart"></canvas>
          </div>
        </div>

        <!-- Cost modeling form -->
        <div v-if="showCostModelingForm" class="cost-modeling-form">
          <div class="form-header">
            <h3>Across-the-Board Cost Change</h3>
            <button @click="cancelCostModeling" class="btn-close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="form-body">
            <div class="form-group">
              <label for="change-percentage">Percentage Change:</label>
              <div class="input-group">
                <input id="change-percentage" v-model="costChangePercentage" type="number" step="0.1">
                <span class="input-addon">%</span>
              </div>
            </div>

            <div class="form-group">
              <label for="apply-to">Apply To:</label>
              <select id="apply-to" v-model="costChangeTarget">
                <option value="all">All Positions</option>
                <option value="department">Specific Department</option>
                <option value="function">Specific Function</option>
                <option value="grade">Specific Grade</option>
              </select>
            </div>

            <div class="form-group" v-if="costChangeTarget === 'department'">
              <label for="department-select">Department:</label>
              <select id="department-select" v-model="costChangeDepartment">
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
            </div>

            <div class="form-group" v-if="costChangeTarget === 'function'">
              <label for="function-select">Function:</label>
              <select id="function-select" v-model="costChangeFunction">
                <option v-for="func in functions" :key="func" :value="func">
                  {{ func }}
                </option>
              </select>
            </div>

            <div class="form-group" v-if="costChangeTarget === 'grade'">
              <label for="grade-select">Grade:</label>
              <select id="grade-select" v-model="costChangeGrade">
                <option v-for="grade in grades" :key="grade" :value="grade">
                  {{ grade }}
                </option>
              </select>
            </div>

            <div class="cost-preview">
              <div class="preview-item">
                <div class="preview-label">Current Total Cost:</div>
                <div class="preview-value">{{ formatCurrency(getMetricValue('total_fully_loaded_cost')) }}</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">New Total Cost:</div>
                <div class="preview-value">{{ formatCurrency(calculateNewTotalCost()) }}</div>
              </div>
              <div class="preview-diff" :class="{ 'preview-increase': costChangePercentage > 0, 'preview-decrease': costChangePercentage < 0 }">
                {{ costChangePercentage > 0 ? '+' : '' }}{{ costChangePercentage }}% ({{ formatCurrency(calculateNewTotalCost() - getMetricValue('total_fully_loaded_cost')) }})
              </div>
            </div>

            <div class="form-actions">
              <button @click="applyCostChange" class="btn-apply">Apply Changes</button>
              <button @click="cancelCostModeling" class="btn-cancel">Cancel</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Headcount Modeling -->
      <div v-if="activeTab === 'headcount'" class="headcount-modeling">
        <div class="headcount-controls">
          <div class="headcount-summary">
            <div class="summary-card">
              <h3>Total Headcount</h3>
              <div class="summary-value">{{ getMetricValue('headcount') }}</div>
              <div class="summary-comparison" v-if="metrics.find(m => m.code === 'headcount')?.pivot?.goal">
                vs Goal: {{ metrics.find(m => m.code === 'headcount').pivot.goal }}
                <span :class="getGoalClass('headcount')">
                  {{ getHeadcountGoalDiff() }}
                </span>
              </div>
            </div>

            <div class="summary-card" v-if="isComparing">
              <h3>Headcount Comparison</h3>
              <div class="comparison-summary">
                <div class="comparison-item">
                  <div class="comparison-label">{{ currentScenario ? currentScenario.name : 'Current' }}:</div>
                  <div class="comparison-value">{{ getMetricValue('headcount') }}</div>
                </div>
                <div class="comparison-item">
                  <div class="comparison-label">{{ comparisonScenario ? comparisonScenario.name : 'Comparison' }}:</div>
                  <div class="comparison-value">{{ getComparisonMetricValue('headcount') }}</div>
                </div>
                <div class="comparison-diff" :class="getHeadcountDiffClass()">
                  {{ getHeadcountDiff() }}
                </div>
              </div>
            </div>
          </div>

          <div class="headcount-actions">
            <button @click="applyHeadcountChange" class="btn-action" v-if="!isComparing">
              <i class="fas fa-users"></i> Model Headcount Change
            </button>
          </div>
        </div>

        <div class="headcount-charts">
          <div class="chart-container">
            <h3>Department Headcount Comparison</h3>
            <canvas ref="departmentHeadcountChart"></canvas>
          </div>

          <div class="chart-container">
            <h3>Function Headcount Comparison</h3>
            <canvas ref="functionHeadcountChart"></canvas>
          </div>
        </div>

        <!-- Headcount modeling form -->
        <div v-if="showHeadcountModelingForm" class="headcount-modeling-form">
          <div class="form-header">
            <h3>Headcount Change Modeling</h3>
            <button @click="cancelHeadcountModeling" class="btn-close">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="form-body">
            <div class="form-group">
              <label for="headcount-change">Headcount Change:</label>
              <div class="input-group">
                <input id="headcount-change" v-model="headcountChange" type="number" step="1">
                <span class="input-addon">positions</span>
              </div>
            </div>

            <div class="form-group">
              <label for="headcount-change-type">Change Type:</label>
              <select id="headcount-change-type" v-model="headcountChangeType">
                <option value="increase">Increase (Add Positions)</option>
                <option value="reduction">Reduction (Remove Positions)</option>
              </select>
            </div>

            <div class="form-group">
              <label for="headcount-target">Apply To:</label>
              <select id="headcount-target" v-model="headcountChangeTarget">
                <option value="all">All Departments</option>
                <option value="department">Specific Department</option>
                <option value="function">Specific Function</option>
              </select>
            </div>

            <div class="form-group" v-if="headcountChangeTarget === 'department'">
              <label for="headcount-department">Department:</label>
              <select id="headcount-department" v-model="headcountChangeDepartment">
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
            </div>

            <div class="form-group" v-if="headcountChangeTarget === 'function'">
              <label for="headcount-function">Function:</label>
              <select id="headcount-function" v-model="headcountChangeFunction">
                <option v-for="func in functions" :key="func" :value="func">
                  {{ func }}
                </option>
              </select>
            </div>

            <div class="headcount-preview">
              <div class="preview-item">
                <div class="preview-label">Current Headcount:</div>
                <div class="preview-value">{{ getMetricValue('headcount') }}</div>
              </div>
              <div class="preview-item">
                <div class="preview-label">New Headcount:</div>
                <div class="preview-value">{{ calculateNewHeadcount() }}</div>
              </div>
              <div class="preview-diff" :class="{ 'preview-increase': headcountChange > 0 && headcountChangeType === 'increase', 'preview-decrease': headcountChange > 0 && headcountChangeType === 'reduction' }">
                {{ headcountChangeType === 'increase' ? '+' : '-' }}{{ headcountChange }} positions
              </div>
            </div>

            <div class="form-group" v-if="headcountChangeType === 'increase'">
              <label for="avg-cost">Average Cost per New Position:</label>
              <div class="input-group">
                <span class="input-prefix">$</span>
                <input id="avg-cost" v-model="newPositionAvgCost" type="number" step="1000">
              </div>
            </div>

            <div class="cost-impact">
              <div class="impact-label">Estimated Cost Impact:</div>
              <div class="impact-value" :class="{ 'impact-increase': calculateCostImpact() > 0, 'impact-decrease': calculateCostImpact() < 0 }">
                {{ formatCurrency(calculateCostImpact()) }}
              </div>
            </div>

            <div class="form-actions">
              <button @click="applyHeadcountChanges" class="btn-apply">Apply Changes</button>
              <button @click="cancelHeadcountModeling" class="btn-cancel">Cancel</button>
            </div>
          </div>
        </div>
      </div>
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

    <!-- Scenario Comparison Modal -->
    <div v-if="showComparisonModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Compare Scenarios</h3>
          <button @click="cancelComparison" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="comparison-scenario">Select Scenario to Compare:</label>
            <select id="comparison-scenario" v-model="comparisonScenarioId">
              <option v-for="scenario in otherScenarios" :key="scenario.id" :value="scenario.id">
                {{ scenario.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="startComparison" class="btn-primary" :disabled="!comparisonScenarioId">Compare</button>
          <button @click="cancelComparison" class="btn-secondary">Cancel</button>
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
      selectedScenarioId: null,
      currentScenario: null,
      scenarios: [],
      departments: [],
      positions: [],
      metrics: [],
      functions: [],
      grades: [],

      activeTab: 'department',
      tabs: [
        { id: 'department', name: 'Department Structure' },
        { id: 'cost', name: 'Cost Modeling' },
        { id: 'headcount', name: 'Headcount Planning' }
      ],

      editingDepartment: null,

      showNewScenarioModal: false,
      newScenario: {
        name: '',
        description: '',
        baseScenarioId: null
      },

      showComparisonModal: false,
      isComparing: false,
      comparisonScenarioId: null,
      comparisonScenario: null,
      comparisonPositions: [],
      comparisonMetrics: [],

      showCostModelingForm: false,
      costChangePercentage: 0,
      costChangeTarget: 'all',
      costChangeDepartment: null,
      costChangeFunction: null,
      costChangeGrade: null,

      showHeadcountModelingForm: false,
      headcountChange: 0,
      headcountChangeType: 'increase',
      headcountChangeTarget: 'all',
      headcountChangeDepartment: null,
      headcountChangeFunction: null,
      newPositionAvgCost: 0,

      charts: {}
    };
  },

  computed: {
    keyMetrics() {
      return this.metrics.filter(metric =>
          ['headcount', 'total_fully_loaded_cost', 'avg_span', 'total_managers'].includes(metric.code)
      );
    },

    otherScenarios() {
      return this.scenarios.filter(s => s.id !== this.selectedScenarioId);
    },

    canSetAsCurrent() {
      return this.currentScenario && !this.currentScenario.is_current;
    }
  },

  watch: {
    scenario: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.selectedScenarioId = newVal.id;
          this.currentScenario = newVal;
          this.loadScenario();
        }
      }
    },

    activeTab(newTab) {
      this.$nextTick(() => {
        this.initCharts();
      });
    },

    costChangeTarget() {
      // Reset specific selections when target type changes
      this.costChangeDepartment = null;
      this.costChangeFunction = null;
      this.costChangeGrade = null;
    },

    headcountChangeTarget() {
      // Reset specific selections when target type changes
      this.headcountChangeDepartment = null;
      this.headcountChangeFunction = null;
    }
  },

  methods: {
    async loadScenario() {
      if (!this.selectedScenarioId) return;

      try {
        // Fetch scenario details
        const scenarioResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}`
        );
        this.currentScenario = scenarioResponse.data;

        // Fetch departments
        const departmentsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/departments`
        );
        this.departments = departmentsResponse.data;

        // Fetch positions
        const positionsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions`
        );
        this.positions = positionsResponse.data;

        // Fetch metrics
        const metricsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/metrics`
        );
        this.metrics = metricsResponse.data;

        // Extract unique functions and grades
        this.functions = [...new Set(this.positions.filter(p => p.function).map(p => p.function))];
        this.grades = [...new Set(this.positions.filter(p => p.grade).map(p => p.grade))];

        // Calculate average cost for new positions
        this.newPositionAvgCost = this.positions.length > 0
            ? this.positions.reduce((sum, p) => sum + (parseFloat(p.fully_loaded_cost) || 0), 0) / this.positions.length
            : 50000; // Default value

        // Initialize charts after data is loaded
        this.$nextTick(() => {
          this.initCharts();
        });

      } catch (error) {
        console.error('Error loading scenario data:', error);
      }
    },

    async fetchScenarios() {
      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/scenarios`);
        this.scenarios = response.data;
      } catch (error) {
        console.error('Error fetching scenarios:', error);
      }
    },

    async loadComparisonScenario() {
      if (!this.comparisonScenarioId) return;

      try {
        // Fetch comparison scenario details
        const scenarioResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.comparisonScenarioId}`
        );
        this.comparisonScenario = scenarioResponse.data;

        // Fetch comparison positions
        const positionsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.comparisonScenarioId}/positions`
        );
        this.comparisonPositions = positionsResponse.data;

        // Fetch comparison metrics
        const metricsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.comparisonScenarioId}/metrics`
        );
        this.comparisonMetrics = metricsResponse.data;

        // Initialize comparison charts
        this.$nextTick(() => {
          this.initCharts();
        });

      } catch (error) {
        console.error('Error loading comparison scenario data:', error);
      }
    },

    initCharts() {
      // Clear existing charts
      Object.values(this.charts).forEach(chart => {
        if (chart) chart.destroy();
      });

      // Initialize only charts for the active tab
      switch (this.activeTab) {
        case 'cost':
          this.initCostCharts();
          break;
        case 'headcount':
          this.initHeadcountCharts();
          break;
      }
    },

    initCostCharts() {
      // Department Cost Chart
      const deptCtx = this.$refs.departmentCostChart?.getContext('2d');
      if (deptCtx) {
        const deptData = this.getDepartmentCostData();

        this.charts.departmentCost = new Chart(deptCtx, {
          type: 'bar',
          data: {
            labels: deptData.labels,
            datasets: [
              {
                label: this.currentScenario ? this.currentScenario.name : 'Current',
                data: deptData.currentValues,
                backgroundColor: '#4caf50',
                borderColor: '#2e7d32',
                borderWidth: 1
              },
              ...this.isComparing ? [{
                label: this.comparisonScenario ? this.comparisonScenario.name : 'Comparison',
                data: deptData.comparisonValues,
                backgroundColor: '#2196f3',
                borderColor: '#0d47a1',
                borderWidth: 1
              }] : []
            ]
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

      // Function Cost Chart
      const funcCtx = this.$refs.functionCostChart?.getContext('2d');
      if (funcCtx) {
        const funcData = this.getFunctionCostData();

        this.charts.
















            functionCost = new Chart(funcCtx, {
          type: 'bar',
          data: {
            labels: funcData.labels,
            datasets: [
              {
                label: this.currentScenario ? this.currentScenario.name : 'Current',
                data: funcData.currentValues,
                backgroundColor: '#ff9800',
                borderColor: '#e65100',
                borderWidth: 1
              },
              ...this.isComparing ? [{
                label: this.comparisonScenario ? this.comparisonScenario.name : 'Comparison',
                data: funcData.comparisonValues,
                backgroundColor: '#9c27b0',
                borderColor: '#4a148c',
                borderWidth: 1
              }] : []
            ]
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
    },

    initHeadcountCharts() {
      // Department Headcount Chart
      const deptCtx = this.$refs.departmentHeadcountChart?.getContext('2d');
      if (deptCtx) {
        const deptData = this.getDepartmentHeadcountData();

        this.charts.departmentHeadcount = new Chart(deptCtx, {
          type: 'bar',
          data: {
            labels: deptData.labels,
            datasets: [
              {
                label: this.currentScenario ? this.currentScenario.name : 'Current',
                data: deptData.currentValues,
                backgroundColor: '#4caf50',
                borderColor: '#2e7d32',
                borderWidth: 1
              },
              ...this.isComparing ? [{
                label: this.comparisonScenario ? this.comparisonScenario.name : 'Comparison',
                data: deptData.comparisonValues,
                backgroundColor: '#2196f3',
                borderColor: '#0d47a1',
                borderWidth: 1
              }] : []
            ]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: (context) => {
                    return `${context.dataset.label}: ${context.raw}`;
                  }
                }
              }
            }
          }
        });
      }

      // Function Headcount Chart
      const funcCtx = this.$refs.functionHeadcountChart?.getContext('2d');
      if (funcCtx) {
        const funcData = this.getFunctionHeadcountData();

        this.charts.functionHeadcount = new Chart(funcCtx, {
          type: 'bar',
          data: {
            labels: funcData.labels,
            datasets: [
              {
                label: this.currentScenario ? this.currentScenario.name : 'Current',
                data: funcData.currentValues,
                backgroundColor: '#ff9800',
                borderColor: '#e65100',
                borderWidth: 1
              },
              ...this.isComparing ? [{
                label: this.comparisonScenario ? this.comparisonScenario.name : 'Comparison',
                data: funcData.comparisonValues,
                backgroundColor: '#9c27b0',
                borderColor: '#4a148c',
                borderWidth: 1
              }] : []
            ]
          },
          options: {
            responsive: true,
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1
                }
              }
            },
            plugins: {
              tooltip: {
                callbacks: {
                  label: (context) => {
                    return `${context.dataset.label}: ${context.raw}`;
                  }
                }
              }
            }
          }
        });
      }
    },

    getDepartmentCostData() {
      const labels = this.departments.map(d => d.name);
      const currentValues = this.departments.map(d => this.getDepartmentCost(d));
      const comparisonValues = this.isComparing ? this.departments.map(d => this.getDepartmentCost(d, true)) : [];

      return { labels, currentValues, comparisonValues };
    },

    getFunctionCostData() {
      const labels = this.functions;
      const currentValues = this.functions.map(f => this.getFunctionCost(f));
      const comparisonValues = this.isComparing ? this.functions.map(f => this.getFunctionCost(f, true)) : [];

      return { labels, currentValues, comparisonValues };
    },

    getDepartmentHeadcountData() {
      const labels = this.departments.map(d => d.name);
      const currentValues = this.departments.map(d => this.getDepartmentPositionCount(d));
      const comparisonValues = this.isComparing ? this.departments.map(d => this.getDepartmentPositionCount(d, true)) : [];

      return { labels, currentValues, comparisonValues };
    },

    getFunctionHeadcountData() {
      const labels = this.functions;
      const currentValues = this.functions.map(f => this.getFunctionHeadcount(f));
      const comparisonValues = this.isComparing ? this.functions.map(f => this.getFunctionHeadcount(f, true)) : [];

      return { labels, currentValues, comparisonValues };
    },

    getFunctionCost(func, isComparison = false) {
      const positions = isComparison ? this.comparisonPositions : this.positions;
      return positions
          .filter(p => p.function === func)
          .reduce((sum, p) => sum + (parseFloat(p.fully_loaded_cost) || 0), 0);
    },

    getFunctionHeadcount(func, isComparison = false) {
      const positions = isComparison ? this.comparisonPositions : this.positions;
      return positions.filter(p => p.function === func).length;
    },

    getMetricValue(code) {
      const metric = this.metrics.find(m => m.code === code);
      return metric ? metric.value : 0;
    },

    getComparisonMetricValue(code) {
      const metric = this.comparisonMetrics.find(m => m.code === code);
      return metric ? metric.value : 0;
    },

    formatMetricValue(metric) {
      if (!metric || metric.value === null || metric.value === undefined) return 'N/A';

      if (metric.code === 'avg_span') {
        return metric.value.toFixed(1);
      } else if (metric.code === 'total_fully_loaded_cost') {
        return this.formatCurrency(metric.value);
      } else {
        return metric.value;
      }
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

    formatComparisonValue(metric) {
      const comparisonMetric = this.comparisonMetrics.find(m => m.code === metric.code);
      if (!comparisonMetric || comparisonMetric.value === null || comparisonMetric.value === undefined) return 'N/A';

      if (metric.code === 'avg_span') {
        return comparisonMetric.value.toFixed(1);
      } else if (metric.code === 'total_fully_loaded_cost') {
        return this.formatCurrency(comparisonMetric.value);
      } else {
        return comparisonMetric.value;
      }
    },

    calculateDifference(metric) {
      const currentValue = parseFloat(metric.value);
      const comparisonValue = parseFloat(this.comparisonMetrics.find(m => m.code === metric.code)?.value);

      if (isNaN(currentValue) || isNaN(comparisonValue)) return 'N/A';

      if (metric.code === 'total_fully_loaded_cost') {
        return this.formatCurrency(comparisonValue - currentValue);
      } else if (metric.code === 'avg_span') {
        return (comparisonValue - currentValue).toFixed(1);
      } else {
        return (comparisonValue - currentValue);
      }
    },

    getDifferenceClass(metric) {
      const difference = this.calculateDifference(metric);
      if (difference === 'N/A') return '';

      if (metric.code === 'total_fully_loaded_cost') {
        return difference > 0 ? 'increase' : difference < 0 ? 'decrease' : '';
      } else if (metric.code === 'avg_span') {
        return difference > 0 ? 'increase' : difference < 0 ? 'decrease' : '';
      } else {
        return difference > 0 ? 'increase' : difference < 0 ? 'decrease' : '';
      }
    },

    createNewScenario() {
      this.showNewScenarioModal = true;
      this.newScenario = {
        name: '',
        description: '',
        baseScenarioId: this.selectedScenarioId
      };
    },

    async saveNewScenario() {
      try {
        const response = await axios.post(`/api/organizations/${this.organization.id}/scenarios`, this.newScenario);
        this.scenarios.push(response.data);
        this.selectedScenarioId = response.data.id;
        this.currentScenario = response.data;
        this.showNewScenarioModal = false;
        this.loadScenario();
      } catch (error) {
        console.error('Error creating new scenario:', error);
      }
    },

    cancelNewScenario() {
      this.showNewScenarioModal = false;
    },

    compareScenarios() {
      this.showComparisonModal = true;
      this.comparisonScenarioId = null;
    },

    async startComparison() {
      await this.loadComparisonScenario();
      this.isComparing = true;
      this.showComparisonModal = false;
    },

    cancelComparison() {
      this.showComparisonModal = false;
    },

    makeCurrentScenario() {
      if (!this.currentScenario) return;

      axios.put(`/api/organizations/<span class="math-inline">\{this\.organization\.id\}/scenarios/</span>{this.currentScenario.id}/set-current`)
          .then(() => {
            this.currentScenario.is_current = true;
            this.scenarios.forEach(s => {
              if (s.id !== this.currentScenario.id) {
                s.is_current = false;
              }
            });
          })
          .catch(error => {
            console.error('Error setting scenario as current:', error);
          });
    },

    addDepartment() {
      const newDepartment = {
        name: `Department ${this.departments.length + 1}`,
        color: `#${Math.floor(Math.random() * 16777215).toString(16)}`,
        organization_id: this.organization.id
      };
      axios.post(`/api/organizations/${this.organization.id}/departments`, newDepartment)
          .then(response => {
            this.departments.push(response.data);
          })
          .catch(error => {
            console.error('Error adding department:', error);
          });
    },

    editDepartment(department) {
      this.editingDepartment = { ...department };
    },

    saveDepartment() {
      axios.put(`/api/organizations/<span class="math-inline">\{this\.organization\.id\}/departments/</span>{this.editingDepartment.id}`, this.editingDepartment)
          .then(() => {
            const index = this.departments.findIndex(d => d.id === this.editingDepartment.id);
            if (index !== -1) {
              this.departments.splice(index, 1, { ...this.editingDepartment });
            }
            this.editingDepartment = null;
          })
          .catch(error => {
            console.error('Error saving department:', error);
          });
    },

    cancelEdit() {
      this.editingDepartment = null;
    },

    deleteDepartment(department) {
      if (confirm(`Are you sure you want to delete ${department.name}?`)) {
        axios.delete(`/api/organizations/<span class="math-inline">\{this\.organization\.id\}/departments/</span>{department.id}`)
            .then(() => {
              this.departments = this.departments.filter(d => d.id !== department.id);
            })
            .catch(error => {
              console.error('Error deleting department:', error);
            });
      }
    },

    getDepartmentPositionCount(department, isComparison = false) {
      const positions = isComparison ? this.comparisonPositions : this.positions;
      return positions.filter(p => p.department_id === department.id).length;
    },

    getDepartmentCost(department, isComparison = false) {
      const positions = isComparison ? this.comparisonPositions : this.positions;
      return positions
          .filter(p => p.department_id === department.id)
          .reduce((sum, p) => sum + (parseFloat(p.fully_loaded_cost) || 0), 0);
    },

    formatCurrency(value, withSymbol = true) {
      if (value === null || value === undefined) return '$0';
      const formattedValue = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
      }).format(value);
      return withSymbol ? formattedValue : formattedValue.replace('$', '');
    },

    getDepartmentCostDiff(department) {
      const currentCost = this.getDepartmentCost(department);
      const comparisonCost = this.getDepartmentCost(department, true);
      const diff = comparisonCost - currentCost;
      return this.formatCurrency(diff);
    },

    getCostDiffClass(department) {
      const diff = this.getDepartmentCostDiff(department);
      if (diff === '$0') return '';
      return diff.startsWith('-') ? 'decrease' : 'increase';
    },

    getDepartmentPositionDiff(department) {
      const currentCount = this.getDepartmentPositionCount(department);
      const comparisonCount = this.getDepartmentPositionCount(department, true);
      const diff = comparisonCount - currentCount;
      return diff > 0 ? `+${diff}` : diff.toString();
    },

    getPositionDiffClass(department) {
      const diff = this.getDepartmentPositionDiff(department);
      if (diff === '0') return '';
      return diff.startsWith('-') ? 'decrease' : 'increase';
    },

    applyAcrossTheBoard() {
      this.showCostModelingForm = true;
      this.costChangePercentage = 0;
      this.costChangeTarget = 'all';
      this.costChangeDepartment = null;
      this.costChangeFunction = null;
      this.costChangeGrade = null;
    },

    cancelCostModeling() {
      this.showCostModelingForm = false;
    },

    calculateNewTotalCost() {
      let totalCost = this.getMetricValue('total_fully_loaded_cost');
      let positionsToChange = this.positions;

      if (this.costChangeTarget === 'department' && this.costChangeDepartment) {
        positionsToChange = this.positions.filter(p => p.department_id === this.costChangeDepartment);
      } else if (this.costChangeTarget === 'function' && this.costChangeFunction) {
        positionsToChange = this.positions.filter(p => p.function === this.costChangeFunction);
      } else if (this.costChangeTarget === 'grade' && this.costChangeGrade) {
        positionsToChange = this.positions.filter(p => p.grade === this.costChangeGrade);
      }

      positionsToChange.forEach(position => {
        const cost = parseFloat(position.fully_loaded_cost) || 0;
        totalCost += cost * (this.costChangePercentage / 100);
      });

      return totalCost;
    },

    async applyCostChange() {
      let positionsToChange = this.positions;

      if (this.costChangeTarget === 'department' && this.costChangeDepartment) {
        positionsToChange = this.positions.filter(p => p.department_id === this.costChangeDepartment);
      } else if (this.costChangeTarget === 'function' && this.costChangeFunction) {
        positionsToChange = this.positions.filter(p => p.function === this.costChangeFunction);
      } else if (this.costChangeTarget === 'grade' && this.costChangeGrade) {
        positionsToChange = this.positions.filter(p => p.grade === this.costChangeGrade);
      }

      const promises = positionsToChange.map(position => {
        const cost = parseFloat(position.fully_loaded_cost) || 0;
        const newCost = cost + (cost * (this.costChangePercentage / 100));
        return axios
            .put(
                `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions/${position.id}`,
                { fully_loaded_cost: newCost }
            )
            .catch(error => {
              console.error(
                  `Error updating position ${position.id}: ${error.message || error}`
              );
              // Optionally, you could store the failed position IDs for user feedback
              return Promise.reject(error); // Rejects the promise to trigger the main try/catch block if any of the individual promises rejects.
            });
      });

      try {
        await Promise.all(promises);
        await this.loadScenario();
        this.showCostModelingForm = false;
        // Optionally, you could provide a success message to the user here.
      } catch (error) {
        console.error('Error applying cost changes:', error);
        // Optionally, provide user feedback about the overall failure.
      }
    },

    getGoalClass(metricCode) {
      const metric = this.metrics.find(m => m.code === metricCode);
      if (!metric || !metric.pivot || metric.pivot.goal === null || metric.pivot.goal === undefined) return '';

      const goal = parseFloat(metric.pivot.goal);
      const value = parseFloat(metric.value);

      if (isNaN(goal) || isNaN(value)) return '';

      if (metricCode === 'total_fully_loaded_cost') {
        return value > goal ? 'over-goal' : value < goal ? 'under-goal' : '';
      } else if (metricCode === 'headcount') {
        return value > goal ? 'over-goal' : value < goal ? 'under-goal' : '';
      } else {
        return '';
      }
    },

    getGoalDifference(metricCode) {
      const metric = this.metrics.find(m => m.code === metricCode);
      if (!metric || !metric.pivot || metric.pivot.goal === null || metric.pivot.goal === undefined) return '';

      const goal = parseFloat(metric.pivot.goal);
      const value = parseFloat(metric.value);

      if (isNaN(goal) || isNaN(value)) return '';

      const diff = value - goal;
      if (metricCode === 'total_fully_loaded_cost') {
        return this.formatCurrency(diff);
      } else {
        return diff > 0 ? `+${diff}` : diff.toString();
      }
    },

    getTotalCostDiffClass() {
      const currentCost = this.getMetricValue('total_fully_loaded_cost');
      const comparisonCost = this.getComparisonMetricValue('total_fully_loaded_cost');
      const diff = comparisonCost - currentCost;
      if (diff === 0) return '';
      return diff > 0 ? 'increase' : 'decrease';
    },

    getTotalCostDiff() {
      const currentCost = this.getMetricValue('total_fully_loaded_cost');
      const comparisonCost = this.getComparisonMetricValue('total_fully_loaded_cost');
      const diff = comparisonCost - currentCost;
      return this.formatCurrency(diff);
    },

    getHeadcountGoalDiff() {
      return this.getGoalDifference('headcount');
    },

    getHeadcountDiffClass() {
      const currentHeadcount = this.getMetricValue('headcount');
      const comparisonHeadcount = this.getComparisonMetricValue('headcount');
      const diff = comparisonHeadcount - currentHeadcount;
      if (diff === 0) return '';
      return diff > 0 ? 'increase' : 'decrease';
    },

    getHeadcountDiff() {
      const currentHeadcount = this.getMetricValue('headcount');
      const comparisonHeadcount = this.getComparisonMetricValue('headcount');
      const diff = comparisonHeadcount - currentHeadcount;
      return diff > 0 ? `+${diff}` : diff.toString();
    },

    applyHeadcountChange() {
      this.showHeadcountModelingForm = true;
      this.headcountChange = 0;
      this.headcountChangeType = 'increase';
      this.headcountChangeTarget = 'all';
      this.headcountChangeDepartment = null;
      this.headcountChangeFunction = null;
      this.newPositionAvgCost = this.positions.length > 0
          ? this.positions.reduce((sum, p) => sum + (parseFloat(p.fully_loaded_cost) || 0), 0) / this.positions.length
          : 50000;
    },

    cancelHeadcountModeling() {
      this.showHeadcountModelingForm = false;
    },

    calculateNewHeadcount() {
      const currentHeadcount = this.getMetricValue('headcount');
      return this.headcountChangeType === 'increase'
          ? currentHeadcount + this.headcountChange
          : currentHeadcount - this.headcountChange;
    },

    calculateCostImpact() {
      return this.headcountChangeType === 'increase'
          ? this.headcountChange * this.newPositionAvgCost
          : 0; // Reductions don't have a direct cost impact here
    },

    async applyHeadcountChanges() {
      let positionsToChange = this.positions;

      if (this.headcountChangeTarget === 'department' && this.headcountChangeDepartment) {
        positionsToChange = this.positions.filter(p => p.department_id === this.headcountChangeDepartment);
      } else if (this.headcountChangeTarget === 'function' && this.headcountChangeFunction) {
        positionsToChange = this.positions.filter(p => p.function === this.headcountChangeFunction);
      }

      if (this.headcountChangeType === 'increase') {
        // Add new positions
        const newPositions = [];
        for (let i = 0; i < this.headcountChange; i++) {
          const newPosition = {
            name: `New Position ${this.positions.length + i + 1}`,
            fully_loaded_cost: this.newPositionAvgCost,
            department_id: this.headcountChangeTarget === 'department' ? this.headcountChangeDepartment : null,
            function: this.headcountChangeTarget === 'function' ? this.headcountChangeFunction : null,
            scenario_id: this.selectedScenarioId,
            organization_id: this.organization.id,
          };
          newPositions.push(newPosition);
        }

        try {
          await Promise.all(newPositions.map(position => axios.post(`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions`, position)));
          await this.loadScenario();
          this.showHeadcountModelingForm = false;
        } catch (error) {
          console.error('Error adding new positions:', error);
        }
      } else {
        // Remove positions
        if (positionsToChange.length < this.headcountChange) {
          alert("Can't remove more positions than exist.");
          return;
        }

        try {
          const positionsToRemove = positionsToChange.slice(0, this.headcountChange);
          await Promise.all(positionsToRemove.map(position => axios.delete(`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions/${position.id}`)));
          await this.loadScenario();
          this.showHeadcountModelingForm = false;
        } catch (error) {
          console.error('Error removing positions:', error);
        }
      }
    },
  },

  mounted() {
    this.fetchScenarios();
  }
};
</script>


<style scoped>
 .to-be-modeling {
   display: flex;
   flex-direction: column;
   height: 100%;
 }

.scenario-controls {
  display: flex;
  justify-content: space-between;
  padding: 1rem;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
  flex-wrap: wrap;
  gap: 1rem;
}

.scenario-selector {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.scenario-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-create, .btn-action {
  padding: 0.5rem 0.75rem;
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-create {
  background-color: #4caf50;
  color: white;
  border-color: #388e3c;
}

.btn-create:hover {
  background-color: #388e3c;
}

.btn-action:hover {
  background-color: #f0f0f0;
}

.scenario-metrics {
  padding: 1rem;
  background-color: #fff;
  border-bottom: 1px solid #ddd;
}

.metrics-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.comparison-labels {
  display: flex;
  gap: 1rem;
}

.comparison-label {
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.comparison-label.current {
  background-color: #e6f7ff;
  color: #1890ff;
}

.comparison-label.comparison {
  background-color: #fff7e6;
  color: #fa8c16;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.metric-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  padding: 1rem;
  display: flex;
  flex-direction: column;
}

.metric-card.metric-comparing {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.metric-header {
  margin-bottom: 0.5rem;
}

.metric-content {
  display: flex;
  flex-direction: column;
  flex-grow: 1;
}

.metric-current {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.metric-comparison {
  display: flex;
  flex-direction: column;
  border-left: 1px solid #eee;
  padding-left: 1rem;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: bold;
  margin-bottom: 0.25rem;
}

.metric-goal {
  font-size: 0.875rem;
  color: #666;
}

.metric-difference {
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.increase {
  color: #52c41a;
}

.decrease {
  color: #f5222d;
}

.modeling-tabs {
  display: flex;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.tab {
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.tab:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.tab.active {
  border-bottom: 2px solid #4caf50;
  color: #4caf50;
}

.modeling-content {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
}

.department-modeling {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.department-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.department-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.department-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  overflow: hidden;
  transition: box-shadow 0.3s ease;
}

.department-card:hover {
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
}

.department-card.is-editing {
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
}

.card-header {
  padding: 1rem;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h4 {
  margin: 0;
  font-size: 1.25rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.card-actions i {
  cursor: pointer;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.card-actions i:hover {
  opacity: 1;
}

.card-content {
  padding: 1rem;
}

.department-stats {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-item {
  display: flex;
  justify-content: space-between;
}

.comparison-stats {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-diff {
  font-size: 0.875rem;
  margin-left: 0.5rem;
}

.department-edit-form {
  padding: 1rem;
  border-top: 1px solid #eee;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: space-between;
}

.btn-save, .btn-cancel, .btn-apply {
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-save, .btn-apply {
  background-color: #4caf50;
  color: white;
  border: none;
}

.btn-cancel {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  color: #333;
}

.btn-add {
  padding: 0.5rem 1rem;
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cost-modeling, .headcount-modeling {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.cost-controls, .headcount-controls {
  display: flex;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.cost-summary, .headcount-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.summary-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  padding: 1.5rem;
}

.summary-value {
  font-size: 2rem;
  font-weight: bold;
  margin: 1rem 0 0.5rem;
}

.summary-comparison {
  font-size: 0.875rem;
  color: #666;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.comparison-summary {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.comparison-item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
}

.comparison-diff {
  font-weight: bold;
  padding: 0.5rem 0;
  border-top: 1px solid #eee;
  margin-top: 0.5rem;
}

.goal-good {
  color: #52c41a;
}

.goal-bad {
  color: #f5222d;
}

.cost-charts, .headcount-charts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 1.5rem;
}

.chart-container {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  padding: 1.5rem;
}

.chart-container h3 {
  margin-top: 0;
  margin-bottom: 1rem;
}

.cost-modeling-form, .headcount-modeling-form {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  max-width: 500px;
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
  z-index: 1000;
}

.form-header {
  padding: 1rem;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-header h3 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
}

.form-body {
  padding: 1.5rem;
}

.input-group {
  display: flex;
  align-items: center;
}

.input-addon {
  padding: 0.5rem;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-left: none;
  border-radius: 0 0.25rem 0.25rem 0;
}

.input-prefix {
  padding: 0.5rem;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-right: none;
  border-radius: 0.25rem 0 0 0.25rem;
}

.input-group input {
  border-radius: 0.25rem 0 0 0.25rem;
}

.input-group input + .input-addon + input {
  border-radius: 0;
}

.cost-preview, .headcount-preview {
  margin: 1.5rem 0;
  padding: 1rem;
  background-color: #f9f9f9;
  border-radius: 0.25rem;
  border: 1px solid #eee;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.preview-diff {
  text-align: center;
  font-weight: bold;
  padding: 0.5rem 0;
  border-top: 1px solid #ddd;
  margin-top: 0.5rem;
}

.preview-increase {
  color: #52c41a;
}

.preview-decrease {
  color: #f5222d;
}

.cost-impact {
  margin: 1.5rem 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background-color: #f9f9f9;
  border-radius: 0.25rem;
  border: 1px solid #eee;
}

.impact-label {
  font-weight: 500;
}

.impact-value {
  font-size: 1.25rem;
  font-weight: bold;
}

.impact-increase {
  color: #f5222d;
}

.impact-decrease {
  color: #52c41a;
}

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  width: 90%;
  max-width: 500px;
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
}

.modal-header {
  padding: 1rem;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem;
  background-color: #f5f5f5;
  border-top: 1px solid #ddd;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.btn-primary {
  padding: 0.5rem 1rem;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-primary:disabled {
  background-color: #9e9e9e;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 0.5rem 1rem;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  color: #333;
  border-radius: 0.25rem;
  cursor: pointer;
}

.form-help {
  font-size: 0.75rem;
  color: #666;
  margin-top: 0.25rem;
}

@media (max-width: 768px) {
  .scenario-controls {
    flex-direction: column;
    align-items: stretch;
  }

  .scenario-selector {
    flex-direction: column;
    align-items: stretch;
  }

  .scenario-actions {
    justify-content: space-between;
  }

  .cost-charts, .headcount-charts {
    grid-template-columns: 1fr;
  }
}
</style>
