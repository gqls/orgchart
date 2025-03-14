// resources/js/components/OrganizationSettingsCompare.vue
<template>
  <div class="settings-container">
    <div class="settings-header">
      <h1>Organization Settings</h1>
      <p class="subtitle">Manage your organization details, users, and preferences</p>
    </div>

    <div v-if="loading" class="loading-container">
      <div class="spinner">
        <i class="fas fa-circle-notch fa-spin"></i>
      </div>
      <p>Loading organization settings...</p>
    </div>

    <div v-else-if="error" class="error-message">
      <i class="fas fa-exclamation-circle"></i>
      <p>{{ error }}</p>
      <button @click="fetchOrganizationData" class="btn-retry">Retry</button>
    </div>

    <div v-else class="settings-content">
      <div class="tab-navigation">
        <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="['tab-button', { active: activeTab === tab.id }]"
        >
          <i :class="tab.icon"></i>
          {{ tab.name }}
        </button>
      </div>

      <div class="tab-content">
        <!-- General Settings Tab -->
        <div v-if="activeTab === 'general'" class="general-settings">
          <h2>Organization Details</h2>
          <form @submit.prevent="updateOrganizationDetails">
            <div class="form-group">
              <label for="org-name">Organization Name</label>
              <input
                  id="org-name"
                  v-model="organizationForm.name"
                  type="text"
                  required
                  class="form-control"
              />
            </div>

            <div class="form-group">
              <label for="org-description">Description</label>
              <textarea
                  id="org-description"
                  v-model="organizationForm.description"
                  rows="3"
                  class="form-control"
              ></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="primary-color">Primary Color</label>
                <div class="color-picker-group">
                  <input
                      id="primary-color"
                      v-model="organizationForm.primary_color"
                      type="color"
                      class="color-picker"
                  />
                  <input
                      v-model="organizationForm.primary_color"
                      type="text"
                      class="form-control color-text"
                  />
                </div>
              </div>

              <div class="form-group">
                <label for="secondary-color">Secondary Color</label>
                <div class="color-picker-group">
                  <input
                      id="secondary-color"
                      v-model="organizationForm.secondary_color"
                      type="color"
                      class="color-picker"
                  />
                  <input
                      v-model="organizationForm.secondary_color"
                      type="text"
                      class="form-control color-text"
                  />
                </div>
              </div>
            </div>

            <div class="form-group">
              <label for="org-logo">Organization Logo</label>
              <div class="file-upload">
                <input
                    type="file"
                    id="org-logo"
                    ref="logoInput"
                    @change="handleLogoUpload"
                    accept="image/*"
                    class="file-input"
                />
                <button type="button" @click="triggerLogoUpload" class="btn-secondary">
                  <i class="fas fa-upload"></i> Choose File
                </button>
                <span v-if="logoFile" class="file-name">{{ logoFile.name }}</span>
                <span v-else-if="organizationForm.logo_path" class="file-name">Current logo</span>
                <span v-else class="file-name placeholder">No file chosen</span>
              </div>
              <div v-if="logoPreview" class="logo-preview">
                <img :src="logoPreview" alt="Logo preview" />
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-primary" :disabled="saving">
                <span v-if="saving">
                  <i class="fas fa-spinner fa-spin"></i> Saving...
                </span>
                <span v-else>Save Changes</span>
              </button>
            </div>
          </form>

          <div class="setting-section danger-zone">
            <h2>Danger Zone</h2>
            <p class="warning-text">The following actions are destructive and cannot be reversed.</p>

            <div class="danger-actions">
              <button @click="showDeleteOrganizationConfirmation" class="btn-danger">
                <i class="fas fa-trash"></i> Delete Organization
              </button>
            </div>
          </div>
        </div>

        <!-- User Management Tab -->
        <div v-if="activeTab === 'users'" class="users-management">
          <div class="tab-actions">
            <h2>Users & Permissions</h2>
            <button @click="showInviteUserModal = true" class="btn-primary">
              <i class="fas fa-user-plus"></i> Invite User
            </button>
          </div>

          <div class="users-table-wrapper">
            <table class="users-table">
              <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Admin</th>
                <th>Actions</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="user in users" :key="user.id">
                <td>{{ user.name }}</td>
                <td>{{ user.email }}</td>
                <td>{{ user.role ? user.role.name : 'No Role' }}</td>
                <td>
                    <span v-if="user.pivot && user.pivot.is_admin" class="admin-badge">
                      <i class="fas fa-check-circle"></i> Admin
                    </span>
                  <span v-else>-</span>
                </td>
                <td class="actions">
                  <button @click="editUser(user)" class="btn-action">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="confirmRemoveUser(user)" class="btn-action" :disabled="user.pivot && user.pivot.is_admin && currentUser.id === user.id">
                    <i class="fas fa-user-minus"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="users.length === 0">
                <td colspan="5" class="empty-state">No users found</td>
              </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Departments Tab -->
        <div v-if="activeTab === 'departments'" class="departments-settings">
          <div class="tab-actions">
            <h2>Departments</h2>
            <button @click="showAddDepartmentModal = true" class="btn-primary">
              <i class="fas fa-plus"></i> Add Department
            </button>
          </div>

          <div class="departments-grid">
            <div
                v-for="department in departments"
                :key="department.id"
                class="department-card"
            >
              <div class="card-header" :style="{ backgroundColor: department.color || '#4caf50' }">
                <h3>{{ department.name }}</h3>
                <div class="card-actions">
                  <button @click="editDepartment(department)" class="btn-action">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button @click="confirmDeleteDepartment(department)" class="btn-action">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
              <div class="card-content">
                <p v-if="department.code"><strong>Code:</strong> {{ department.code }}</p>
                <p v-if="department.description"><strong>Description:</strong> {{ department.description }}</p>
                <p><strong>Positions:</strong> {{ getDepartmentPositionCount(department) }}</p>
              </div>
            </div>
            <div v-if="departments.length === 0" class="empty-state-card">
              <i class="fas fa-building"></i>
              <p>No departments yet</p>
              <button @click="showAddDepartmentModal = true" class="btn-primary">Add Department</button>
            </div>
          </div>
        </div>

        <!-- Account and Security Tab -->
        <div v-if="activeTab === 'security'" class="security-settings">
          <h2>Account & Security</h2>

          <div class="setting-section">
            <h3>Two-Factor Authentication</h3>
            <p class="section-description">
              Enable two-factor authentication to add an extra layer of security to your account.
            </p>

            <div class="toggle-setting">
              <span class="setting-label">Two-Factor Authentication</span>
              <label class="toggle">
                <input type="checkbox" v-model="twoFactorEnabled" @change="toggleTwoFactor">
                <span class="toggle-slider"></span>
              </label>
              <span class="setting-status">{{ twoFactorEnabled ? 'Enabled' : 'Disabled' }}</span>
            </div>
          </div>

          <div class="setting-section">
            <h3>API Tokens</h3>
            <p class="section-description">
              Create and manage API tokens to interact with our API from external applications.
            </p>

            <button @click="showCreateTokenModal = true" class="btn-secondary">
              <i class="fas fa-key"></i> Create New Token
            </button>

            <div v-if="apiTokens.length > 0" class="api-tokens-list">
              <div v-for="token in apiTokens" :key="token.id" class="token-item">
                <div class="token-details">
                  <div class="token-name">{{ token.name }}</div>
                  <div class="token-created">Created: {{ formatDate(token.created_at) }}</div>
                </div>
                <button @click="revokeToken(token)" class="btn-danger-outline">
                  <i class="fas fa-trash"></i> Revoke
                </button>
              </div>
            </div>
            <div v-else class="empty-state">
              <p>No API tokens created</p>
            </div>
          </div>
        </div>

        <!-- Notifications Tab -->
        <div v-if="activeTab === 'notifications'" class="notification-settings">
          <h2>Notification Preferences</h2>

          <div class="notification-options">
            <div class="notification-group">
              <h3>Email Notifications</h3>

              <div v-for="(option, index) in emailNotifications" :key="'email-'+index" class="notification-option">
                <div class="option-details">
                  <div class="option-name">{{ option.name }}</div>
                  <div class="option-description">{{ option.description }}</div>
                </div>
                <label class="toggle">
                  <input type="checkbox" v-model="option.enabled" @change="updateNotificationPreferences">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>

            <div class="notification-group">
              <h3>In-App Notifications</h3>

              <div v-for="(option, index) in appNotifications" :key="'app-'+index" class="notification-option">
                <div class="option-details">
                  <div class="option-name">{{ option.name }}</div>
                  <div class="option-description">{{ option.description }}</div>
                </div>
                <label class="toggle">
                  <input type="checkbox" v-model="option.enabled" @change="updateNotificationPreferences">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Invite User Modal -->
    <div v-if="showInviteUserModal" class="modal-backdrop" @click.self="showInviteUserModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Invite User</h3>
          <button @click="showInviteUserModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="inviteUser">
            <div class="form-group">
              <label for="invite-email">Email Address</label>
              <input id="invite-email" v-model="inviteForm.email" type="email" required class="form-control" />
            </div>

            <div class="form-group">
              <label for="invite-role">Role</label>
              <select id="invite-role" v-model="inviteForm.role_id" class="form-control">
                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
              </select>
            </div>

            <div class="form-group checkbox-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="inviteForm.is_admin">
                Make organization admin
              </label>
              <p class="help-text">Organization admins can manage users, settings, and all data.</p>
            </div>

            <div class="form-group">
              <label for="invite-message">Personal Message (Optional)</label>
              <textarea id="invite-message" v-model="inviteForm.message" rows="3" class="form-control"></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button @click="inviteUser" class="btn-primary" :disabled="!inviteForm.email">
            Send Invitation
          </button>
          <button @click="showInviteUserModal = false" class="btn-secondary">
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Department Modal -->
    <div v-if="showDepartmentModal" class="modal-backdrop" @click.self="showDepartmentModal = false">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingDepartment ? 'Edit Department' : 'Add Department' }}</h3>
          <button @click="showDepartmentModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveDepartment">
            <div class="form-group">
              <label for="dept-name">Department Name</label>
              <input id="dept-name" v-model="departmentForm.name" type="text" required class="form-control" />
            </div>

            <div class="form-group">
              <label for="dept-code">Department Code</label>
              <input id="dept-code" v-model="departmentForm.code" type="text" class="form-control" />
              <p class="help-text">Optional code for integrations or reporting</p>
            </div>

            <div class="form-group">
              <label for="dept-description">Description</label>
              <textarea id="dept-description" v-model="departmentForm.description" rows="3" class="form-control"></textarea>
            </div>

            <div class="form-group">
              <label for="dept-color">Color</label>
              <div class="color-picker-group">
                <input id="dept-color" v-model="departmentForm.color" type="color" class="color-picker" />
                <input v-model="departmentForm.color" type="text" class="form-control color-text" />
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button @click="saveDepartment" class="btn-primary" :disabled="!departmentForm.name">
            {{ editingDepartment ? 'Update Department' : 'Add Department' }}
          </button>
          <button @click="showDepartmentModal = false" class="btn-secondary">
            Cancel
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteConfirmation" class="modal-backdrop">
      <div class="modal-content modal-sm">
        <div class="modal-header">
          <h3>{{ deleteConfirmType === 'organization' ? 'Delete Organization' : 'Delete Department' }}</h3>
          <button @click="showDeleteConfirmation = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="warning-icon">
            <i class="fas fa-exclamation-triangle"></i>
          </div>

          <p class="warning-message">
            Are you sure you want to delete
            <strong>{{ deleteConfirmName }}</strong>?
          </p>

          <p class="warning-details">
            {{ deleteConfirmType === 'organization'
              ? 'This will permanently delete all data associated with this organization including scenarios, positions, and departments.'
              : 'This will permanently delete this department and unassign all positions currently in this department.'
            }}
          </p>

          <div v-if="deleteConfirmType === 'organization'" class="confirm-input">
            <label for="confirm-text">Type the organization name to confirm:</label>
            <input
                id="confirm-text"
                v-model="deleteConfirmInput"
                type="text"
                class="form-control"
                placeholder="Type name to confirm"
            />
          </div>
        </div>
        <div class="modal-footer">
          <button
              @click="confirmDelete"
              class="btn-danger"
              :disabled="deleteConfirmType === 'organization' && deleteConfirmInput !== deleteConfirmName"
          >
            Delete {{ deleteConfirmType === 'organization' ? 'Organization' : 'Department' }}
          </button>
          <button @click="showDeleteConfirmation = false" class="btn-secondary">
            Cancel
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  props: {
    id: {
      type: [Number, String],
      required: true
    }
  },

  data() {
    return {
      organization: {},
      departments: [],
      users: [],
      currentUser: {},
      roles: [],
      positions: [],

      activeTab: 'general',
      tabs: [
        {id: 'general', name: 'General', icon: 'fas fa-cog'},
        {id: 'users', name: 'Users & Permissions', icon: 'fas fa-users'},
        {id: 'departments', name: 'Departments', icon: 'fas fa-building'},
        {id: 'security', name: 'Security', icon: 'fas fa-shield-alt'},
        {id: 'notifications', name: 'Notifications', icon: 'fas fa-bell'}
      ],

      loading: true,
      error: null,
      saving: false,

      // Organization form
      organizationForm: {
        name: '',
        description: '',
        primary_color: '#4caf50',
        secondary_color: '#2196f3',
        logo_path: null
      },
      logoFile: null,
      logoPreview: null,

      // Invite user form
      showInviteUserModal: false,
      inviteForm: {
        email: '',
        role_id: null,
        is_admin: false,
        message: ''
      },

      // Department modal
      showDepartmentModal: false,
      editingDepartment: null,
      departmentForm: {
        name: '',
        code: '',
        description: '',
        color: '#4caf50'
      },

      // Delete confirmation
      showDeleteConfirmation: false,
      deleteConfirmType: '',
      deleteConfirmId: null,
      deleteConfirmName: '',
      deleteConfirmInput: '',

      // Security settings
      twoFactorEnabled: false,
      apiTokens: [],
      showCreateTokenModal: false,

      // Notification settings
      emailNotifications: [
        {name: 'Organization Updates', description: 'Receive updates about organization changes', enabled: true},
        {name: 'New Users', description: 'Be notified when new users join the organization', enabled: true},
        {
          name: 'Scenario Changes',
          description: 'Get notifications when scenarios are created or updated',
          enabled: true
        }
      ],
      appNotifications: [
        {name: 'Organization Activity', description: 'See activity notifications in-app', enabled: true},
        {name: 'Position Changes', description: 'Get notified about position changes', enabled: true},
        {name: 'Department Updates', description: 'Receive notifications about department changes', enabled: false}
      ]
    };
  },

  created() {
    this.fetchOrganizationData();
    this.fetchCurrentUser();
  },

  methods: {
    async fetchOrganizationData() {
      this.loading = true;
      this.error = null;

      try {
        // Fetch organization
        const orgResponse = await axios.get(`/api/organizations/${this.id}`);
        this.organization = orgResponse.data;

        // Copy to form data
        this.organizationForm = {
          name: this.organization.name,
          description: this.organization.description || '',
          primary_color: this.organization.primary_color || '#4caf50',
          secondary_color: this.organization.secondary_color || '#2196f3',
          logo_path: this.organization.logo_path
        };

        if (this.organization.logo_path) {
          this.logoPreview = this.getLogoUrl(this.organization.logo_path);
        }

        // Fetch departments
        const deptResponse = await axios.get(`/api/organizations/${this.id}/departments`);
        this.departments = deptResponse.data;

        // Fetch organization users
        const usersResponse = await axios.get(`/api/organizations/${this.id}/users`);
        this.users = usersResponse.data;

        // Fetch positions
        const positionsResponse = await axios.get(`/api/organizations/${this.id}/positions`);
        this.positions = positionsResponse.data;

        // Fetch roles
        const rolesResponse = await axios.get(`/api/roles`);
        this.roles = rolesResponse.data;

        // Fetch API tokens
        const tokensResponse = await axios.get(`/api/user/tokens`);
        this.apiTokens = tokensResponse.data || [];

      } catch (error) {
        console.error('Error fetching organization data:', error);
        this.error = 'Failed to load organization data. Please try again.';
      } finally {
        this.loading = false;
      }
    },

    async fetchCurrentUser() {
      try {
        const response = await axios.get('/api/user');
        this.currentUser = response.data;
      } catch (error) {
        console.error('Error fetching current user:', error);
      }
    },

    /*** Organization Update ***/
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

    async updateOrganizationDetails() {
      this.saving = true;

      try {
        const formData = new FormData();
        formData.append('name', this.organizationForm.name);
        formData.append('description', this.organizationForm.description);
        formData.append('primary_color', this.organizationForm.primary_color);
        formData.append('secondary_color', this.organizationForm.secondary_color);

        if (this.logoFile) {
          formData.append('logo', this.logoFile);
        }

        // Use FormData with multipart/form-data for file upload
        await axios.post(`/api/organizations/${this.id}?_method=PUT`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        // Refresh data
        await this.fetchOrganizationData();

        this.$toaster.success('Organization details saved successfully');
      } catch (error) {
        console.error('Error updating organization:', error);
        this.$toaster.error('Failed to save organization details');
      } finally {
        this.saving = false;
      }
    },

    /*** User Management ***/
    async inviteUser() {
      if (!this.inviteForm.email) return;

      try {
        await axios.post(`/api/organizations/${this.id}/users/invite`, this.inviteForm);

        // Refresh users list
        const usersResponse = await axios.get(`/api/organizations/${this.id}/users`);
        this.users = usersResponse.data;

        this.showInviteUserModal = false;
        this.inviteForm = {
          email: '',
          role_id: null,
          is_admin: false,
          message: ''
        };

        this.$toaster.success('Invitation sent successfully');
      } catch (error) {
        console.error('Error inviting user:', error);
        this.$toaster.error('Failed to send invitation');
      }
    },

    editUser(user) {
      // Implementation would depend on your user management system
      alert('Edit user functionality would go here');
    },

    async confirmRemoveUser(user) {
      if (confirm(`Are you sure you want to remove ${user.name} from this organization?`)) {
        try {
          await axios.delete(`/api/organizations/${this.id}/users/${user.id}`);

          // Remove from local list
          this.users = this.users.filter(u => u.id !== user.id);

          this.$toaster.success('User removed successfully');
        } catch (error) {
          console.error('Error removing user:', error);
          this.$toaster.error('Failed to remove user');
        }
      }
    },

    /*** Department Management ***/
    addDepartment() {
      this.editingDepartment = null;
      this.departmentForm = {
        name: '',
        code: '',
        description: '',
        color: '#4caf50'
      };
      this.showDepartmentModal = true;
    },

    editDepartment(department) {
      this.editingDepartment = department;
      this.departmentForm = {
        name: department.name,
        code: department.code || '',
        description: department.description || '',
        color: department.color || '#4caf50'
      };
      this.showDepartmentModal = true;
    },

    async saveDepartment() {
      try {
        if (this.editingDepartment) {
          // Update existing department
          await axios.put(`/api/organizations/${this.id}/departments/${this.editingDepartment.id}`, this.departmentForm);

          // Update local data
          const index = this.departments.findIndex(d => d.id === this.editingDepartment.id);
          if (index !== -1) {
            this.departments.splice(index, 1, {
              ...this.editingDepartment,
              ...this.departmentForm
            });
          }

          this.$toaster.success('Department updated successfully');
        } else {
          // Create new department
          const response = await axios.post(`/api/organizations/${this.id}/departments`, this.departmentForm);

          // Add to local data
          this.departments.push(response.data);

          this.$toaster.success('Department created successfully');
        }

        this.showDepartmentModal = false;
      } catch (error) {
        console.error('Error saving department:', error);
        this.$toaster.error('Failed to save department');
      }
    },

    confirmDeleteDepartment(department) {
      this.deleteConfirmType = 'department';
      this.deleteConfirmId = department.id;
      this.deleteConfirmName = department.name;
      this.showDeleteConfirmation = true;
    },

    /*** Delete Organization ***/
    showDeleteOrganizationConfirmation() {
      this.deleteConfirmType = 'organization';
      this.deleteConfirmId = this.organization.id;
      this.deleteConfirmName = this.organization.name;
      this.deleteConfirmInput = '';
      this.showDeleteConfirmation = true;
    },

    async confirmDelete() {
      try {
        if (this.deleteConfirmType === 'organization') {
          // Delete organization
          if (this.deleteConfirmInput !== this.deleteConfirmName) return;

          await axios.delete(`/api/organizations/${this.deleteConfirmId}`);

          // Redirect to dashboard
          this.$router.push('/dashboard');
          this.$toaster.success('Organization deleted successfully');
        } else if (this.deleteConfirmType === 'department') {
          // Delete department
          await axios.delete(`/api/organizations/${this.id}/departments/${this.deleteConfirmId}`);

          // Remove from local list
          this.departments = this.departments.filter(d => d.id !== this.deleteConfirmId);

          this.showDeleteConfirmation = false;
          this.$toaster.success('Department deleted successfully');
        }
      } catch (error) {
        console.error('Error deleting:', error);
        this.$toaster.error(`Failed to delete ${this.deleteConfirmType}`);
      }
    },

    /*** Security Management ***/
    async toggleTwoFactor() {
      try {
        await axios.post(`/api/user/two-factor-authentication`, {
          enabled: this.twoFactorEnabled
        });

        this.$toaster.success(`Two-factor authentication ${this.twoFactorEnabled ? 'enabled' : 'disabled'}`);
      } catch (error) {
        console.error('Error toggling two-factor auth:', error);
        this.twoFactorEnabled = !this.twoFactorEnabled; // Revert toggle
        this.$toaster.error('Failed to update two-factor authentication');
      }
    },

    async createApiToken() {
      if (!this.tokenForm || !this.tokenForm.name) return;

      try {
        const response = await axios.post(`/api/user/tokens`, this.tokenForm);

        // Show token to user (only shown once)
        alert(`Your new token is: ${response.data.token}\n\nPlease copy this now as it won't be shown again.`);

        // Refresh token list
        const tokensResponse = await axios.get(`/api/user/tokens`);
        this.apiTokens = tokensResponse.data || [];

        this.showCreateTokenModal = false;
        this.tokenForm = {
          name: '',
          abilities: ['read']
        };
      } catch (error) {
        console.error('Error creating token:', error);
        this.$toaster.error('Failed to create API token');
      }
    },

    async revokeToken(token) {
      if (confirm(`Are you sure you want to revoke the token "${token.name}"?`)) {
        try {
          await axios.delete(`/api/user/tokens/${token.id}`);

          // Remove from local list
          this.apiTokens = this.apiTokens.filter(t => t.id !== token.id);

          this.$toaster.success('Token revoked successfully');
        } catch (error) {
          console.error('Error revoking token:', error);
          this.$toaster.error('Failed to revoke token');
        }
      }
    },

    /*** Notification Settings ***/
    async updateNotificationPreferences() {
      try {
        const preferences = {
          email: this.emailNotifications.reduce((obj, pref) => {
            obj[pref.name.toLowerCase().replace(/\s+/g, '_')] = pref.enabled;
            return obj;
          }, {}),
          app: this.appNotifications.reduce((obj, pref) => {
            obj[pref.name.toLowerCase().replace(/\s+/g, '_')] = pref.enabled;
            return obj;
          }, {})
        };

        await axios.post(`/api/user/notification-preferences`, preferences);

        this.$toaster.success('Notification preferences updated');
      } catch (error) {
        console.error('Error updating notification preferences:', error);
        this.$toaster.error('Failed to update notification preferences');
      }
    },

    /*** Helper Methods ***/
    getLogoUrl(path) {
      if (!path) return null;
      if (path.startsWith('http')) return path;
      return `/storage/${path}`;
    },

    getDepartmentPositionCount(department) {
      return this.positions.filter(p => p.department_id === department.id).length;
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const date = new Date(dateStr);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    }
  }
}
</script>
<style scoped>
.settings-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.settings-header {
  margin-bottom: 2rem;
}

