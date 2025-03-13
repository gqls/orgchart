// resources/js/components/ScenarioList.vue
<template>
  <div class="scenario-list">
    <div class="page-header">
      <h1>Scenarios</h1>
      <button class="btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Create New Scenario
      </button>
    </div>

    <div v-if="loading" class="loading">
      <p>Loading scenarios...</p>
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>
    <div v-else class="scenarios-grid">
      <div v-for="scenario in scenarios" :key="scenario.id"
           class="scenario-card"
           :class="{'current-scenario': scenario.is_current, 'base-scenario': scenario.is_base}">
        <div class="scenario-header">
          <h3>{{ scenario.name }}</h3>
          <div class="scenario-badges">
            <span v-if="scenario.is_current" class="badge current">Current</span>
            <span v-if="scenario.is_base" class="badge base">Base</span>
          </div>
        </div>

        <div class="scenario-body">
          <p v-if="scenario.description" class="scenario-description">{{ scenario.description }}</p>
          <p class="scenario-meta">
            <span>Created by: {{ scenario.user ? scenario.user.name : 'Unknown' }}</span>
            <span>{{ formatDate(scenario.created_at) }}</span>
          </p>
        </div>

        <div class="scenario-actions">
          <button @click="viewScenario(scenario)" class="btn-action">
            <i class="fas fa-eye"></i> View
          </button>
          <button v-if="!scenario.is_current" @click="setAsCurrent(scenario)" class="btn-action">
            <i class="fas fa-check-circle"></i> Set as Current
          </button>
          <button @click="editScenario(scenario)" class="btn-action">
            <i class="fas fa-edit"></i> Edit
          </button>
          <button v-if="!scenario.is_current" @click="confirmDelete(scenario)" class="btn-action btn-danger">
            <i class="fas fa-trash"></i> Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingScenario" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingScenario ? 'Edit Scenario' : 'Create New Scenario' }}</h3>
          <button @click="cancelEdit" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="scenario-name">Name:</label>
            <input id="scenario-name" v-model="formData.name" type="text" required>
          </div>

          <div class="form-group">
            <label for="scenario-description">Description:</label>
            <textarea id="scenario-description" v-model="formData.description" rows="3"></textarea>
          </div>

          <div v-if="!editingScenario" class="form-group">
            <label for="base-scenario">Base Scenario:</label>
            <select id="base-scenario" v-model="formData.base_scenario_id">
              <option v-for="s in scenarios" :key="s.id" :value="s.id">{{ s.name }}</option>
            </select>
            <div class="form-help">New scenario will copy structure and data from the selected base scenario</div>
          </div>

          <div v-if="editingScenario" class="form-group checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="formData.is_base">
              Set as Base Scenario
            </label>
            <div class="form-help">Base scenarios serve as templates for new scenarios</div>
          </div>

          <div v-if="editingScenario" class="form-group checkbox-group">
            <label class="checkbox-label">
              <input type="checkbox" v-model="formData.is_current">
              Set as Current Scenario
            </label>
            <div class="form-help">The current scenario is used for default views</div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="saveScenario" class="btn-primary" :disabled="!formData.name">
            {{ editingScenario ? 'Update' : 'Create' }}
          </button>
          <button @click="cancelEdit" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingScenario" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Confirm Delete</h3>
          <button @click="deletingScenario = null" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <p>Are you sure you want to delete the scenario <strong>{{ deletingScenario.name }}</strong>?</p>
          <p class="text-warning">This action cannot be undone.</p>
        </div>

        <div class="modal-footer">
          <button @click="deleteScenario" class="btn-danger">Delete</button>
          <button @click="deletingScenario = null" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'ScenarioList',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      scenarios: [],
      loading: true,
      error: null,
      showCreateModal: false,
      editingScenario: null,
      deletingScenario: null,
      formData: {
        name: '',
        description: '',
        base_scenario_id: null,
        is_base: false,
        is_current: false
      }
    };
  },
  created() {
    this.fetchScenarios();
  },
  methods: {
    async fetchScenarios() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/api/organizations/${this.id}/scenarios`);
        this.scenarios = response.data;

        // If creating a new scenario, default to current scenario as base
        if (this.scenarios.length > 0 && !this.formData.base_scenario_id) {
          const currentScenario = this.scenarios.find(s => s.is_current);
          if (currentScenario) {
            this.formData.base_scenario_id = currentScenario.id;
          } else {
            this.formData.base_scenario_id = this.scenarios[0].id;
          }
        }
      } catch (error) {
        console.error('Error fetching scenarios:', error);
        this.error = 'Failed to load scenarios. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    formatDate(dateString) {
      if (!dateString) return '';

      const date = new Date(dateString);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    },

    viewScenario(scenario) {
      this.$router.push({
        name: 'organizations.scenarios.show',
        params: {
          id: this.id,
          scenarioId: scenario.id
        }
      });
    },

    async setAsCurrent(scenario) {
      try {
        await axios.put(`/api/organizations/${this.id}/scenarios/${scenario.id}`, {
          is_current: true
        });

        // Refresh the scenarios list
        await this.fetchScenarios();
      } catch (error) {
        console.error('Error setting current scenario:', error);
        alert('Failed to set current scenario. Please try again.');
      }
    },

    editScenario(scenario) {
      this.editingScenario = scenario;
      this.formData = {
        name: scenario.name,
        description: scenario.description || '',
        is_base: scenario.is_base,
        is_current: scenario.is_current
      };
    },

    confirmDelete(scenario) {
      this.deletingScenario = scenario;
    },

    cancelEdit() {
      this.showCreateModal = false;
      this.editingScenario = null;
      this.formData = {
        name: '',
        description: '',
        base_scenario_id: this.scenarios.length > 0 ?
            (this.scenarios.find(s => s.is_current)?.id || this.scenarios[0].id) : null,
        is_base: false,
        is_current: false
      };
    },

    async saveScenario() {
      try {
        if (this.editingScenario) {
          // Update existing scenario
          await axios.put(`/api/organizations/${this.id}/scenarios/${this.editingScenario.id}`, this.formData);
        } else {
          // Create new scenario
          await axios.post(`/api/organizations/${this.id}/scenarios`, this.formData);
        }

        // Refresh the scenarios list
        await this.fetchScenarios();

        // Close the modal
        this.cancelEdit();
      } catch (error) {
        console.error('Error saving scenario:', error);
        alert('Failed to save scenario. Please try again.');
      }
    },

    async deleteScenario() {
      try {
        await axios.delete(`/api/organizations/${this.id}/scenarios/${this.deletingScenario.id}`);

        // Refresh the scenarios list
        await this.fetchScenarios();

        // Close the modal
        this.deletingScenario = null;
      } catch (error) {
        console.error('Error deleting scenario:', error);

        if (error.response && error.response.status === 422) {
          alert('Cannot delete the current scenario. Please set another scenario as current first.');
        } else {
          alert('Failed to delete scenario. Please try again.');
        }
      }
    }
  }
};
</script>

<style scoped>
.scenario-list {
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.scenarios-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.scenario-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  border-top: 4px solid #9e9e9e;
  transition: transform 0.2s, box-shadow 0.2s;
}

.scenario-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.current-scenario {
  border-top-color: #4caf50;
}

.base-scenario {
  border-top-color: #2196f3;
}

.current-scenario.base-scenario {
  border-top-color: #9c27b0;
}

.scenario-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #eee;
}

.scenario-header h3 {
  margin: 0;
  font-size: 1.2rem;
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

.scenario-body {
  padding: 1rem;
  flex: 1;
}

.scenario-description {
  margin-bottom: 1rem;
  color: #555;
}

.scenario-meta {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #777;
}

.scenario-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 1rem;
  background-color: #f9f9f9;
  border-top: 1px solid #eee;
}

.btn-action {
  padding: 0.5rem 0.75rem;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.btn-action:hover {
  background-color: #e9e9e9;
}

.btn-action.btn-danger {
  color: #f44336;
}

.btn-action.btn-danger:hover {
  background-color: #ffebee;
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
  z-index: 100;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 1rem;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.checkbox-group {
  margin-top: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}

.form-help {
  font-size: 0.875rem;
  color: #777;
  margin-top: 0.25rem;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.form-group input[type="checkbox"] {
  width: auto;
}

.text-warning {
  color: #ff9800;
}

.loading,
.error-message {
  text-align: center;
  padding: 2rem;
}
</style>