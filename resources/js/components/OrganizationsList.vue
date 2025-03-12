// resources/js/components/OrganizationsList.vue
<template>
  <div class="organizations-container">
    <div class="organizations-header">
      <h1>Your Organizations</h1>
      <button @click="$router.push({ name: 'organizations.create' })" class="btn btn-primary">
        <i class="fas fa-plus"></i> New Organization
      </button>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner">
        <i class="fas fa-circle-notch fa-spin"></i>
      </div>
      <p>Loading organizations...</p>
    </div>

    <div v-else-if="error" class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      <p>{{ error }}</p>
      <button @click="fetchOrganizations" class="btn-retry">Retry</button>
    </div>

    <div v-else-if="organizations.length === 0" class="empty-state">
      <div class="empty-icon">
        <i class="fas fa-building"></i>
      </div>
      <h2>No Organizations Found</h2>
      <p>You don't have any organizations yet. Create your first organization to get started.</p>
      <button @click="$router.push({ name: 'organizations.create' })" class="btn btn-primary">
        Create Organization
      </button>
    </div>

    <div v-else class="organizations-grid">
      <div
          v-for="org in organizations"
          :key="org.id"
          class="organization-card"
          @click="navigateToOrganization(org)"
      >
        <div class="card-header" :style="{ backgroundColor: org.primary_color || '#4caf50' }">
          <div v-if="org.logo_path" class="org-logo">
            <img :src="getLogoUrl(org.logo_path)" :alt="org.name + ' logo'" />
          </div>
          <div v-else class="org-logo placeholder">
            {{ getOrgInitials(org.name) }}
          </div>
        </div>
        <div class="card-body">
          <h3 class="org-name">{{ org.name }}</h3>
          <p class="org-description">{{ truncateDescription(org.description) }}</p>
          <div class="org-meta">
            <span class="org-created">
              <i class="fas fa-calendar-alt"></i> 
              Created: {{ formatDate(org.created_at) }}
            </span>
            <span v-if="isOrgAdmin(org)" class="org-admin-badge">
              <i class="fas fa-crown"></i> Admin
            </span>
          </div>
        </div>
        <div class="card-actions">
          <button class="btn-action" @click.stop="navigateToOrganization(org)">
            <i class="fas fa-chart-network"></i> Open
          </button>
          <button v-if="isOrgAdmin(org)" class="btn-action settings" @click.stop="editOrganization(org)">
            <i class="fas fa-cog"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Organization Modal -->
    <div v-if="showEditModal" class="modal-backdrop" @click.self="showEditModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Edit Organization</h3>
          <button @click="showEditModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="updateOrganization">
            <div class="form-group">
              <label for="org-name">Name</label>
              <input
                  id="org-name"
                  v-model="editForm.name"
                  type="text"
                  required
                  class="form-control"
              />
            </div>

            <div class="form-group">
              <label for="org-description">Description</label>
              <textarea
                  id="org-description"
                  v-model="editForm.description"
                  rows="3"
                  class="form-control"
              ></textarea>
            </div>

            <div class="form-group">
              <label for="org-primary-color">Primary Color</label>
              <div class="color-input">
                <input
                    id="org-primary-color"
                    v-model="editForm.primary_color"
                    type="color"
                    class="color-picker"
                />
                <input
                    v-model="editForm.primary_color"
                    type="text"
                    class="form-control color-text"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="org-secondary-color">Secondary Color</label>
              <div class="color-input">
                <input
                    id="org-secondary-color"
                    v-model="editForm.secondary_color"
                    type="color"
                    class="color-picker"
                />
                <input
                    v-model="editForm.secondary_color"
                    type="text"
                    class="form-control color-text"
                />
              </div>
            </div>

            <div class="form-group">
              <label for="org-logo">Logo</label>
              <div class="file-upload">
                <input
                    type="file"
                    id="org-logo"
                    @change="handleLogoUpload"
                    ref="logoInput"
                    accept="image/*"
                    class="file-input"
                />
                <button type="button" @click="triggerLogoUpload" class="btn-secondary">
                  <i class="fas fa-upload"></i> Choose File
                </button>
                <span v-if="logoFile" class="file-name">{{ logoFile.name }}</span>
                <span v-else-if="editForm.logo_path" class="file-name">Current logo</span>
                <span v-else class="file-name placeholder">No file chosen</span>
              </div>
              <div v-if="logoPreview" class="logo-preview">
                <img :src="logoPreview" alt="Logo preview" />
              </div>
            </div>

            <div class="form-group danger-zone">
              <h4>Danger Zone</h4>
              <button type="button" @click="confirmDeleteOrg" class="btn-danger">
                <i class="fas fa-trash-alt"></i> Delete Organization
              </button>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" @click="updateOrganization" class="btn-primary" :disabled="updating">
            <span v-if="updating">
              <i class="fas fa-spinner fa-spin"></i> Saving...
            </span>
            <span v-else>Save Changes</span>
          </button>
          <button type="button" @click="showEditModal = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-backdrop">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3>Confirm Deletion</h3>
          <button @click="showDeleteModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <p class="warning-message">
            <i class="fas fa-exclamation-triangle"></i>
            Are you sure you want to delete <strong>{{ editForm.name }}</strong>?
          </p>
          <p>This action cannot be undone. All data associated with this organization will be permanently deleted.</p>
          <div class="confirmation-input">
            <label for="confirm-name">Please type <strong>{{ editForm.name }}</strong> to confirm:</label>
            <input
                id="confirm-name"
                v-model="deleteConfirmation"
                type="text"
                class="form-control"
                placeholder="Type organization name"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button
              type="button"
              @click="deleteOrganization"
              class="btn-danger"
              :disabled="deleteConfirmation !== editForm.name || deleting"
          >
            <span v-if="deleting">
              <i class="fas fa-spinner fa-spin"></i> Deleting...
            </span>
            <span v-else>Delete Organization</span>
          </button>
          <button type="button" @click="showDeleteModal = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      organizations: [],
      loading: true,
      error: null,
      showEditModal: false,
      showDeleteModal: false,
      editForm: {
        id: null,
        name: '',
        description: '',
        primary_color: '#4caf50',
        secondary_color: '#2196f3',
        logo_path: null
      },
      logoFile: null,
      logoPreview: null,
      deleteConfirmation: '',
      updating: false,
      deleting: false
    };
  },

  created() {
    this.fetchOrganizations();
  },

  methods: {
    async fetchOrganizations() {
      this.loading = true;
      this.error = null;

      try {
        const response = await axios.get('/api/organizations');
        this.organizations = response.data;
      } catch (error) {
        console.error('Error fetching organizations:', error);
        this.error = 'Failed to load organizations. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    navigateToOrganization(org) {
      this.$router.push({ name: 'organizations.dashboard', params: { id: org.id } });
    },

    editOrganization(org) {
      this.editForm = {
        id: org.id,
        name: org.name,
        description: org.description || '',
        primary_color: org.primary_color || '#4caf50',
        secondary_color: org.secondary_color || '#2196f3',
        logo_path: org.logo_path
      };

      if (org.logo_path) {
        this.logoPreview = this.getLogoUrl(org.logo_path);
      } else {
        this.logoPreview = null;
      }

      this.logoFile = null;
      this.showEditModal = true;
    },

    triggerLogoUpload() {
      this.$refs.logoInput.click();
    },

    handleLogoUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.logoFile = file;

      // Create preview
      const reader = new FileReader();
      reader.onload = e => {
        this.logoPreview = e.target.result;
      };
      reader.readAsDataURL(file);
    },

    async updateOrganization() {
      this.updating = true;

      try {
        const formData = new FormData();
        formData.append('name', this.editForm.name);
        formData.append('description', this.editForm.description);
        formData.append('primary_color', this.editForm.primary_color);
        formData.append('secondary_color', this.editForm.secondary_color);

        if (this.logoFile) {
          formData.append('logo', this.logoFile);
        }

        // Use FormData with multipart/form-data for file upload
        const response = await axios.post(`/api/organizations/${this.editForm.id}?_method=PUT`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        // Update local data
        const index = this.organizations.findIndex(org => org.id === this.editForm.id);
        if (index !== -1) {
          this.organizations[index] = response.data;
        }

        this.showEditModal = false;
      } catch (error) {
        console.error('Error updating organization:', error);
        alert('Failed to update organization. Please try again.');
      } finally {
        this.updating = false;
      }
    },

    confirmDeleteOrg() {
      this.deleteConfirmation = '';
      this.showDeleteModal = true;
    },

    async deleteOrganization() {
      if (this.deleteConfirmation !== this.editForm.name) return;

      this.deleting = true;

      try {
        await axios.delete(`/api/organizations/${this.editForm.id}`);

        // Remove from local data
        this.organizations = this.organizations.filter(org => org.id !== this.editForm.id);

        this.showDeleteModal = false;
        this.showEditModal = false;
      } catch (error) {
        console.error('Error deleting organization:', error);
        alert('Failed to delete organization. Please try again.');
      } finally {
        this.deleting = false;
      }
    },

    getLogoUrl(path) {
      if (!path) return null;
      if (path.startsWith('http')) return path;
      return `/storage/${path}`;
    },

    getOrgInitials(name) {
      if (!name) return '';
      return name
          .split(' ')
          .map(word => word.charAt(0).toUpperCase())
          .slice(0, 2)
          .join('');
    },

    truncateDescription(description) {
      if (!description) return 'No description';
      return description.length > 100 ? description.substring(0, 97) + '...' : description;
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    },

    isOrgAdmin(org) {
      // Check pivot data for admin status
      return org.pivot && org.pivot.is_admin;
    }
  }
};
</script>

