},

getTotalCostDiff() {
const currentCost = this.getMetricValue('total_fully_loaded_cost');
const comparisonCost = this.getComparisonMetricValue('total_fully_loaded_cost');
const diff = currentCost - comparisonCost;
const percentage = comparisonCost ? (diff / comparisonCost * 100).toFixed(1) : 0;

return `${diff > 0 ? '+' : ''}${this.formatCurrency(diff)} (${percentage}%)`;
},

getTotalCostDiffClass() {
const diff = this.getMetricValue('total_fully_loaded_cost') - this.getComparisonMetricValue('total_fully_loaded_cost');
// For costs, a decrease is generally considered positive
return diff < 0 ? 'diff-good' : diff > 0 ? 'diff-bad' : '';
},

getHeadcountDiff() {
const currentHeadcount = this.getMetricValue('headcount');
const comparisonHeadcount = this.getComparisonMetricValue('headcount');
const diff = currentHeadcount - comparisonHeadcount;
const percentage = comparisonHeadcount ? (diff / comparisonHeadcount * 100).toFixed(1) : 0;

return `${diff > 0 ? '+' : ''}${diff} (${percentage}%)`;
},

getHeadcountDiffClass() {
const diff = this.getMetricValue('headcount') - this.getComparisonMetricValue('headcount');
// For headcount, judgment depends on goal - just show direction
return diff > 0 ? 'diff-increase' : diff < 0 ? 'diff-decrease' : '';
},

calculateNewTotalCost() {
const currentCost = this.getMetricValue('total_fully_loaded_cost');
let targetCost = 0;

// Determine which positions are affected by the change
let affectedPositions = [];

switch (this.costChangeTarget) {
case 'all':
affectedPositions = this.positions;
break;
case 'department':
affectedPositions = this.positions.filter(p => p.department_id === this.costChangeDepartment);
break;
case 'function':
affectedPositions = this.positions.filter(p => p.function === this.costChangeFunction);
break;
case 'grade':
affectedPositions = this.positions.filter(p => p.grade === this.costChangeGrade);
break;
}

// Calculate cost of affected positions
const affectedCost = affectedPositions.reduce((sum, p) => sum + parseFloat(p.fully_loaded_cost || 0), 0);

// Calculate change amount
const changeAmount = affectedCost * (this.costChangePercentage / 100);

// Calculate new total cost
return currentCost + changeAmount;
},

calculateNewHeadcount() {
const currentHeadcount = this.getMetricValue('headcount');
const change = parseInt(this.headcountChange) || 0;

return this.headcountChangeType === 'increase'
? currentHeadcount + change
: currentHeadcount - change;
},

calculateCostImpact() {
if (this.headcountChangeType === 'increase') {
return this.headcountChange * this.newPositionAvgCost;
} else {
// For reductions, estimate based on average cost
return -1 * this.headcountChange * this.newPositionAvgCost;
}
},

createNewScenario() {
this.fetchScenarios();
this.showNewScenarioModal = true;
this.newScenario.baseScenarioId = this.selectedScenarioId;
},

