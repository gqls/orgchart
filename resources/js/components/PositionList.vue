// resources/js/components/DepartmentList.vue
<template>
  <div class="department-list">
    <div class="page-header">
      <h1>Departments</h1>
      <button class="btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Add Department
      </button>
    </div>

    <div class="filters">
      <div class="filter-group">
        <label for="search-filter">Search:</label>
        <input id="search-filter" v-model="filters.search" type="text" placeholder="Search departments...">
      </div>
    </div>

    <div v-if="loading" class="loading">
      <p>Loading departments...</p>
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>
    <div v-else>
      <div class="departments-grid">
        <div
            v-for="department in filteredDepartments"
            :key="department.id"
            class="department-card"
        >
          <div class="card-header" :style="{ backgroundColor: department.color || '#1976d2' }">
            <h3>{{ department.name }}</h3>
            <div class="card-actions">
              <button class="btn-icon" @click="editDepartment(department)" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="btn-icon" @click="confirmDelete(department)" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <div class="card-content">
            <div v-if="department.code" class="card-detail">
              <span class="detail-label">Code:</span>
              <span class="detail-value">{{ department.code }}</span>
            </div>
            <div v-if="department.description" class="card-detail">
              <span class="detail-label">Description:</span>
              <span class="detail-value">{{ department.description }}</span>
            </div>
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
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingDepartment" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingDepartment ? 'Edit Department' : 'Create Department' }}</h3>
          <button @click="cancelEdit" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="form-group">
            <label for="name">Department Name:</label>
            <input id="name" v-model="formData.name" type="text" required>
          </div>

          <div class="form-group">
            <label for="code">Department Code:</label>
            <input id="code" v-model="formData.code" type="text" placeholder="Optional code">
          </div>

          <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" v-model="formData.description" rows="3" placeholder="Department description"></textarea>
          </div>

          <div class="form-group">
            <label for="color">Color:</label>
            <input id="color" v-model="formData.color" type="color">
          </div>
        </div>

        <div class="modal-footer">
          <button @click="saveDepartment" class="btn-primary" :disabled="!formData.name">
            {{ editingDepartment ? 'Update' : 'Create' }}
          </button>
          <button @click="cancelEdit" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingDepartment" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Confirm Delete</h3>
          <button @click="deletingDepartment = null" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <p>Are you sure you want to delete the department <strong>{{ deletingDepartment.name }}</strong>?</p>
          <p v-if="getDepartmentPositionCount(deletingDepartment) > 0" class="text-warning">
            Warning: This department has {{ getDepartmentPositionCount(deletingDepartment) }} positions associated with it.
          </p>
          <p class="text-warning">This action cannot be undone.</p>
        </div>

        <div class="modal-footer">
          <button @click="deleteDepartment" class="btn-danger">Delete</button>
          <button @click="deletingDepartment = null" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DepartmentList',
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },
  data() {
    return {
      departments: [],
      positions: [],
      loading: true,
      error: null,
      showCreateModal: false,
      editingDepartment: null,
      deletingDepartment: null,
      filters: {
        search: ''
      },
      formData: {
        name: '',
        code: '',
        description: '',
        color: '#1976d2'
      }
    };
  },
  computed: {
    filteredDepartments() {
      if (!this.filters.search) {
        return this.departments;
      }

      const search = this.filters.search.toLowerCase();
      return this.departments.filter(dept =>
          (dept.name && dept.name.toLowerCase().includes(search)) ||
          (dept.code && dept.code.toLowerCase().includes(search)) ||
          (dept.description && dept.description.toLowerCase().includes(search))
      );
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
        // Fetch departments
        const deptResponse = await axios.get(`/api/organizations/${this.id}/departments`);
        this.departments = deptResponse.data;

        // Fetch positions to calculate department stats
        const posResponse = await axios.get(`/api/organizations/${this.id}/positions`);
        this.positions = posResponse.data;
      } catch (error) {
        console.error('Error fetching data:', error);
        this.error = 'Failed to load data. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    getDepartmentPositionCount(department) {
      if (!department || !this.positions.length) return 0;
      return this.positions.filter(p => p.department_id === department.id).length;
    },

    getDepartmentCost(department) {
      if (!department || !this.positions.length) return 0;

      return this.positions
          .filter(p => p.department_id === department.id)
          .reduce((sum, position) => sum + (parseFloat(position.fully_loaded_cost) || 0), 0);
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

    editDepartment(department) {
      this.editingDepartment = department;
      this.formData = {
        name: department.name,
        code: department.code || '',
        description: department.description || '',
        color: department.color || '#1976d2'
      };
    },

    confirmDelete(department) {
      this.deletingDepartment = department;
    },

    cancelEdit() {
      this.showCreateModal = false;
      this.editingDepartment = null;
      this.formData = {
        name: '',
        code: '',
        description: '',
        color: '#1976d2'
      };
    },

    async saveDepartment() {
      try {
        if (this.editingDepartment) {
          // Update existing department
          await axios.put(`/api/organizations/${this.id}/departments/${this.editingDepartment.id}`, this.formData);
        } else {
          // Create new department
          await axios.post(`/api/organizations/${this.id}/departments`, this.formData);
        }

        // Refresh the data
        await this.fetchData();

        // Close the modal
        this.cancelEdit();
      } catch (error) {
        console.error('Error saving department:', error);
        alert('Failed to save department. Please try again.');
      }
    },

    async deleteDepartment() {
      try {
        await axios.delete(`/api/organizations/${this.id}/departments/${this.deletingDepartment.id}`);

        // Refresh the data
        await this.fetchData();

        // Close the modal
        this.deletingDepartment = null;
      } catch (error) {
        console.error('Error deleting department:', error);
        alert('Failed to delete department. Please try again.');
      }
    }
  }
};
</script>

<style scoped>
.department-list {
  padding: 1rem;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.filters {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  background-color: #f5f5f5;
  padding: 1rem;
  border-radius: 0.5rem;
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

.filter-group input {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.departments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.department-card {
  background-color: white;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.department-card:hover {
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
}

.card-header {
  padding: 1rem;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  color: white;
  cursor: pointer;
  font-size: 0.875rem;
  padding: 0.25rem;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.btn-icon:hover {
  opacity: 1;
}

.card-content {
  padding: 1rem;
}

.card-detail {
  margin-bottom: 0.75rem;
}

.detail-label {
  font-weight: 500;
  margin-right: 0.5rem;
}

.department-stats {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #eee;
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
}

.stat-value {
  font-weight: 600;
  font-size: 1.1rem;
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

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.form-group textarea {
  resize: vertical;
}

.text-warning {
  color: #ff9800;
}

.loading,
.error-message {
  text-align: center;
  padding: 2rem;
}

.btn-primary {
  background-color: #4caf50;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary:hover {
  background-color: #388e3c;
}

.btn-primary:disabled {
  background-color: #a5d6a7;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
}

.btn-danger {
  background-color: #f44336;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.25rem;
  cursor: pointer;
}

.btn-danger:hover {
  background-color: #d32f2f;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #777;
}

.btn-close:hover {
  color: #333;
}
</style>