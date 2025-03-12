// resources/js/components/OrgChart.vue
<template>
  <div class="org-chart-container">
    <div class="org-chart-controls">
      <div class="org-chart-actions">
        <button @click="toggleEditMode" class="btn-action" :class="{ active: editMode }">
          <i class="fas fa-edit"></i> Edit Structure
        </button>
        <button v-if="editMode" @click="createNewPosition" class="btn-action">
          <i class="fas fa-plus"></i> Add Position
        </button>
        <button @click="toggleViewMode" class="btn-action">
          <i class="fas fa-eye"></i> {{ viewMode === 'horizontal' ? 'Horizontal' : 'Vertical' }}
        </button>
        <button v-if="editMode" @click="finalizeStructure" class="btn-action" :disabled="!hasChanges">
          <i class="fas fa-save"></i> Finalize Structure
        </button>
      </div>

      <div class="org-chart-filters">
        <div class="filter-group">
          <label for="scenario-select">Scenario:</label>
          <select id="scenario-select" v-model="selectedScenarioId" @change="loadScenario">
            <option v-for="scenario in scenarios" :key="scenario.id" :value="scenario.id">
              {{ scenario.name }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label for="function-filter">Function:</label>
          <select id="function-filter" v-model="functionFilter">
            <option value="">All Functions</option>
            <option v-for="func in functions" :key="func" :value="func">{{ func }}</option>
          </select>
        </div>
      </div>
    </div>

    <div class="org-chart-wrapper" :class="viewMode">
      <div
          class="org-chart"
          ref="orgChartContainer"
          @mousemove="onMouseMove"
          @mouseup="onMouseUp"
      >
        <div
            v-for="node in filteredNodes"
            :key="node.id"
            class="org-node"
            :class="[
            getNodeStatusClass(node),
            { 'selected': selectedNode && selectedNode.id === node.id }
          ]"
            :style="getNodeStyle(node)"
            @mousedown="onNodeMouseDown(node, $event)"
            @click="selectNode(node)"
        >
          <div class="node-content">
            <div class="node-header" :style="getNodeHeaderStyle(node)">
              <strong>{{ node.title }}</strong>
              <div v-if="editMode" class="node-actions">
                <i class="fas fa-times" @click.stop="removeNode(node)"></i>
              </div>
            </div>
            <div class="node-details">
              <div v-if="node.name">{{ node.name }}</div>
              <div>Employee ID: {{ node.employee_id || 'N/A' }}</div>
              <div>Function: {{ node.function || 'N/A' }}</div>
              <div>Grade: {{ node.grade || 'N/A' }}</div>
            </div>
          </div>
        </div>

        <!-- Connector lines -->
        <svg class="connectors" ref="connectors">
          <path
              v-for="(connector, index) in connectors"
              :key="index"
              :d="connector.path"
              :class="connector.status"
          ></path>
        </svg>
      </div>
    </div>

    <!-- Node detail panel -->
    <div v-if="selectedNode" class="node-detail-panel">
      <div class="panel-header">
        <h3>{{ selectedNode.title }}</h3>
        <button @click="closeDetailPanel" class="btn-close">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="panel-content">
        <div v-if="editMode" class="edit-form">
          <div class="form-group">
            <label for="title">Title:</label>
            <input id="title" v-model="selectedNode.title" type="text">
          </div>

          <div class="form-group">
            <label for="name">Name:</label>
            <input id="name" v-model="selectedNode.name" type="text">
          </div>

          <div class="form-group">
            <label for="employee-id">Employee ID:</label>
            <input id="employee-id" v-model="selectedNode.employee_id" type="text">
          </div>

          <div class="form-group">
            <label for="function">Function:</label>
            <input id="function" v-model="selectedNode.function" type="text">
          </div>

          <div class="form-group">
            <label for="grade">Grade:</label>
            <input id="grade" v-model="selectedNode.grade" type="text">
          </div>

          <div class="form-group">
            <label for="fully-loaded-cost">Fully Loaded Cost:</label>
            <input id="fully-loaded-cost" v-model="selectedNode.fully_loaded_cost" type="number">
          </div>

          <div class="form-group">
            <label for="manager">Manager:</label>
            <select id="manager" v-model="selectedNode.manager_id">
              <option value="">No Manager</option>
              <option
                  v-for="node in nodes.filter(n => n.id !== selectedNode.id)"
                  :key="node.id"
                  :value="node.id"
              >
                {{ node.title }} ({{ node.name || 'Unnamed' }})
              </option>
            </select>
          </div>

          <div class="form-actions">
            <button @click="saveNode" class="btn-primary">Save</button>
            <button @click="cancelEdit" class="btn-secondary">Cancel</button>
          </div>
        </div>

        <div v-else class="node-info">
          <div class="info-group">
            <h4>Position Details</h4>
            <div class="info-row">
              <span class="info-label">Name:</span>
              <span class="info-value">{{ selectedNode.name || 'N/A' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Employee ID:</span>
              <span class="info-value">{{ selectedNode.employee_id || 'N/A' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Function:</span>
              <span class="info-value">{{ selectedNode.function || 'N/A' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Grade:</span>
              <span class="info-value">{{ selectedNode.grade || 'N/A' }}</span>
            </div>
            <div class="info-row">
              <span class="info-label">Fully Loaded Cost:</span>
              <span class="info-value">{{ formatCurrency(selectedNode.fully_loaded_cost) }}</span>
            </div>
          </div>

          <div class="info-group">
            <h4>Reporting Structure</h4>
            <div class="info-row">
              <span class="info-label">Reports to:</span>
              <span class="info-value">
                {{ getManagerName(selectedNode) || 'No Manager' }}
              </span>
            </div>
            <div class="info-row">
              <span class="info-label">Direct Reports:</span>
              <span class="info-value">{{ getDirectReportsCount(selectedNode) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
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
      nodes: [],
      relationships: [],
      scenarios: [],
      selectedScenarioId: null,
      viewMode: 'horizontal',
      editMode: false,
      functionFilter: '',
      functions: [],
      selectedNode: null,
      draggingNode: null,
      dragOffset: { x: 0, y: 0 },
      connectors: [],
      hasChanges: false,
      originalNodes: []
    };
  },

  computed: {
    filteredNodes() {
      if (!this.functionFilter) {
        return this.nodes;
      }

      return this.nodes.filter(node => node.function === this.functionFilter);
    }
  },

  watch: {
    scenario: {
      immediate: true,
      handler(newVal) {
        if (newVal) {
          this.selectedScenarioId = newVal.id;
          this.loadScenario();
        }
      }
    },

    nodes: {
      deep: true,
      handler() {
        this.updateConnectors();
      }
    },

    filteredNodes: {
      handler() {
        this.$nextTick(() => {
          this.updateConnectors();
        });
      }
    }
  },

  created() {
    this.fetchScenarios();
  },

  mounted() {
    window.addEventListener('resize', this.updateConnectors);
  },

  beforeDestroy() {
    window.removeEventListener('resize', this.updateConnectors);
  },

  methods: {
    async fetchScenarios() {
      try {
        const response = await axios.get(`/api/organizations/${this.organization.id}/scenarios`);
        this.scenarios = response.data;
      } catch (error) {
        console.error('Error fetching scenarios:', error);
      }
    },

    async loadScenario() {
      try {
        // Fetch positions for this scenario
        const positionsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/positions`
        );

        // Fetch relationships for this scenario
        const relationshipsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/relationships`
        );

        this.nodes = positionsResponse.data;
        this.originalNodes = JSON.parse(JSON.stringify(this.nodes));
        this.relationships = relationshipsResponse.data;

        // Extract unique functions for filtering
        this.functions = [...new Set(this.nodes.filter(node => node.function).map(node => node.function))];

        // Calculate initial positions if not already set
        this.nodes.forEach(node => {
          if (!node.x || !node.y) {
            // Set default positions based on org hierarchy
            this.calculateNodePosition(node);
          }
        });

        this.updateConnectors();
        this.hasChanges = false;

      } catch (error) {
        console.error('Error loading scenario data:', error);
      }
    },

    calculateNodePosition(node) {
      // Find the node's level in the hierarchy
      let level = 0;
      let currentNode = node;
      let parentIds = new Set();

      while (true) {
        const relationship = this.relationships.find(rel => rel.direct_report_position_id === currentNode.id);
        if (!relationship) break;

        // Check for circular reference
        if (parentIds.has(relationship.manager_position_id)) break;

        parentIds.add(relationship.manager_position_id);
        currentNode = this.nodes.find(n => n.id === relationship.manager_position_id);
        if (!currentNode) break;

        level++;
      }

      // Get siblings (nodes with the same manager)
      const managerRelationship = this.relationships.find(rel => rel.direct_report_position_id === node.id);
      let siblingsCount = 0;
      let siblingIndex = 0;

      if (managerRelationship) {
        const siblings = this.relationships
            .filter(rel => rel.manager_position_id === managerRelationship.manager_position_id)
            .map(rel => rel.direct_report_position_id);

        siblingsCount = siblings.length;
        siblingIndex = siblings.indexOf(node.id);
      }

      // Calculate position based on level and siblings
      if (this.viewMode === 'horizontal') {
        node.x = 200 + level * 300;
        node.y = 100 + (siblingIndex * 120);
      } else {
        node.x = 100 + (siblingIndex * 220);
        node.y = 100 + level * 150;
      }
    },

    updateConnectors() {
      this.connectors = [];

      // Only create connectors for visible nodes
      const visibleNodeIds = this.filteredNodes.map(node => node.id);

      this.relationships.forEach(rel => {
        const sourceNode = this.nodes.find(node => node.id === rel.manager_position_id);
        const targetNode = this.nodes.find(node => node.id === rel.direct_report_position_id);

        // Skip if either node is not in the filtered view
        if (!sourceNode || !targetNode ||
            !visibleNodeIds.includes(sourceNode.id) ||
            !visibleNodeIds.includes(targetNode.id)) {
          return;
        }

        const sourceEl = this.$el.querySelector(`.org-node[data-id="${sourceNode.id}"]`);
        const targetEl = this.$el.querySelector(`.org-node[data-id="${targetNode.id}"]`);

        if (!sourceEl || !targetEl) return;

        const sourceRect = sourceEl.getBoundingClientRect();
        const targetRect = targetEl.getBoundingClientRect();
        const containerRect = this.$refs.orgChartContainer.getBoundingClientRect();

        const sourceX = sourceNode.x + sourceRect.width / 2;
        const sourceY = sourceNode.y + sourceRect.height;
        const targetX = targetNode.x + targetRect.width / 2;
        const targetY = targetNode.y;

        let path;
        if (this.viewMode === 'horizontal') {
          path = `M${sourceX},${sourceY} C${sourceX},${sourceY + 50} ${targetX},${targetY - 50} ${targetX},${targetY}`;
        } else {
          path = `M${sourceX},${sourceY} C${sourceX},${sourceY + 30} ${targetX},${targetY - 30} ${targetX},${targetY}`;
        }

        // Get position status
        let status = 'unchanged';
        const position = this.nodes.find(n => n.id === targetNode.id);
        if (position && position.pivot && position.pivot.status) {
          status = position.pivot.status;
        }

        this.connectors.push({
          path,
          status
        });
      });
    },

    toggleEditMode() {
      this.editMode = !this.editMode;
      if (!this.editMode) {
        this.selectedNode = null;
      }
    },

    toggleViewMode() {
      this.viewMode = this.viewMode === 'horizontal' ? 'vertical' : 'horizontal';

      // Recalculate positions when view mode changes
      this.nodes.forEach(node => {
        this.calculateNodePosition(node);
      });
    },

    createNewPosition() {
      const newPosition = {
        id: `temp-${Date.now()}`,
        title: 'New Position',
        function: this.functionFilter || null,
        x: 100,
        y: 100,
        isNew: true
      };

      this.nodes.push(newPosition);
      this.selectNode(newPosition);
      this.hasChanges = true;
    },

    selectNode(node) {
      this.selectedNode = JSON.parse(JSON.stringify(node));
    },

    closeDetailPanel() {
      this.selectedNode = null;
    },

    async saveNode() {
      const index = this.nodes.findIndex(n => n.id === this.selectedNode.id);

      if (index === -1) return;

      // Update the node in the array
      this.nodes.splice(index, 1, {
        ...this.selectedNode,
        isModified: true
      });

      // Update manager relationship if changed
      if (this.selectedNode.manager_id) {
        // Check if relationship already exists
        const existingRel = this.relationships.find(
            rel => rel.direct_report_position_id === this.selectedNode.id
        );

        if (existingRel) {
          // Update existing relationship
          if (existingRel.manager_position_id !== this.selectedNode.manager_id) {
            existingRel.manager_position_id = this.selectedNode.manager_id;
            existingRel.isModified = true;
          }
        } else {
          // Create new relationship
          this.relationships.push({
            manager_position_id: this.selectedNode.manager_id,
            direct_report_position_id: this.selectedNode.id,
            isNew: true
          });
        }
      } else {
        // Remove relationship if manager is cleared
        const relIndex = this.relationships.findIndex(
            rel => rel.direct_report_position_id === this.selectedNode.id
        );

        if (relIndex !== -1) {
          this.relationships.splice(relIndex, 1);
        }
      }

      this.closeDetailPanel();
      this.hasChanges = true;
    },

    cancelEdit() {
      // If this was a new node that was canceled, remove it
      if (this.selectedNode.isNew) {
        const index = this.nodes.findIndex(n => n.id === this.selectedNode.id);
        if (index !== -1) {
          this.nodes.splice(index, 1);
        }
      }

      this.closeDetailPanel();
    },

    removeNode(node) {
      // Remove the node
      const index = this.nodes.findIndex(n => n.id === node.id);
      if (index !== -1) {
        this.nodes.splice(index, 1);
      }

      // Remove relationships involving this node
      this.relationships = this.relationships.filter(
          rel => rel.manager_position_id !== node.id && rel.direct_report_position_id !== node.id
      );

      if (this.selectedNode && this.selectedNode.id === node.id) {
        this.selectedNode = null;
      }

      this.hasChanges = true;
    },

    onNodeMouseDown(node, event) {
      if (!this.editMode) return;

      this.draggingNode = node;
      const rect = event.target.getBoundingClientRect();
      this.dragOffset = {
        x: event.clientX - rect.left,
        y: event.clientY - rect.top
      };

      event.preventDefault();
    },

    onMouseMove(event) {
      if (!this.draggingNode || !this.editMode) return;

      const containerRect = this.$refs.orgChartContainer.getBoundingClientRect();
      const x = event.clientX - containerRect.left - this.dragOffset.x;
      const y = event.clientY - containerRect.top - this.dragOffset.y;

      // Update node position
      const node = this.nodes.find(n => n.id === this.draggingNode.id);
      if (node) {
        node.x = Math.max(0, x);
        node.y = Math.max(0, y);
        node.isModified = true;
      }

      this.hasChanges = true;
    },

    onMouseUp() {
      this.draggingNode = null;
    },

    async finalizeStructure() {
      if (!this.hasChanges) return;

      try {
        // Create/update positions
        for (const node of this.nodes) {
          if (node.isNew) {
            // Create new position
            await axios.post(
                `/api/organizations/${this.organization.id}/positions`,
                {
                  title: node.title,
                  name: node.name,
                  employee_id: node.employee_id,
                  function: node.function,
                  grade: node.grade,
                  fully_loaded_cost: node.fully_loaded_cost
                }
            );
          } else if (node.isModified) {
            // Update existing position
            await axios.put(
                `/api/organizations/${this.organization.id}/positions/${node.id}`,
                {
                  title: node.title,
                  name: node.name,
                  employee_id: node.employee_id,
                  function: node.function,
                  grade: node.grade,
                  fully_loaded_cost: node.fully_loaded_cost
                }
            );
          }
        }

        // Create/update relationships
        for (const rel of this.relationships) {
          if (rel.isNew) {
            // Create new relationship
            await axios.post(
                `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/relationships`,
                {
                  manager_position_id: rel.manager_position_id,
                  direct_report_position_id: rel.direct_report_position_id
                }
            );
          } else if (rel.isModified) {
            // Update existing relationship
            await axios.put(
                `/api/organizations/${this.organization.id}/scenarios/${this.selectedScenarioId}/relationships/${rel.id}`,
                {
                  manager_position_id: rel.manager_position_id
                }
            );
          }
        }

        // Reload the scenario to refresh data
        await this.loadScenario();
        this.hasChanges = false;
        this.editMode = false;

      } catch (error) {
        console.error('Error finalizing structure:', error);
      }
    },

    getNodeStatusClass(node) {
      if (!node.pivot || !node.pivot.status) return '';
      return `status-${node.pivot.status}`;
    },

    getNodeStyle(node) {
      return {
        left: `${node.x}px`,
        top: `${node.y}px`,
        'z-index': this.selectedNode && this.selectedNode.id === node.id ? 10 : 1
      };
    },

    getNodeHeaderStyle(node) {
      // Determine color based on function
      let color = '#3498db'; // Default blue

      switch (node.function) {
        case 'Leadership':
          color = '#9b59b6'; // Purple
          break;
        case 'Finance':
          color = '#2ecc71'; // Green
          break;
        case 'HR':
          color = '#e74c3c'; // Red
          break;
        case 'BD':
          color = '#f39c12'; // Orange
          break;
        case 'Pop Up':
          color = '#1abc9c'; // Teal
          break;
      }

      return {
        backgroundColor: color
      };
    },

    getManagerName(node) {
      const relationship = this.relationships.find(rel => rel.direct_report_position_id === node.id);
      if (!relationship) return null;

      const manager = this.nodes.find(n => n.id === relationship.manager_position_id);
      if (!manager) return null;

      return `${manager.title} (${manager.name || 'Unnamed'})`;
    },

    getDirectReportsCount(node) {
      return this.relationships.filter(rel => rel.manager_position_id === node.id).length;
    },

    formatCurrency(value) {
      if (!value) return 'N/A';
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
    }
  }
};
</script>

<style scoped>
.org-chart-container {
  position: relative;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.org-chart-controls {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
}

.org-chart-actions {
  display: flex;
  gap: 0.5rem;
}

.org-chart-filters {
  display: flex;
  gap: 1rem;
}

.filter-group {
  display: flex;
  align-items: center;
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

.btn-action.active {
  background-color: #e6f7ff;
  border-color: #1890ff;
  color: #1890ff;
}

.org-chart-wrapper {
  flex: 1;
  overflow: auto;
  position: relative;
}

.org-chart {
  position: relative;
  min-height: 800px;
  min-width: 1200px;
}

.connectors {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 0;
}

.connectors path {
  fill: none;
  stroke: #888;
  stroke-width: 2;
}

.connectors path.status-new {
  stroke: #4caf50;
}

.connectors path.status-changed {
  stroke: #ff9800;
}

.connectors path.status-removed {
  stroke: #f44336;
  stroke-dasharray: 5, 5;
}

.org-node {
  position: absolute;
  width: 200px;
  background-color: #fff;
  border-radius: 0.25rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
  transition: box-shadow 0.3s;
  overflow: hidden;
  cursor: pointer;
}

.org-node:hover {
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
}

.org-node.selected {
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
  border: 2px solid #1890ff;
}

.org-node.status-new {
  border: 2px solid #4caf50;
}

.org-node.status-changed {
  border: 2px solid #ff9800;
}

.org-node.status-removed {
  border: 2px dashed #f44336;
  opacity: 0.7;
}

.node-content {
  display: flex;
  flex-direction: column;
}

.node-header {
  padding: 0.5rem;
  color: white;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
}

.node-actions {
  cursor: pointer;
}

.node-details {
  padding: 0.5rem;
  font-size: 0.75rem;
}

.node-detail-panel {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 300px;
  background-color: #fff;
  border-radius: 0.25rem;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.19), 0 6px 6px rgba(0, 0, 0, 0.23);
  z-index: 100;
}

.panel-header {
  padding: 0.75rem;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-close {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1rem;
}

.panel-content {
  padding: 1rem;
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
.form-group select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.form-actions {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
}

.btn-primary,
.btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-primary {
  background-color: #1890ff;
  color: white;
  border: none;
}

.  btn-secondary {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  color: #333;
}

.info-group {
  margin-bottom: 1.5rem;
}

.info-group h4 {
  margin-bottom: 0.5rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid #eee;
}

.info-row {
  display: flex;
  margin-bottom: 0.5rem;
}

.info-label {
  width: 130px;
  font-weight: 500;
  color: #666;
}

.horizontal .org-chart {
  flex-direction: column;
}

.vertical .org-chart {
  flex-direction: row;
}