async saveNewScenario() {
try {
const response = await axios.post(
`/api/organizations/${this.organization.id}/scenarios`,
{
name: this.newScenario.name,
description: this.newScenario.description,
base_scenario_id: this.newScenario.baseScenarioId
}
);

// Reload scenarios and select the newly created one
await this.fetchScenarios();
this.selectedScenarioId = response.data.id;
this.loadScenario();

this.showNewScenarioModal = false;
this.newScenario = {
name: '',
description: '',
baseScenarioId: null
};

} catch (error) {
console.error('Error creating new scenario:', error);
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

compareScenarios() {
this.showComparisonModal = true;
},

async startComparison() {
this.isComparing = true;
this.showComparisonModal = false;

await this.loadComparisonScenario();
},

cancelComparison() {
this.showComparisonModal = false;
this.comparisonScenarioId = null;
},

endComparison() {
this.isComparing = false;
this.comparisonScenarioId = null;
this.comparisonScenario = null;
this.comparisonPositions = [];
this.comparisonMetrics = [];

// Re-initialize charts
this.$nextTick(() => {
this.initCharts();
});
},

async makeCurrentScenario() {
try {
await axios.put(
`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}`,
{
is_current: true
}
);

// Reload scenarios
await this.fetchScenarios();
await this.loadScenario();

} catch (error) {
console.error('Error setting current scenario:', error);
}
},

addDepartment() {
this.editingDepartment = {
name: 'New Department',
color: '#' + Math.floor(Math.random()*16777215).toString(16),
isNew: true
};
},

editDepartment(department) {
this.editingDepartment = { ...department };
},

async saveDepartment() {
try {
if (this.editingDepartment.isNew) {
// Create new department
await axios.post(
`/api/organizations/${this.organization.id}/departments`,
{
name: this.editingDepartment.name,
color: this.editingDepartment.color
}
);
} else {
// Update existing department
await axios.put(
`/api/organizations/${this.organization.id}/departments/${this.editingDepartment.id}`,
{
name: this.editingDepartment.name,
color: this.editingDepartment.color
}
);
}

// Reload departments
const response = await axios.get(
`/api/organizations/${this.organization.id}/departments`
);
this.departments = response.data;

this.editingDepartment = null;

} catch (error) {
console.error('Error saving department:', error);
}
},

cancelEdit() {
this.editingDepartment = null;
},

async deleteDepartment(department) {
if (!confirm(`Are you sure you want to delete the department "${department.name}"?`)) {
return;
}

try {
await axios.delete(
`/api/organizations/${this.organization.id}/departments/${department.id}`
);

// Reload departments
const response = await axios.get(
`/api/organizations/${this.organization.id}/departments`
);
this.departments = response.data;

} catch (error) {
console.error('Error deleting department:', error);
}
},

applyAcrossTheBoard() {
this.showCostModelingForm = true;
this.costChangePercentage = 0;
this.costChangeTarget = 'all';
this.costChangeDepartment = this.departments.length > 0 ? this.departments[0].id : null;
this.costChangeFunction = this.functions.length > 0 ? this.functions[0] : null;
this.costChangeGrade = this.grades.length > 0 ? this.grades[0] : null;
},

cancelCostModeling() {
this.showCostModelingForm = false;
},

async applyCostChange() {
try {
// Determine which positions are affected by the change
let affectedPositions = [];

switch (this.costChangeTarget) {
case 'all':
affectedPositions = this.positions;
break;
case 'department':
affectedPositions = this.positions.filter(p => p.department_id === this.costChangeDepartment);
break;
case 'function':
affectedPositions = this.positions.filter(p => p.function === this.costChangeFunction);
break;
case 'grade':
affectedPositions = this.positions.filter(p => p.grade === this.costChangeGrade);
break;
}

// Apply cost change to each position
for (const position of affectedPositions) {
const currentCost = parseFloat(position.fully_loaded_cost) || 0;
const newCost = currentCost * (1 + this.costChangePercentage / 100);

await axios.put(
`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions/${position.id}`,
{
fully_loaded_cost: newCost,
status: 'changed'
}
);
}

// Recalculate metrics
await axios.post(
`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/calculate-metrics`
);

// Reload data
await this.loadScenario();

this.showCostModelingForm = false;

} catch (error) {
console.error('Error applying cost change:', error);
}
},

applyHeadcountChange() {
this.showHeadcountModelingForm = true;
this.headcountChange = 0;
this.headcountChangeType = 'increase';
this.headcountChangeTarget = 'all';
this.headcountChangeDepartment = this.departments.length > 0 ? this.departments[0].id : null;
this.headcountChangeFunction = this.functions.length > 0 ? this.functions[0] : null;
},

cancelHeadcountModeling() {
this.showHeadcountModelingForm = false;
},

async applyHeadcountChanges() {
try {
if (this.headcountChangeType === 'increase') {
// Add new positions
for (let i = 0; i < this.headcountChange; i++) {
const departmentId = this.headcountChangeTarget === 'department'
? this.headcountChangeDepartment
: null;

const functionName = this.headcountChangeTarget === 'function'
? this.headcountChangeFunction
: null;

// Create a new position
await axios.post(
`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions`,
{
title: 'New Position',
department_id: departmentId,
function: functionName,
fully_loaded_cost: this.newPositionAvgCost,
status: 'new'
}
);
}
} else {
// Remove positions
let positionsToRemove = [];

switch (this.headcountChangeTarget) {
case 'all':
positionsToRemove = [...this.positions].sort(() => Math.random() - 0.5);
break;
case 'department':
positionsToRemove = this.positions.filter(p => p.department_id === this.headcountChangeDepartment);
break;
case 'function':
positionsToRemove = this.positions.filter(p => p.function === this.headcountChangeFunction);
break;
}

// Limit to the number requested
positionsToRemove = positionsToRemove.slice(0, this.headcountChange);

// Mark positions as removed
for (const position of positionsToRemove) {
await axios.put(
`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions/${position.id}`,
{
status: 'removed'
}
);
}
}

// Recalculate metrics
await axios.post(
`/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/calculate-metrics`
);

// Reload data
await this.loadScenario();

this.showHeadcountModelingForm = false;

} catch (error) {
console.error('Error applying headcount changes:', error);
}
}
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
  align-items: center;
  padding: 1rem;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.scenario-selector {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.scenario-selector label {
  font-weight: 500;
}

.scenario-selector select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  min-width: 200px;
}

.btn-create {
  padding: 0.5rem 0.75rem;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.scenario-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  padding: 0.5rem 0.75rem;
  background-color: #fff;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.btn-action:hover {
  background-color: #f9f9f9;
}

.btn-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.scenario-metrics {
  background-color: #fff;
  padding: 1rem;
  margin: 1rem;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
}

.metrics-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.metrics-header h3 {
  margin: 0;
}

.comparison-labels {
  display: flex;
  gap: 1rem;
}

.comparison-label {
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.comparison-label.current {
  background-color: rgba(76, 175, 80, 0.1);
  color: #2e7d32;
}

.comparison-label.comparison {
  background-color: rgba(33, 150, 243, 0.1);
  color: #0d47a1;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.metric-card {
  background-color: #f9f9f9;
  border-radius: 0.5rem;
  padding: 1rem;
  border: 1px solid #eee;
}

.metric-card.metric-comparing {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.metric-header {
  margin-bottom: 0.5rem;
}

.metric-header h4 {
  margin: 0;
  font-size: 1rem;
  color: #555;
}

.metric-content {
  display: flex;
  flex-direction: column;
}

.metric-current, .metric-comparison {
  display: flex;
  flex-direction: column;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: bold;
  color: #333;
}

.metric-goal {
  font-size: 0.875rem;
  color: #666;
  margin-top: 0.25rem;
}

.metric-difference {
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.diff-good {
  color: #4caf50;
}

.diff-bad {
  color: #f44336;
}

.diff-increase {
  color: #2196f3;
}

.diff-decrease {
  color: #ff9800;
}

.modeling-tabs {
  display: flex;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
  margin: 0 1rem;
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

.modeling-content {
  flex: 1;
  padding: 1rem;
  overflow-y: auto;
}

/* Department Modeling */
.department-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.department-header h3 {
  margin: 0;
}

.btn-add {
  padding: 0.5rem 0.75rem;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 0.25rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
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
  position: relative;
}

.department-card.is-editing {
  border: 2px solid #2196f3;
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
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.card-actions i {
  cursor: pointer;
}

.card-content {
  padding: 1rem;
}

.department-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
}

.stat-label {
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-weight: bold;
}

.stat-diff {
  font-size: 0.75rem;
  margin-top: 0.25rem;
}

.comparison-stats {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px dashed #ddd;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.department-edit-form {
  padding: 1rem;
  background-color: #f9f9f9;
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

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: space-between;
}

.btn-save,
.btn-cancel,
.btn-apply {
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-save,
.btn-apply {
  background-color: #4caf50;
  color: white;
  border: none;
}

.btn-cancel {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  color: #333;
}

/* Cost Modeling */
.cost-controls {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.cost-summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  flex: 1;
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

.summary-value {
  font-size: 2rem;
  font-weight: bold;
  color: #333;
  margin-bottom: 0.5rem;
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
  align-items: center;
}

.comparison-label {
  font-weight: 500;
}

.comparison-diff {
  font-weight: 500;
  margin-top: 0.5rem;
}

.goal-good {
  color: #4caf50;
}

.goal-bad {
  color: #f44336;
}

.cost-charts,
.headcount-charts {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
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
  font-size: 1.1rem;
  color: #555;
}

/* Modal Styling */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #ddd;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
  border: none;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  color: #333;
}

/* Cost Modeling Form */
.cost-modeling-form,
.headcount-modeling-form {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  z-index: 1000;
}

.form-header {
  padding: 1rem;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-header h3 {
  margin: 0;
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
  border-radius: 0;
}

.input-group input:first-child {
  border-radius: 0.25rem 0 0 0.25rem;
}

.input-group input:last-child {
  border-radius: 0 0.25rem 0.25rem 0;
}

.form-help {
  font-size: 0.75rem;
  color: #666;
  margin-top: 0.25rem;
}

.cost-preview,
.headcount-preview,
.cost-impact {
  margin: 1.5rem 0;
  padding: 1rem;
  background-color: #f9f9f9;
  border-radius: 0.5rem;
  border: 1px solid #eee;
}

.preview-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.preview-label {
  font-weight: 500;
}

.preview-diff,
.impact-value {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px dashed #ddd;
  font-weight: bold;
  text-align: right;
}

.preview-increase,
.impact-increase {
  color: #f44336;
}

.preview-decrease,
.impact-decrease {
  color: #4caf50;
}

.impact-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
  .scenario-controls,
  .cost-controls {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }

  .scenario-selector {
    flex-direction: column;
    align-items: flex-start;
  }

  .department-stats,
  .comparison-stats {
    grid-template-columns: 1fr;
  }

  .cost-charts,
  .headcount-charts {
    grid-template-columns: 1fr;
  }
}
</style>
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

        this.charts.functionCost = new Chart(funcCtx, {
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
                beginAtZero: true
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
                beginAtZero: true
              }
            }
          }
        });
      }
    },

    getDepartmentCostData() {
      const departments = {};
      const comparisonDepartments = {};

      // Current scenario data
      this.positions.forEach(position => {
        if (position.fully_loaded_cost && position.department_id) {
          if (!departments[position.department_id]) {
            departments[position.department_id] = 0;
          }
          departments[position.department_id] += parseFloat(position.fully_loaded_cost || 0);
        }
      });

      // Comparison scenario data (if comparing)
      if (this.isComparing) {
        this.comparisonPositions.forEach(position => {
          if (position.fully_loaded_cost && position.department_id) {
            if (!comparisonDepartments[position.department_id]) {
              comparisonDepartments[position.department_id] = 0;
            }
            comparisonDepartments[position.department_id] += parseFloat(position.fully_loaded_cost || 0);
          }
        });
      }

      const labels = [];
      const currentValues = [];
      const comparisonValues = [];

      // Combine all department IDs
      const allDeptIds = [...new Set([
        ...Object.keys(departments),
        ...Object.keys(comparisonDepartments)
      ])];

      // Create data arrays
      allDeptIds.forEach(deptId => {
        const department = this.departments.find(d => d.id === parseInt(deptId));
        if (department) {
          labels.push(department.name);
          currentValues.push(departments[deptId] || 0);
          comparisonValues.push(comparisonDepartments[deptId] || 0);
        }
      });

      return { labels, currentValues, comparisonValues };
    },

    getFunctionCostData() {
      const functions = {};
      const comparisonFunctions = {};

      // Current scenario data
      this.positions.forEach(position => {
        if (position.fully_loaded_cost) {
          const func = position.function || 'No Function';
          if (!functions[func]) {
            functions[func] = 0;
          }
          functions[func] += parseFloat(position.fully_loaded_cost || 0);
        }
      });

      // Comparison scenario data (if comparing)
      if (this.isComparing) {
        this.comparisonPositions.forEach(position => {
          if (position.fully_loaded_cost) {
            const func = position.function || 'No Function';
            if (!comparisonFunctions[func]) {
              comparisonFunctions[func] = 0;
            }
            comparisonFunctions[func] += parseFloat(position.fully_loaded_cost || 0);
          }
        });
      }

      const labels = [];
      const currentValues = [];
      const comparisonValues = [];

      // Combine all functions
      const allFunctions = [...new Set([
        ...Object.keys(functions),
        ...Object.keys(comparisonFunctions)
      ])];

      // Create data arrays
      allFunctions.forEach(func => {
        labels.push(func);
        currentValues.push(functions[func] || 0);
        comparisonValues.push(comparisonFunctions[func] || 0);
      });

      return { labels, currentValues, comparisonValues };
    },

    getDepartmentHeadcountData() {
      const departments = {};
      const comparisonDepartments = {};

      // Current scenario data
      this.positions.forEach(position => {
        if (position.department_id) {
          if (!departments[position.department_id]) {
            departments[position.department_id] = 0;
          }
          departments[position.department_id]++;
        }
      });

      // Comparison scenario data (if comparing)
      if (this.isComparing) {
        this.comparisonPositions.forEach(position => {
          if (position.department_id) {
            if (!comparisonDepartments[position.department_id]) {
              comparisonDepartments[position.department_id] = 0;
            }
            comparisonDepartments[position.department_id]++;
          }
        });
      }

      const labels = [];
      const currentValues = [];
      const comparisonValues = [];

      // Combine all department IDs
      const allDeptIds = [...new Set([
        ...Object.keys(departments),
        ...Object.keys(comparisonDepartments)
      ])];

      // Create data arrays
      allDeptIds.forEach(deptId => {
        const department = this.departments.find(d => d.id === parseInt(deptId));
        if (department) {
          labels.push(department.name);
          currentValues.push(departments[deptId] || 0);
          comparisonValues.push(comparisonDepartments[deptId] || 0);
        }
      });

      return { labels, currentValues, comparisonValues };
    },

    getFunctionHeadcountData() {
      const functions = {};
      const comparisonFunctions = {};

      // Current scenario data
      this.positions.forEach(position => {
        const func = position.function || 'No Function';
        if (!functions[func]) {
          functions[func] = 0;
        }
        functions[func]++;
      });

      // Comparison scenario data (if comparing)
      if (this.isComparing) {
        this.comparisonPositions.forEach(position => {
          const func = position.function || 'No Function';
          if (!comparisonFunctions[func]) {
            comparisonFunctions[func] = 0;
          }
          comparisonFunctions[func]++;
        });
      }

      const labels = [];
      const currentValues = [];
      const comparisonValues = [];

      // Combine all functions
      const allFunctions = [...new Set([
        ...Object.keys(functions),
        ...Object.keys(comparisonFunctions)
      ])];

      // Create data arrays
      allFunctions.forEach(func => {
        labels.push(func);
        currentValues.push(functions[func] || 0);
        comparisonValues.push(comparisonFunctions[func] || 0);
      });

      return { labels, currentValues, comparisonValues };
    },

    getDepartmentPositionCount(department, useComparison = false) {
      const positionsToUse = useComparison ? this.comparisonPositions : this.positions;
      return positionsToUse.filter(p => p.department_id === department.id).length;
    },

    getDepartmentCost(department, useComparison = false) {
      const positionsToUse = useComparison ? this.comparisonPositions : this.positions;
      return positionsToUse
          .filter(p => p.department_id === department.id)
          .reduce((sum, p) => sum + parseFloat(p.fully_loaded_cost || 0), 0);
    },

    getPositionDiff(department) {
      const currentCount = this.getDepartmentPositionCount(department);
      const comparisonCount = this.getDepartmentPositionCount(department, true);
      const diff = currentCount - comparisonCount;

      return diff === 0 ? 'No change' : (diff > 0 ? `+${diff}` : diff);
    },

    getPositionDiffClass(department) {
      const diff = this.getDepartmentPositionCount(department) - this.getDepartmentPositionCount(department, true);
      return diff > 0 ? 'diff-increase' : diff < 0 ? 'diff-decrease' : '';
    },

    getCostDiff(department) {
      const currentCost = this.getDepartmentCost(department);
      const comparisonCost = this.getDepartmentCost(department, true);
      const diff = currentCost - comparisonCost;

      return diff === 0 ? 'No change' : this.formatCurrency(diff);
    },

    getCostDiffClass(department) {
      const diff = this.getDepartmentCost(department) - this.getDepartmentCost(department, true);
      // For costs, an increase is generally considered negative (red), a decrease positive (green)
      return diff > 0 ? 'diff-increase' : diff < 0 ? 'diff-decrease' : '';
    },

    getMetricValue(code) {
      const metric = this.metrics.find(m => m.code === code);
      return metric && metric.pivot ? parseFloat(metric.pivot.value) : 0;
    },

    getComparisonMetricValue(code) {
      const metric = this.comparisonMetrics.find(m => m.code === code);
      return metric && metric.pivot ? parseFloat(metric.pivot.value) : 0;
    },

    formatMetricValue(metric) {
      if (!metric.pivot) return '';

      if (metric.format === 'currency' || metric.code.includes('cost')) {
        return this.formatCurrency(metric.pivot.value);
      } else if (metric.format === 'percentage' || metric.code.includes('percentage')) {
        return `${metric.pivot.value}%`;
      } else if (metric.format === 'number' || !isNaN(metric.pivot.value)) {
        return new Intl.NumberFormat('en-US').format(metric.pivot.value);
      }

      return metric.pivot.value;
    },

    formatComparisonValue(metric) {
      if (!this.isComparing) return '';

      const comparisonMetric = this.comparisonMetrics.find(m => m.code === metric.code);
      if (!comparisonMetric || !comparisonMetric.pivot) return 'N/A';

      if (metric.format === 'currency' || metric.code.includes('cost')) {
        return this.formatCurrency(comparisonMetric.pivot.value);
      } else if (metric.format === 'percentage' || metric.code.includes('percentage')) {
        return `${comparisonMetric.pivot.value}%`;
      } else if (metric.format === 'number' || !isNaN(comparisonMetric.pivot.value)) {
        return new Intl.NumberFormat('en-US').format(comparisonMetric.pivot.value);
      }

      return comparisonMetric.pivot.value;
    },

    calculateDifference(metric) {
      if (!this.isComparing) return '';

      const currentValue = metric.pivot ? parseFloat(metric.pivot.value) : 0;

      const comparisonMetric = this.comparisonMetrics.find(m => m.code === metric.code);
      if (!comparisonMetric || !comparisonMetric.pivot) return '';

      const comparisonValue = parseFloat(comparisonMetric.pivot.value);
      const diff = currentValue - comparisonValue;

      if (diff === 0) return 'No change';

      if (metric.format === 'currency' || metric.code.includes('cost')) {
        return this.formatCurrency(diff);
      } else if (metric.format === 'percentage' || metric.code.includes('percentage')) {
        return `${diff > 0 ? '+' : ''}${diff.toFixed(1)}%`;
      }

      return `${diff > 0 ? '+' : ''}${diff}`;
    },

    getDifferenceClass(metric) {
      if (!this.isComparing) return '';

      const currentValue = metric.pivot ? parseFloat(metric.pivot.value) : 0;

      const comparisonMetric = this.comparisonMetrics.find(m => m.code === metric.code);
      if (!comparisonMetric || !comparisonMetric.pivot) return '';

      const comparisonValue = parseFloat(comparisonMetric.pivot.value);
      const diff = currentValue - comparisonValue;

      if (diff === 0) return '';

      // For cost metrics, a decrease is good
      if (metric.code.includes('cost')) {
        return diff < 0 ? 'diff-good' : 'diff-bad';
      }

      // For efficiency metrics like spans, higher is generally better
      if (metric.code.includes('span') || metric.code.includes('efficiency')) {
        return diff > 0 ? 'diff-good' : 'diff-bad';
      }

      // Default behavior - just show increase/decrease without judgment
      return diff > 0 ? 'diff-increase' : 'diff-decrease';
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

      // For cost metrics, lower is better
      if (metricCode.includes('cost')) {
        return value <= goal ? 'goal-good' : 'goal-bad';
      }

      // For other metrics, higher values are better
      return value >= goal ? 'goal-good' : 'goal-bad';
    },

    getHeadcountGoalDiff() {
      const metric = this.metrics.find(m => m.code === 'headcount');
      if (!metric || !metric.pivot || !metric.pivot.goal) return '';

      const value = parseFloat(metric.pivot.value);
      const goal = parseFloat(metric.pivot.goal);
      const diff = value - goal;
      const percentage = (diff / goal * 100).toFixed(1);

      return `${diff > 0 ? '+' : ''}${diff} (${percentage}%)`;
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
                Goal: {{ formatMetricValue({...metric, pivot: {value: metric.pivot.goal}}) }}
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
              <div class="summary-comparison" v-if="metrics.find(m => m.code === 'total_fully_loaded_cost').pivot.goal">
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
              <div class="summary-comparison" v-if="metrics.find(m => m.code === 'headcount').pivot.goal">
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
        <div class="impact-value" :class="{