.settings-header h1 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  color: var(--primary-color, #4caf50);
}

.settings-header .subtitle {
  color: var(--text-secondary, #666);
  font-size: 1.1rem;
}

.settings-content {
  display: flex;
  flex: 1;
}

.tab-navigation {
  width: 250px;
  margin-right: 2rem;
  border-right: 1px solid var(--border-color, #ddd);
  padding-right: 1rem;
  flex-shrink: 0;
}

.tab-button {
  display: flex;
  align-items: center;
  width: 100%;
  text-align: left;
  padding: 0.75rem 1rem;
  margin-bottom: 0.5rem;
  border: none;
  background-color: transparent;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: background-color 0.2s;
  color: var(--text-color, #333);
  font-weight: 500;
}

.tab-button:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.tab-button.active {
  background-color: var(--primary-light, #e8f5e9);
  color: var(--primary-color, #4caf50);
}

.tab-button i {
  margin-right: 0.75rem;
  width: 20px;
  text-align: center;
}

.tab-content {
  flex: 1;
  padding: 0 1rem;
}

/* Form styling */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 0.25rem;
  font-size: 1rem;
}

.form-control:focus {
  outline: none;
  border-color: var(--primary-color, #4caf50);
  box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.form-row {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-row .form-group {
  flex: 1;
  margin-bottom: 0;
}

.color-picker-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.color-picker {
  width: 50px;
  height: 40px;
  padding: 0;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 0.25rem;
  cursor: pointer;
}

.color-text {
  flex: 1;
}

.form-actions {
  margin-top: 2rem;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background-color: var(--primary-color, #4caf50);
  color: white;
  border: none;
  border-radius: 0.25rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: var(--primary-dark, #388e3c);
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background-color: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
}

.btn-danger {
  padding: 0.75rem 1.5rem;
  background-color: #f44336;
  color: white;
  border: none;
  border-radius: 0.25rem;
  font-size: 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-danger:hover {
  background-color: #d32f2f;
}

.btn-danger-outline {
  padding: 0.5rem 1rem;
  background-color: white;
  color: #f44336;
  border: 1px solid #f44336;
  border-radius: 0.25rem;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger-outline:hover {
  background-color: #ffebee;
}

.btn-retry {
  padding: 0.5rem 1rem;
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
  cursor: pointer;
  margin-top: 1rem;
}

.setting-section {
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--border-color, #eee);
}

.setting-section h2 {
  margin-bottom: 1.5rem;
  color: var(--text-color, #333);
  font-size: 1.5rem;
}

.setting-section h3 {
  margin-bottom: 1rem;
  color: var(--text-color, #333);
  font-size: 1.2rem;
}

.section-description {
  margin-bottom: 1.5rem;
  color: var(--text-secondary, #666);
}

/* Danger zone */
.danger-zone {
  margin-top: 3rem;
  padding: 1.5rem;
  border: 1px solid #ffcdd2;
  border-radius: 0.5rem;
  background-color: #ffebee;
}

.danger-zone h2 {
  color: #d32f2f;
}

.warning-text {
  color: #d32f2f;
  margin-bottom: 1.5rem;
}

/* Users table */
.users-table-wrapper {
  overflow-x: auto;
  margin-bottom: 2rem;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border-color, #eee);
}

.users-table th {
  font-weight: 600;
  background-color: #f5f5f5;
}

.admin-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.5rem;
  background-color: #fff8e1;
  color: #ff8f00;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.admin-badge i {
  margin-right: 0.25rem;
}

.tab-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.tab-actions h2 {
  margin: 0;
}

.btn-action {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.btn-action:hover {
  background-color: #f5f5f5;
  color: #333;
}

/* Departments */
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
}

.card-header {
  padding: 1rem;
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-content {
  padding: 1rem;
}

.card-content p {
  margin-bottom: 0.75rem;
}

.card-content p:last-child {
  margin-bottom: 0;
}

.empty-state-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background-color: #f9f9f9;
  border-radius: 0.5rem;
  text-align: center;
}

.empty-state-card i {
  font-size: 3rem;
  color: #ccc;
  margin-bottom: 1rem;
}

.empty-state-card p {
  margin-bottom: 1.5rem;
  color: #666;
}

/* Toggle switch */
.toggle-setting {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
}

.setting-label {
  flex: 1;
  font-weight: 500;
}

.setting-status {
  margin-left: 1rem;
  font-size: 0.9rem;
  color: #666;
}

.toggle {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.toggle input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background-color: var(--primary-color, #4caf50);
}

input:focus + .toggle-slider {
  box-shadow: 0 0 1px var(--primary-color, #4caf50);
}

input:checked + .toggle-slider:before {
  transform: translateX(26px);
}

/* API Tokens */
.api-tokens-list {
  margin-top: 1.5rem;
}

.token-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border: 1px solid var(--border-color, #eee);
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.token-name {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.token-created {
  font-size: 0.875rem;
  color: #666;
}

/* Notification options */
.notification-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
}

.notification-group h3 {
  margin-bottom: 1.5rem;
}

.notification-option {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border: 1px solid var(--border-color, #eee);
  border-radius: 0.5rem;
  margin-bottom: 1rem;
}

.option-name {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.option-description {
  font-size: 0.875rem;
  color: #666;
}

/* Modal styles */
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
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  width: 90%;
  max-width: 600px;
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
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color, #eee);
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
  color: #666;
}

.modal-body {
  padding: 1.5rem;
  flex: 1;
  overflow-y: auto;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color, #eee);
}

.warning-icon {
  text-align: center;
  font-size: 3rem;
  color: #f44336;
  margin-bottom: 1.5rem;
}

.warning-message {
  font-weight: 500;
  margin-bottom: 1rem;
}

.warning-details {
  margin-bottom: 1.5rem;
}

.confirm-input {
  margin-top: 1.5rem;
}

.checkbox-group {
  display: flex;
  align-items: flex-start;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
}

.checkbox-label input {
  margin-right: 0.5rem;
  margin-top: 0.25rem;
}

.help-text {
  font-size: 0.875rem;
  color: #666;
  margin-top: 0.5rem;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 0;
}

.spinner {
  font-size: 2rem;
  color: var(--primary-color, #4caf50);
  margin-bottom: 1rem;
}

.error-message {
  text-align: center;
  padding: 2rem;
  max-width: 600px;
  margin: 0 auto;
}

.error-message i {
  font-size: 3rem;
  color: #f44336;
  margin-bottom: 1rem;
}

/* Media queries */
@media (max-width: 768px) {
  .settings-content {
    flex-direction: column;
  }

  .tab-navigation {
    width: 100%;
    margin-right: 0;
    margin-bottom: 2rem;
    padding-right: 0;
    border-right: none;
    border-bottom: 1px solid var(--border-color, #ddd);
    padding-bottom: 1rem;
    display: flex;
    overflow-x: auto;
  }

  .tab-button {
    flex-shrink: 0;
    width: auto;
    margin-right: 0.5rem;
  }

  .form-row {
    flex-direction: column;
    gap: 1rem;
  }

  .notification-options {
    grid-template-columns: 1fr;
  }
}
</style>