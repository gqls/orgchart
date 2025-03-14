// resources/js/components/ErrorDisplay.vue

<template>
  <div class="error-container" v-if="message">
    <div class="error-message">
      <i class="fas fa-exclamation-triangle"></i>
      <h3>{{ title }}</h3>
      <p>{{ message }}</p>
      <div class="error-details" v-if="details && showDetails">
        <pre>{{ details }}</pre>
      </div>
      <div class="error-actions">
        <button v-if="details" @click="toggleDetails" class="btn-secondary">
          {{ showDetails ? 'Hide Details' : 'Show Details' }}
        </button>
        <button @click="retry" class="btn-primary" v-if="retryAction">
          Retry
        </button>
        <button @click="dismiss" class="btn-secondary">
          Dismiss
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    message: {
      type: String,
      default: ''
    },
    title: {
      type: String,
      default: 'Error'
    },
    details: {
      type: String,
      default: ''
    },
    retryAction: {
      type: Function,
      default: null
    }
  },

  data() {
    return {
      showDetails: false
    };
  },

  methods: {
    toggleDetails() {
      this.showDetails = !this.showDetails;
    },

    retry() {
      if (this.retryAction && typeof this.retryAction === 'function') {
        this.retryAction();
      }
      this.$emit('retry');
    },

    dismiss() {
      this.$emit('dismiss');
    }
  }
};
</script>

<style scoped>
.error-container {
  background-color: rgba(255, 235, 235, 0.9);
  border-radius: 6px;
  padding: 1rem;
  margin: 1rem 0;
  border: 1px solid #ffcdd2;
}

.error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  color: #d32f2f;
  text-align: center;
}

.error-message i {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.error-message h3 {
  margin: 0.5rem 0;
  color: #d32f2f;
}

.error-message p {
  margin-bottom: 1rem;
  color: #333;
}

.error-details {
  background-color: rgba(0, 0, 0, 0.05);
  padding: 1rem;
  border-radius: 4px;
  text-align: left;
  overflow-x: auto;
  max-width: 100%;
  margin-bottom: 1rem;
}

.error-details pre {
  white-space: pre-wrap;
  word-break: break-word;
  font-size: 0.875rem;
  color: #555;
}

.error-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.btn-primary, .btn-secondary {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: background-color 0.2s;
}

.btn-primary {
  background-color: #d32f2f;
  color: white;
}

.btn-primary:hover {
  background-color: #b71c1c;
}

.btn-secondary {
  background-color: #f5f5f5;
  border: 1px solid #ddd;
  color: #333;
}

.btn-secondary:hover {
  background-color: #e0e0e0;
}
</style>