<style scoped>
.organizations-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.organizations-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.organizations-header h1 {
  font-size: 1.75rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
}

.spinner {
  font-size: 2rem;
  color: #4caf50;
  margin-bottom: 1rem;
}

.error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: #d32f2f;
}

.error-message i {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.btn-retry {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  cursor: pointer;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  color: #ccc;
  margin-bottom: 1rem;
}

.empty-state h2 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: #555;
}

.empty-state p {
  margin-bottom: 1.5rem;
  color: #777;
  max-width: 500px;
}

.organizations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.organization-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
  cursor: pointer;
}

.organization-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.card-header {
  height: 100px;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
}

.org-logo {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  overflow: hidden;
  background-color: white;
  display: flex;
  justify-content: center;
  align-items: center;
}

.org-logo img {
  max-width: 100%;
  max-height: 100%;
}

.org-logo.placeholder {
  font-size: 1.75rem;
  font-weight: 600;
  color: #333;
}

.card-body {
  padding: 1.25rem;
}

.org-name {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.org-description {
  margin-bottom: 1rem;
  color: #666;
  font-size: 0.875rem;
  line-height: 1.5;
  height: 4rem; /* Limit to about 2-3 lines */
  overflow: hidden;
}

.org-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: #666;
}

.org-created {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.org-admin-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #ffc107;
  font-weight: 500;
}

