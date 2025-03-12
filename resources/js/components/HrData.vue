// resources/js/components/HrData.vue
<template>
  <div class="hr-data">
    <div class="section-header">
      <h2 class="text-xl font-semibold mb-4">HR Data Management</h2>
      <div class="action-buttons">
        <button @click="showImportModal = true" class="btn-primary">
          <i class="fas fa-file-import mr-2"></i> Import Data
        </button>
        <button @click="showExportModal = true" class="btn-secondary">
          <i class="fas fa-file-export mr-2"></i> Export Data
        </button>
      </div>
    </div>

    <div class="data-tabs">
      <div
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="['tab', { active: activeTab === tab.id }]"
      >
        {{ tab.name }}
      </div>
    </div>

    <!-- Employees Tab -->
    <div v-if="activeTab === 'employees'" class="data-section">
      <div class="filter-controls">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input
              type="text"
              v-model="employeeSearch"
              placeholder="Search employees..."
              class="search-input"
          />
        </div>
        <div class="filter-group">
          <label for="department-filter">Department:</label>
          <select id="department-filter" v-model="departmentFilter" class="form-control">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="status-filter">Status:</label>
          <select id="status-filter" v-model="statusFilter" class="form-control">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="on-leave">On Leave</option>
            <option value="terminated">Terminated</option>
          </select>
        </div>
      </div>

      <div class="data-grid">
        <table class="data-table">
          <thead>
          <tr>
            <th @click="sortEmployees('employee_id')">
              Employee ID
              <i :class="getSortIcon('employee_id')"></i>
            </th>
            <th @click="sortEmployees('name')">
              Name
              <i :class="getSortIcon('name')"></i>
            </th>
            <th @click="sortEmployees('position')">
              Position
              <i :class="getSortIcon('position')"></i>
            </th>
            <th @click="sortEmployees('department')">
              Department
              <i :class="getSortIcon('department')"></i>
            </th>
            <th @click="sortEmployees('status')">
              Status
              <i :class="getSortIcon('status')"></i>
            </th>
            <th @click="sortEmployees('hire_date')">
              Hire Date
              <i :class="getSortIcon('hire_date')"></i>
            </th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="employee in filteredEmployees" :key="employee.id">
            <td>{{ employee.employee_id }}</td>
            <td>{{ employee.name }}</td>
            <td>{{ employee.position }}</td>
            <td>{{ getDepartmentName(employee.department_id) }}</td>
            <td>
                <span class="status-badge" :class="employee.status">
                  {{ formatStatus(employee.status) }}
                </span>
            </td>
            <td>{{ formatDate(employee.hire_date) }}</td>
            <td class="actions">
              <button @click="editEmployee(employee)" class="btn-icon">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="viewEmployeeDetails(employee)" class="btn-icon">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination" v-if="totalEmployeePages > 1">
        <button
            @click="currentEmployeePage--"
            :disabled="currentEmployeePage === 1"
            class="btn-secondary"
        >
          Previous
        </button>
        <span>Page {{ currentEmployeePage }} of {{ totalEmployeePages }}</span>
        <button
            @click="currentEmployeePage++"
            :disabled="currentEmployeePage === totalEmployeePages"
            class="btn-secondary"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Positions Tab -->
    <div v-if="activeTab === 'positions'" class="data-section">
      <div class="filter-controls">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input
              type="text"
              v-model="positionSearch"
              placeholder="Search positions..."
              class="search-input"
          />
        </div>
        <div class="filter-group">
          <label for="position-department-filter">Department:</label>
          <select id="position-department-filter" v-model="positionDepartmentFilter" class="form-control">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
          </select>
        </div>
        <div class="filter-group">
          <label for="grade-filter">Grade:</label>
          <select id="grade-filter" v-model="gradeFilter" class="form-control">
            <option value="">All Grades</option>
            <option v-for="grade in grades" :key="grade" :value="grade">{{ grade }}</option>
          </select>
        </div>
      </div>

      <div class="data-grid">
        <table class="data-table">
          <thead>
          <tr>
            <th @click="sortPositions('title')">
              Title
              <i :class="getSortIcon('title')"></i>
            </th>
            <th @click="sortPositions('department')">
              Department
              <i :class="getSortIcon('department')"></i>
            </th>
            <th @click="sortPositions('grade')">
              Grade
              <i :class="getSortIcon('grade')"></i>
            </th>
            <th @click="sortPositions('function')">
              Function
              <i :class="getSortIcon('function')"></i>
            </th>
            <th @click="sortPositions('fully_loaded_cost')">
              Cost
              <i :class="getSortIcon('fully_loaded_cost')"></i>
            </th>
            <th @click="sortPositions('status')">
              Status
              <i :class="getSortIcon('status')"></i>
            </th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="position in filteredPositions" :key="position.id">
            <td>{{ position.title }}</td>
            <td>{{ getDepartmentName(position.department_id) }}</td>
            <td>{{ position.grade || 'N/A' }}</td>
            <td>{{ position.function || 'N/A' }}</td>
            <td>{{ formatCurrency(position.fully_loaded_cost) }}</td>
            <td>
                <span class="status-badge" :class="getPositionStatusClass(position)">
                  {{ getPositionStatus(position) }}
                </span>
            </td>
            <td class="actions">
              <button @click="editPosition(position)" class="btn-icon">
                <i class="fas fa-edit"></i>
              </button>
              <button @click="viewPositionDetails(position)" class="btn-icon">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination" v-if="totalPositionPages > 1">
        <button
            @click="currentPositionPage--"
            :disabled="currentPositionPage === 1"
            class="btn-secondary"
        >
          Previous
        </button>
        <span>Page {{ currentPositionPage }} of {{ totalPositionPages }}</span>
        <button
            @click="currentPositionPage++"
            :disabled="currentPositionPage === totalPositionPages"
            class="btn-secondary"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Import Data Modal -->
    <div v-if="showImportModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Import HR Data</h3>
          <button @click="showImportModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="import-type">Data Type:</label>
            <select id="import-type" v-model="importType" class="form-control">
              <option value="employees">Employees</option>
              <option value="positions">Positions</option>
              <option value="both">Both</option>
            </select>
          </div>

          <div class="form-group">
            <label>File Upload:</label>
            <div class="file-upload">
              <input
                  type="file"
                  @change="handleFileUpload"
                  accept=".csv, .xlsx"
                  ref="fileInput"
                  class="file-input"
              />
              <button @click="triggerFileUpload" class="btn-secondary">
                <i class="fas fa-file-upload"></i> Select File
              </button>
              <span v-if="selectedFile" class="file-name">{{ selectedFile.name }}</span>
              <span v-else class="file-name placeholder">No file selected</span>
            </div>
          </div>

          <div class="form-group">
            <label>Import Options:</label>
            <div class="checkbox-group">
              <label>
                <input type="checkbox" v-model="importOptions.overwrite" />
                Overwrite existing records
              </label>
              <label>
                <input type="checkbox" v-model="importOptions.skipErrors" />
                Skip rows with errors
              </label>
              <label>
                <input type="checkbox" v-model="importOptions.validateOnly" />
                Validate only (no import)
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="startImport" class="btn-primary" :disabled="!selectedFile">Import Data</button>
          <button @click="showImportModal = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Export Data Modal -->
    <div v-if="showExportModal" class="modal-backdrop">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Export HR Data</h3>
          <button @click="showExportModal = false" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="export-type">Data Type:</label>
            <select id="export-type" v-model="exportType" class="form-control">
              <option value="employees">Employees</option>
              <option value="positions">Positions</option>
              <option value="both">Both (Separate Files)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="export-format">Format:</label>
            <select id="export-format" v-model="exportFormat" class="form-control">
              <option value="csv">CSV</option>
              <option value="xlsx">Excel (XLSX)</option>
              <option value="pdf">PDF</option>
            </select>
          </div>

          <div class="form-group">
            <label>Export Options:</label>
            <div class="checkbox-group">
              <label>
                <input type="checkbox" v-model="exportOptions.includeHeaders" />
                Include headers
              </label>
              <label>
                <input type="checkbox" v-model="exportOptions.currentFiltersOnly" />
                Apply current filters
              </label>
              <label>
                <input type="checkbox" v-model="exportOptions.includeMetadata" />
                Include metadata
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="startExport" class="btn-primary">Export Data</button>
          <button @click="showExportModal = false" class="btn-secondary">Cancel</button>
        </div>
      </div>
    </div>

    <!-- Employee Detail/Edit Modal -->
    <div v-if="selectedEmployee" class="modal-backdrop">
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Edit Employee' : 'Employee Details' }}</h3>
          <button @click="closeEmployeeModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form v-if="isEditing">
            <div class="form-row">
              <div class="form-group">
                <label for="employee-id">Employee ID:</label>
                <input id="employee-id" type="text" v-model="selectedEmployee.employee_id" class="form-control" />
              </div>
              <div class="form-group">
                <label for="employee-name">Name:</label>
                <input id="employee-name" type="text" v-model="selectedEmployee.name" class="form-control" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="employee-position">Position:</label>
                <input id="employee-position" type="text" v-model="selectedEmployee.position" class="form-control" />
              </div>
              <div class="form-group">
                <label for="employee-department">Department:</label>
                <select id="employee-department" v-model="selectedEmployee.department_id" class="form-control">
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="employee-status">Status:</label>
                <select id="employee-status" v-model="selectedEmployee.status" class="form-control">
                  <option value="active">Active</option>
                  <option value="on-leave">On Leave</option>
                  <option value="terminated">Terminated</option>
                </select>
              </div>
              <div class="form-group">
                <label for="employee-hire-date">Hire Date:</label>
                <input id="employee-hire-date" type="date" v-model="selectedEmployee.hire_date" class="form-control" />
              </div>
            </div>

            <div class="form-group">
              <label for="employee-notes">Notes:</label>
              <textarea id="employee-notes" v-model="selectedEmployee.notes" class="form-control" rows="3"></textarea>
            </div>
          </form>

          <div v-else class="employee-details">
            <div class="detail-section">
              <h4>Personal Information</h4>
              <div class="detail-row">
                <div class="detail-label">Employee ID:</div>
                <div class="detail-value">{{ selectedEmployee.employee_id }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value">{{ selectedEmployee.name }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                  <span class="status-badge" :class="selectedEmployee.status">
                    {{ formatStatus(selectedEmployee.status) }}
                  </span>
                </div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Hire Date:</div>
                <div class="detail-value">{{ formatDate(selectedEmployee.hire_date) }}</div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Position Information</h4>
              <div class="detail-row">
                <div class="detail-label">Position:</div>
                <div class="detail-value">{{ selectedEmployee.position }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value">{{ getDepartmentName(selectedEmployee.department_id) }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Tenure:</div>
                <div class="detail-value">{{ calculateTenure(selectedEmployee.hire_date) }}</div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Notes</h4>
              <p class="detail-notes">{{ selectedEmployee.notes || 'No notes available.' }}</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
              v-if="isEditing"
              @click="saveEmployee"
              class="btn-primary"
          >
            Save Changes
          </button>
          <button
              v-else
              @click="isEditing = true"
              class="btn-primary"
          >
            Edit
          </button>
          <button @click="closeEmployeeModal" class="btn-secondary">
            {{ isEditing ? 'Cancel' : 'Close' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Position Detail/Edit Modal -->
    <div v-if="selectedPosition" class="modal-backdrop">
      <div class="modal-content modal-lg">
        <div class="modal-header">
          <h3>{{ isEditing ? 'Edit Position' : 'Position Details' }}</h3>
          <button @click="closePositionModal" class="btn-close">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form v-if="isEditing">
            <div class="form-row">
              <div class="form-group">
                <label for="position-title">Title:</label>
                <input id="position-title" type="text" v-model="selectedPosition.title" class="form-control" />
              </div>
              <div class="form-group">
                <label for="position-department">Department:</label>
                <select id="position-department" v-model="selectedPosition.department_id" class="form-control">
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="position-grade">Grade:</label>
                <input id="position-grade" type="text" v-model="selectedPosition.grade" class="form-control" />
              </div>
              <div class="form-group">
                <label for="position-function">Function:</label>
                <input id="position-function" type="text" v-model="selectedPosition.function" class="form-control" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="position-cost">Fully Loaded Cost:</label>
                <input id="position-cost" type="number" v-model="selectedPosition.fully_loaded_cost" class="form-control" />
              </div>
              <div class="form-group">
                <label for="position-cost-center">Cost Center:</label>
                <input id="position-cost-center" type="text" v-model="selectedPosition.cost_center" class="form-control" />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="position-location">Location:</label>
                <input id="position-location" type="text" v-model="selectedPosition.location" class="form-control" />
              </div>
              <div class="form-group">
                <label for="position-status">Status:</label>
                <select id="position-status" v-model="selectedPosition.pivot.status" class="form-control">
                  <option value="unchanged">Unchanged</option>
                  <option value="new">New</option>
                  <option value="changed">Changed</option>
                  <option value="removed">Removed</option>
                </select>
              </div>
            </div>
          </form>

          <div v-else class="position-details">
            <div class="detail-section">
              <h4>Basic Information</h4>
              <div class="detail-row">
                <div class="detail-label">Title:</div>
                <div class="detail-value">{{ selectedPosition.title }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value">{{ getDepartmentName(selectedPosition.department_id) }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                  <span class="status-badge" :class="getPositionStatusClass(selectedPosition)">
                    {{ getPositionStatus(selectedPosition) }}
                  </span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Classification</h4>
              <div class="detail-row">
                <div class="detail-label">Grade:</div>
                <div class="detail-value">{{ selectedPosition.grade || 'N/A' }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Function:</div>
                <div class="detail-value">{{ selectedPosition.function || 'N/A' }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Sub-function:</div>
                <div class="detail-value">{{ selectedPosition.sub_function || 'N/A' }}</div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Financial Information</h4>
              <div class="detail-row">
                <div class="detail-label">Fully Loaded Cost:</div>
                <div class="detail-value">{{ formatCurrency(selectedPosition.fully_loaded_cost) }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Cost Center:</div>
                <div class="detail-value">{{ selectedPosition.cost_center || 'N/A' }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Contract Type:</div>
                <div class="detail-value">{{ selectedPosition.contract_type || 'N/A' }}</div>
              </div>
            </div>

            <div class="detail-section">
              <h4>Location Information</h4>
              <div class="detail-row">
                <div class="detail-label">Region:</div>
                <div class="detail-value">{{ selectedPosition.region || 'N/A' }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Country:</div>
                <div class="detail-value">{{ selectedPosition.country || 'N/A' }}</div>
              </div>
              <div class="detail-row">
                <div class="detail-label">Office:</div>
                <div class="detail-value">{{ selectedPosition.office || 'N/A' }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
              v-if="isEditing"
              @click="savePosition"
              class="btn-primary"
          >
            Save Changes
          </button>
          <button
              v-else
              @click="isEditing = true"
              class="btn-primary"
          >
            Edit
          </button>
          <button @click="closePositionModal" class="btn-secondary">
            {{ isEditing ? 'Cancel' : 'Close' }}
          </button>
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
      // Tabs
      activeTab: 'employees',
      tabs: [
        { id: 'employees', name: 'Employees' },
        { id: 'positions', name: 'Positions' }
      ],

      // Data
      employees: [],
      positions: [],
      departments: [],
      grades: [],

      // Filters
      employeeSearch: '',
      departmentFilter: '',
      statusFilter: '',
      positionSearch: '',
      positionDepartmentFilter: '',
      gradeFilter: '',

      // Pagination
      currentEmployeePage: 1,
      employeesPerPage: 10,
      currentPositionPage: 1,
      positionsPerPage: 10,

      // Sorting
      employeeSort: { field: 'name', direction: 'asc' },
      positionSort: { field: 'title', direction: 'asc' },

      // Modals
      showImportModal: false,
      showExportModal: false,
      selectedEmployee: null,
      selectedPosition: null,
      isEditing: false,

      // File Import/Export
      selectedFile: null,
      importType: 'employees',
      importOptions: {
        overwrite: false,
        skipErrors: true,
        validateOnly: false
      },
      exportType: 'employees',
      exportFormat: 'csv',
      exportOptions: {
        includeHeaders: true,
        currentFiltersOnly: false,
        includeMetadata: false
      }
    };
  },

  computed: {
    filteredEmployees() {
      let filtered = this.employees;

      // Apply search filter
      if (this.employeeSearch) {
        const search = this.employeeSearch.toLowerCase();
        filtered = filtered.filter(emp =>
            emp.name.toLowerCase().includes(search) ||
            emp.employee_id.toLowerCase().includes(search) ||
            emp.position.toLowerCase().includes(search)
        );
      }

      // Apply department filter
      if (this.departmentFilter) {
        filtered = filtered.filter(emp => emp.department_id === this.departmentFilter);
      }

      // Apply status filter
      if (this.statusFilter) {
        filtered = filtered.filter(emp => emp.status === this.statusFilter);
      }

      // Apply sorting
      filtered = this.sortData(filtered, this.employeeSort.field, this.employeeSort.direction);

      // Apply pagination
      const start = (this.currentEmployeePage - 1) * this.employeesPerPage;
      const end = start + this.employeesPerPage;

      return filtered.slice(start, end);
    },

    totalEmployeePages() {
      let filteredTotal = this.employees.length;

      // Apply search filter
      if (this.employeeSearch) {
        const search = this.employeeSearch.toLowerCase();
        filteredTotal = this.employees.filter(emp =>
            emp.name.toLowerCase().includes(search) ||
            emp.employee_id.toLowerCase().includes(search) ||
            emp.position.toLowerCase().includes(search)
        ).length;
      }

      // Apply department filter
      if (this.departmentFilter) {
        filteredTotal = this.employees.filter(emp => emp.department_id === this.departmentFilter).length;
      }

      // Apply status filter
      if (this.statusFilter) {
        filteredTotal = this.employees.filter(emp => emp.status === this.statusFilter).length;
      }

      return Math.ceil(filteredTotal / this.employeesPerPage);
    },

    filteredPositions() {
      let filtered = this.positions;

      // Apply search filter
      if (this.positionSearch) {
        const search = this.positionSearch.toLowerCase();
        filtered = filtered.filter(pos =>
            pos.title.toLowerCase().includes(search) ||
            (pos.function && pos.function.toLowerCase().includes(search))
        );
      }

      // Apply department filter
      if (this.positionDepartmentFilter) {
        filtered = filtered.filter(pos => pos.department_id === this.positionDepartmentFilter);
      }

      // Apply grade filter
      if (this.gradeFilter) {
        filtered = filtered.filter(pos => pos.grade === this.gradeFilter);
      }

      // Apply sorting
      filtered = this.sortData(filtered, this.positionSort.field, this.positionSort.direction);

      // Apply pagination
      const start = (this.currentPositionPage - 1) * this.positionsPerPage;
      const end = start + this.positionsPerPage;

      return filtered.slice(start, end);
    },

    totalPositionPages() {
      let filteredTotal = this.positions.length;

      // Apply search filter
      if (this.positionSearch) {
        const search = this.positionSearch.toLowerCase();
        filteredTotal = this.positions.filter(pos =>
            pos.title.toLowerCase().includes(search) ||
            (pos.function && pos.function.toLowerCase().includes(search))
        ).length;
      }

      // Apply department filter
      if (this.positionDepartmentFilter) {
        filteredTotal = this.positions.filter(pos => pos.department_id === this.positionDepartmentFilter).length;
      }

      // Apply grade filter
      if (this.gradeFilter) {
        filteredTotal = this.positions.filter(pos => pos.grade === this.gradeFilter).length;
      }

      return Math.ceil(filteredTotal / this.positionsPerPage);
    }
  },

  watch: {
    organization: {
      immediate: true,
      handler() {
        this.loadData();
      }
    },

    scenario: {
      handler() {
        this.loadData();
      }
    }
  },

  methods: {
    async loadData() {
      if (!this.organization || !this.scenario) return;

      try {
        // Load departments
        const departmentsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/departments`
        );
        this.departments = departmentsResponse.data;

        // Load positions
        const positionsResponse = await axios.get(
            `/api/organizations/${this.organization.id}/scenarios/${this.scenario.id}/positions`
        );
        this.positions = positionsResponse.data;

        // Extract unique grades from positions
        this.grades = [...new Set(this.positions.filter(p => p.grade).map(p => p.grade))];

        // Load employees (potentially derived from positions with names)
        // In a real implementation, this would be a separate API endpoint
        this.employees = this.positions
            .filter(p => p.name)
            .map(p => ({
              id: p.id,
              employee_id: p.employee_id || `EMP${p.id}`,
              name: p.name,
              position: p.title,
              department_id: p.department_id,
              status: this.getEmployeeStatus(p),
              hire_date: this.getRandomHireDate(),
              notes: ''
            }));
      } catch (error) {
        console.error('Error loading HR data:', error);
      }
    },

    getRandomHireDate() {
      // Generate a random hire date between 5 years ago and 1 month ago
      const end = new Date();
      end.setMonth(end.getMonth() - 1);

      const start = new Date();
      start.setFullYear(start.getFullYear() - 5);

      const randomDate = new Date(start.getTime() + Math.random() * (end.getTime() - start.getTime()));
      return randomDate.toISOString().split('T')[0];
    },

    getEmployeeStatus(position) {
      if (!position.pivot) return 'active';

      switch (position.pivot.status) {
        case 'removed':
          return 'terminated';
        case 'new':
          return 'active';
        default:
          return 'active';
      }
    },

    formatDate(dateStr) {
      if (!dateStr) return 'N/A';
      const date = new Date(dateStr);
      return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      }).format(date);
    },

    formatStatus(status) {
      if (!status) return 'Unknown';

      return status
          .split('-')
          .map(word => word.charAt(0).toUpperCase() + word.slice(1))
          .join(' ');
    },

    formatCurrency(value) {
      if (!value) return '$0';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0
      }).format(value);
    },

    getDepartmentName(departmentId) {
      if (!departmentId) return 'N/A';
      const department = this.departments.find(dept => dept.id === departmentId);
      return department ? department.name : 'N/A';
    },

    getPositionStatus(position) {
      if (!position.pivot) return 'Unknown';

      switch (position.pivot.status) {
        case 'unchanged':
          return 'Unchanged';
        case 'new':
          return 'New';
        case 'changed':
          return 'Changed';
        case 'removed':
          return 'Removed';
        default:
          return 'Unknown';
      }
    },

    getPositionStatusClass(position) {
      if (!position.pivot) return '';
      return position.pivot.status;
    },

    calculateTenure(hireDate) {
      if (!hireDate) return 'N/A';

      const hire = new Date(hireDate);
      const today = new Date();

      let years = today.getFullYear() - hire.getFullYear();
      let months = today.getMonth() - hire.getMonth();

      if (months < 0) {
        years--;
        months += 12;
      }

      if (years === 0) {
        return `${months} month${months !== 1 ? 's' : ''}`;
      } else if (months === 0) {
        return `${years} year${years !== 1 ? 's' : ''}`;
      } else {
        return `${years} year${years !== 1 ? 's' : ''}, ${months} month${months !== 1 ? 's' : ''}`;
      }
    },

    sortData(data, field, direction) {
      return [...data].sort((a, b) => {
        let aValue = a[field];
        let bValue = b[field];

        // Handle nested fields
        if (field === 'department') {
          aValue = this.getDepartmentName(a.department_id);
          bValue = this.getDepartmentName(b.department_id);
        }

        // Handle status
        if (field === 'status') {
          if (a.pivot && b.pivot) {
            aValue = a.pivot.status;
            bValue = b.pivot.status;
          }
        }

        // Handle null or undefined values
        if (aValue === null || aValue === undefined) aValue = '';
        if (bValue === null || bValue === undefined) bValue = '';

        // Sort strings case-insensitively
        if (typeof aValue === 'string' && typeof bValue === 'string') {
          aValue = aValue.toLowerCase();
          bValue = bValue.toLowerCase();
        }

        if (direction === 'asc') {
          return aValue > bValue ? 1 : aValue < bValue ? -1 : 0;
        } else {
          return aValue < bValue ? 1 : aValue > bValue ? -1 : 0;
        }
      });
    },

    sortEmployees(field) {
      if (this.employeeSort.field === field) {
        this.employeeSort.direction = this.employeeSort.direction === 'asc' ? 'desc' : 'asc';
      } else {
        this.employeeSort.field = field;
        this.employeeSort.direction = 'asc';
      }
    },

    sortPositions(field) {
      if (this.positionSort.field === field) {
        this.positionSort.direction = this.positionSort.direction === 'asc' ? 'desc' : 'asc';
      } else {
        this.positionSort.field = field;
        this.positionSort.direction = 'asc';
      }
    },

    getSortIcon(field) {
      const sort = this.activeTab === 'employees' ? this.employeeSort : this.positionSort;

      if (sort.field !== field) {
        return 'fas fa-sort';
      }

      return sort.direction === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down';
    },

    editEmployee(employee) {
      this.selectedEmployee = JSON.parse(JSON.stringify(employee));
      this.isEditing = true;
    },

    viewEmployeeDetails(employee) {
      this.selectedEmployee = JSON.parse(JSON.stringify(employee));
      this.isEditing = false;
    },

    closeEmployeeModal() {
      this.selectedEmployee = null;
      this.isEditing = false;
    },

    async saveEmployee() {
      try {
        // In a real implementation, this would make an API call
        const index = this.employees.findIndex(e => e.id === this.selectedEmployee.id);
        if (index !== -1) {
          this.employees[index] = { ...this.selectedEmployee };
        }

        this.closeEmployeeModal();
      } catch (error) {
        console.error('Error saving employee:', error);
      }
    },

    editPosition(position) {
      this.selectedPosition = JSON.parse(JSON.stringify(position));
      this.isEditing = true;
    },

    viewPositionDetails(position) {
      this.selectedPosition = JSON.parse(JSON.stringify(position));
      this.isEditing = false;
    },

    closePositionModal() {
      this.selectedPosition = null;
      this.isEditing = false;
    },

    async savePosition() {
      try {
        // API call to update the position
        await axios.put(
            `/api/organizations/${this.organization.id}/positions/${this.selectedPosition.id}`,
            this.selectedPosition
        );

        // Update the position in the local array
        const index = this.positions.findIndex(p => p.id === this.selectedPosition.id);
        if (index !== -1) {
          this.positions[index] = { ...this.selectedPosition };
        }

        this.closePositionModal();
      } catch (error) {
        console.error('Error saving position:', error);
      }
    },

    triggerFileUpload() {
      this.$refs.fileInput.click();
    },

    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
    },

    async startImport() {
      if (!this.selectedFile) return;

      const formData = new FormData();
      formData.append('file', this.selectedFile);
      formData.append('type', this.importType);
      formData.append('options', JSON.stringify(this.importOptions));

      try {
        // In a real implementation, this would make an API call
        console.log('Importing file:', this.selectedFile.name);
        console.log('Import type:', this.importType);
        console.log('Import options:', this.importOptions);

        // Simulate success
        setTimeout(() => {
          alert('Data imported successfully!');
          this.showImportModal = false;
          this.selectedFile = null;
          this.loadData();
        }, 1000);
      } catch (error) {
        console.error('Error importing data:', error);
      }
    },

    startExport() {
      try {
        // In a real implementation, this would make an API call
        console.log('Exporting data:', this.exportType);
        console.log('Export format:', this.exportFormat);
        console.log('Export options:', this.exportOptions);

        // Simulate success
        setTimeout(() => {
          alert('Data exported successfully!');
          this.showExportModal = false;
        }, 1000);
      } catch (error) {
        console.error('Error exporting data:', error);
      }
    }
  }
};
</script>

<style scoped>
.hr-data {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.data-tabs {
  display: flex;
  background-color: #f5f5f5;
  border-bottom: 1px solid #ddd;
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

.data-section {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 200px;
}

.search-box i {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.search-input {
  width: 100%;
  padding: 0.5rem 0.5rem 0.5rem 2rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.data-grid {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.data-table th {
  position: relative;
  cursor: pointer;
  font-weight: 600;
  color: #555;
  white-space: nowrap;
}

.data-table th i {
  margin-left: 0.25rem;
  font-size: 0.75rem;
}

.data-table tbody tr:hover {
  background-color: #f9f9f9;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-badge.active {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.status-badge.on-leave {
  background-color: #fff8e1;
  color: #f57f17;
}

.status-badge.terminated {
  background-color: #ffebee;
  color: #c62828;
}

.status-badge.unchanged {
  background-color: #e0e0e0;
  color: #616161;
}

.status-badge.new {
  background-color: #e8f5e9;
  color: #2e7d32;
}

.status-badge.changed {
  background-color: #fff8e1;
  color: #f57f17;
}

.status-badge.removed {
  background-color: #ffebee;
  color: #c62828;
}

.actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
  font-size: 1rem;
  padding: 0.25rem;
}

.btn-icon:hover {
  color: #4caf50;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
}

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

.modal-content.modal-lg {
  max-width: 700px;
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

.form-row {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group label {
  font-weight: 500;
  font-size: 0.875rem;
}

.form-control {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 0.25rem;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
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

.employee-details,
.position-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.detail-section {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-section h4 {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid #eee;
}

.detail-row {
  display: flex;
  gap: 1rem;
}

.detail-label {
  font-weight: 500;
  width: 150px;
  flex-shrink: 0;
}

.detail-value {
  flex: 1;
}

.detail-notes {
  background-color: #f5f5f5;
  border-radius: 0.25rem;
  padding: 0.5rem;
  font-style: italic;
}

@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-controls {
    flex-direction: column;
    align-items: flex-start;
  }

  .search-box {
    width: 100%;
  }

  .detail-row {
    flex-direction: column;
    gap: 0.25rem;
  }

  .detail-label {
    width: 100%;
  }
}
</style>