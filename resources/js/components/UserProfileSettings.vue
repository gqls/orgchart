// Create a new component: UserProfileSettings.vue
<template>
  <div class="profile-settings">
    <div class="settings-header">
      <h1>My Profile</h1>
      <p class="subtitle">Manage your personal settings and preferences</p>
    </div>

    <div class="settings-card">
      <div class="profile-section">
        <h2>Personal Information</h2>
        <form @submit.prevent="updateProfile">
          <div class="form-group">
            <label for="display-name">Display Name</label>
            <input id="display-name" v-model="profileForm.name" type="text" class="form-control">
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input id="email" v-model="profileForm.email" type="email" class="form-control" disabled>
            <p class="help-text">Email address cannot be changed</p>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">Update Profile</button>
          </div>
        </form>
      </div>

      <div class="password-section">
        <h2>Change Password</h2>
        <form @submit.prevent="updatePassword">
          <div class="form-group">
            <label for="current-password">Current Password</label>
            <input id="current-password" v-model="passwordForm.current_password" type="password" class="form-control">
          </div>

          <div class="form-group">
            <label for="new-password">New Password</label>
            <input id="new-password" v-model="passwordForm.password" type="password" class="form-control">
          </div>

          <div class="form-group">
            <label for="confirm-password">Confirm New Password</label>
            <input id="confirm-password" v-model="passwordForm.password_confirmation" type="password" class="form-control">
          </div>

          <div class="form-actions">
            <button type="submit" class="btn-primary">Change Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>