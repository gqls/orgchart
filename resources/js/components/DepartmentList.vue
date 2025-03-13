// resources/js/components/DepartmentList.vue
<template>
  <div class="department-list">
    <div class="page-header">
      <h1>Departments</h1>
      <button class="btn-primary" @click="showCreateModal = true">
        <i class="fas fa-plus"></i> Add Department
      </button>
    </div>

    <div v-if="loading" class="loading">
      <p>Loading departments...</p>
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>
    <div v-else class="departments-grid">
      <div v-for="department in departments" :key="department.id" class="department-card">
        <div class="card-header" :style="{ backgroundColor: department.color || '#1976d2' }">
          <h4>{{ department.name }}</h4>
          <div class="card-actions">
            <i class="fas fa-edit" @click="editDepartment(department)"></i>
            <i class="fas fa-trash" @click="confirmDelete(department)"></i>
          </div>
        </div>
        <div class="card-content">
          <p v-if="department.code"><strong>Code:</strong> {{ department.code }}</p>
          <p v-if="department.description"><strong>Description:</strong> {{ department.description }}</p>
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
            <label for="name">Name:</label>
            <input id="name" v-model="formData.name" type="text" required>
          </div>

          <div class="form-group">
            <label for="code">Code:</label>
            <input id="code" v-model="formData.code" type="text">
          </div>

          <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" v-model="formData.description" rows="3"></textarea>
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
      loading: true,
      error: null,
      showCreateModal: false,
      editingDepartment: null,
      deletingDepartment: null,
      formData: {
        name: '',
        code: '',
        description: '',
        color: '#4caf50'
      }
    };
  },
  created() {
    this.fetchDepartments();
  },
  methods: {
    async fetchDepartments() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get(`/api/organizations/${this.id}/departments`);
        this.departments = response.data;
      } catch (error) {
        console.error('Error fetching departments:', error);
        this.error = 'Failed to load departments. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    editDepartment(department) {
      this.editingDepartment = department;
      this.formData = {
        name: department.name,
        code: department.code || '',
        description: department.description || '',
        color: department.color || '#4caf50'
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
        color: '#4caf50'
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

        // Refresh the departments list
        await this.fetchDepartments();

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

        // Refresh the departments list
        await this.fetchDepartments();

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
  margin-bottom: 2rem;
}

.departments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.department-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
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
  font-size: 1.1rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.card-actions i {
  cursor: pointer;
  padding: 0.25rem;
}

.card-content {
  padding: 1rem;
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

.text-warning {
  color: #ff9800;
}

.loading,
.error-message {
  text-align: center;
  padding: 2rem;
}
</style>