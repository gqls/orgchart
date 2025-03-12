// resources/js/components/OrganizationCreate.vue
<template>
  <div class="create-org-container">
    <div class="create-org-header">
      <h1>Create New Organization</h1>
      <p>Set up your organization to start building and analyzing org charts.</p>
    </div>

    <div class="create-org-card">
      <form @submit.prevent="createOrganization" class="org-form">
        <div class="form-section">
          <h2>Basic Information</h2>

          <div class="form-group">
            <label for="org-name">Organization Name *</label>
            <input
                id="org-name"
                v-model="form.name"
                type="text"
                placeholder="Enter organization name"
                required
                class="form-control"
            />
            <div v-if="errors.name" class="error-message">{{ errors.name[0] }}</div>
          </div>

          <div class="form-group">
            <label for="org-description">Description</label>
            <textarea
                id="org-description"
                v-model="form.description"
                placeholder="Brief description of your organization"
                rows="3"
                class="form-control"
            ></textarea>
            <div v-if="errors.description" class="error-message">{{ errors.description[0] }}</div>
          </div>
        </div>

        <div class="form-section">
          <h2>Branding</h2>

          <div class="form-group">
            <label for="org-logo">Logo (Optional)</label>
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
              <span v-else class="file-name placeholder">No file chosen</span>
            </div>
            <div v-if="logoPreview" class="logo-preview">
              <img :src="logoPreview" alt="Logo preview"/>
            </div>
            <div v-if="errors.logo" class="error-message">{{ errors.logo[0] }}</div>
          </div>

          <div class="color-group">
            <div class="form-group">
              <label for="primary-color">Primary Color</label>
              <div class="color-input">
                <input
                    id="primary-color"
                    v-model="form.primary_color"
                    type="color"
                    class="color-picker"
                />
                <input
                    v-model="form.primary_color"
                    type="text"
                    class="form-control color-text"
                />
              </div>
              <div v-if="errors.primary_color" class="error-message">{{ errors.primary_color[0] }}</div>
            </div>

            <div class="form-group">
              <label for="secondary-color">Secondary Color</label>
              <div class="color-input">
                <input
                    id="secondary-color"
                    v-model="form.secondary_color"
                    type="color"
                    class="color-picker"
                />
                <input
                    v-model="form.secondary_color"
                    type="text"
                    class="form-control color-text"
                />
              </div>
              <div v-if="errors.secondary_color" class="error-message">{{
                  errors.secondary_color[0]
                }}
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-primary">Create Organization</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      form: {
        name: '',
        description: '',
        logo: null,
        primary_color: '#007bff',
        secondary_color: '#6c757d',
      },
      errors: {},
      logoFile: null,
      logoPreview: null,
    };
  },
  methods: {
    triggerLogoUpload() {
      this.$refs.logoInput.click();
    },
    handleLogoUpload(event) {
      this.logoFile = event.target.files[0];
      if (this.logoFile) {
        this.form.logo = this.logoFile;
        const reader = new FileReader();
        reader.onload = (e) => {
          this.logoPreview = e.target.result;
        };
        reader.readAsDataURL(this.logoFile);
      } else {
        this.logoPreview = null;
      }
    },
    createOrganization() {
      const formData = new FormData();
      formData.append('name', this.form.name);
      formData.append('description', this.form.description);
      formData.append('primary_color', this.form.primary_color);
      formData.append('secondary_color', this.form.secondary_color);
      if (this.form.logo) {
        formData.append('logo', this.form.logo);
      }

      axios.post('/api/organizations', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
          .then(() => {
            this.$router.push('/organizations');
          })
          .catch(error => {
            if (error.response && error.response.data.errors) {
              this.errors = error.response.data.errors;
            } else {
              console.error('Error creating organization:', error);
            }
          });
    },
  },
};
</script>

<style scoped>
.create-org-container {
  padding: 20px;
}

.create-org-header {
  text-align: center;
  margin-bottom: 30px;
}

.create-org-card {
  max-width: 600px;
  margin: 0 auto;
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.org-form {
  display: flex;
  flex-direction: column;
}

.form-section {
  margin-bottom: 20px;
}

.form-group {
  margin-bottom: 15px;
}

label {
  display: block;
  margin-bottom: 5px;
}

.form-control {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}

textarea.form-control {
  resize: vertical;
}

.form-actions {
  text-align: right;
}

.btn-primary {
  background-color: #007bff;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-secondary {
  background-color: #e0e0e0;
  color: #333;
  padding: 8px 15px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.error-message {
  color: red;
  font-size: 0.8em;
  margin-top: 5px;
}

.file-upload {
  position: relative;
  overflow: hidden;
  display: inline-block;
}

.file-input {
  position: absolute;
  font-size: 100px;
  position: absolute;
  left: 0;
  top: 0;
  opacity: 0;
}

.file-name {
  margin-left: 10px;
}

.file-name.placeholder {
  color: #999;
}

.logo-preview {
  margin-top: 10px;
}

.logo-preview img {
  max-width: 150px;
  max-height: 150px;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.color-group {
  display: flex;
  justify-content: space-between;
}

.color-group .form-group {
  width: 48%;
}

.color-input {
  display: flex;
  align-items: center;
}

.color-picker {
  width: 50px;
  height: 38px;
  border: 1px solid #ccc;
  border-radius: 4px;
  margin-right: 10px;
  padding: 2px;
}

.color-text {
  flex-grow: 1;
}
</style>