.card-actions {
  display: flex;
  border-top: 1px solid #eee;
}

.btn-action {
  flex: 1;
  padding: 0.75rem;
  text-align: center;
  background: none;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-action:hover {
  background-color: #f9f9f9;
}

.btn-action.settings {
  border-left: 1px solid #eee;
  flex: 0 0 50px;
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 0.5rem;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.modal-content.modal-sm {
  max-width: 400px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid #eee;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.25rem;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  cursor: pointer;
  color: #999;
}

.modal-body {
  padding: 1rem;
  flex: 1;
  overflow-y: auto;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  font-size: 1rem;
}

.color-input {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.color-picker {
  width: 40px;
  height: 40px;
  border: none;
  cursor: pointer;
}

.color-text {
  flex: 1;
}

.file-upload {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.file-input {
  display: none;
}

.file-name {
  flex: 1;
  padding: 0.5rem;
  background-color: #f5f5f5;
  border-radius: 0.25rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-name.placeholder {
  color: #999;
}

.logo-preview {
  margin-top: 0.5rem;
  width: 100px;
  height: 100px;
  border-radius: 0.25rem;
  overflow: hidden;
  border: 1px solid #ddd;
}

.logo-preview img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.danger-zone {
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #ffebee;
}

.danger-zone h4 {
  color: #d32f2f;
  margin-bottom: 0.75rem;
}

.btn-danger {
  padding: 0.5rem 1rem;
  background-color: #ffebee;
  color: #d32f2f;
  border: 1px solid #ffcdd2;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-danger:hover {
  background-color: #ffcdd2;
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.warning-message {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #d32f2f;
  font-weight: 500;
  margin-bottom: 1rem;
}

.confirmation-input {
  margin-top: 1.5rem;
}

.confirmation-input label {
  margin-bottom: 0.75rem;
}

.btn-primary {
  padding: 0.75rem 1rem;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 0.25rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: #43a047;
}

.btn-primary:disabled {
  background-color: #a5d6a7;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 0.75rem 1rem;
  background-color: #f0f0f0;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
}

@media (max-width: 768px) {
  .organizations-container {
    padding: 1rem;
  }

  .organizations-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }

  .organizations-grid {
    grid-template-columns: 1fr;
  }
}
</style>