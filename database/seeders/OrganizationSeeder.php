<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create a demo organization
        $demoOrg = Organization::create([
            'name' => 'Demo Organisation',
            'description' => 'This is a demo organization to help you get started with OrgChart. You can explore its features or create your own organization.',
            'slug' => Str::slug('Demo Organisation'),
            'primary_color' => '#4CAF50',
            'logo_path' => null, // No logo by default
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Attach all existing users to this organization (optional)
        // This ensures all users can see the demo organization
        // Use query builder directly to avoid soft delete issues
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            DB::table('organization_user')->insert([
                'organization_id' => $demoOrg->id,
                'user_id' => $user->id,
                'role_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Add demo departments
        $departments = [
            ['name' => 'Executive', 'description' => 'Company leadership team', 'order' => 1],
            ['name' => 'Sales', 'description' => 'Sales and customer acquisition team', 'order' => 2],
            ['name' => 'Marketing', 'description' => 'Brand and product marketing', 'order' => 3],
            ['name' => 'Engineering', 'description' => 'Product development and technical operations', 'order' => 4],
            ['name' => 'Customer Support', 'description' => 'Customer service and technical support', 'order' => 5],
            ['name' => 'Human Resources', 'description' => 'Recruitment and employee management', 'order' => 6],
            ['name' => 'Finance', 'description' => 'Accounting and financial planning', 'order' => 7],
        ];

        foreach ($departments as $deptData) {
            $department = $demoOrg->departments()->create([
                'name' => $deptData['name'],
                'description' => $deptData['description'],
                'order' => $deptData['order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add demo positions for each department
            $this->createDepartmentPositions($department);
        }

        // Create demo employees
        $this->createDemoEmployees($demoOrg);

        // Create a demo scenario
        // First get a user to be the creator of the scenario
        $firstUser = DB::table('users')->first();

        if ($firstUser) {
            $userId = $firstUser->id;

        // Create a demo scenario
        $scenario = $demoOrg->scenarios()->create([
            'organization_id' => $demoOrg->id,
            'user_id' => $userId,
            'name' => 'Current State',
            'description' => 'Baseline organizational structure',
            'is_current' => true,
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Store the id of the inserted scenario
        $scenarioId = DB::getPdo()->lastInsertId();

        // Create alternative scenario
        $futureScenario = $demoOrg->scenarios()->create([
            'organization_id' => $demoOrg->id,
            'user_id' => $userId,
            'name' => 'Growth Plan 2025',
            'description' => 'Projected organization structure after planned expansion',
            'is_current' => false,
            'is_base' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Store the id of the future scenario
        $futureScenarioId = DB::getPdo()->lastInsertId();

        // Create demo metrics
        $this->createDemoMetrics($demoOrg, $scenarioId);
    } else {
        echo "No users found in the database. Skipping scenario creation";}
    }

    /**
     * Create positions for a given department
     *
     * @param \App\Models\Department $department
     * @return void
     */
    private function createDepartmentPositions($department)
    {
        $positions = [];

        // Different positions based on department
        switch($department->name) {
            case 'Executive':
                $positions = [
                    ['title' => 'CEO', 'level' => 1],
                    ['title' => 'COO', 'level' => 2],
                    ['title' => 'CTO', 'level' => 2],
                    ['title' => 'CFO', 'level' => 2],
                ];
                break;
            case 'Sales':
                $positions = [
                    ['title' => 'VP of Sales', 'level' => 3],
                    ['title' => 'Sales Manager', 'level' => 4],
                    ['title' => 'Senior Sales Representative', 'level' => 5],
                    ['title' => 'Sales Representative', 'level' => 6],
                ];
                break;
            case 'Marketing':
                $positions = [
                    ['title' => 'Marketing Director', 'level' => 3],
                    ['title' => 'Marketing Manager', 'level' => 4],
                    ['title' => 'Content Specialist', 'level' => 5],
                    ['title' => 'Social Media Coordinator', 'level' => 6],
                ];
                break;
            case 'Engineering':
                $positions = [
                    ['title' => 'Engineering Director', 'level' => 3],
                    ['title' => 'Engineering Manager', 'level' => 4],
                    ['title' => 'Senior Software Engineer', 'level' => 5],
                    ['title' => 'Software Engineer', 'level' => 6],
                    ['title' => 'QA Engineer', 'level' => 6],
                ];
                break;
            case 'Customer Support':
                $positions = [
                    ['title' => 'Support Manager', 'level' => 4],
                    ['title' => 'Senior Support Specialist', 'level' => 5],
                    ['title' => 'Support Specialist', 'level' => 6],
                ];
                break;
            case 'Human Resources':
                $positions = [
                    ['title' => 'HR Director', 'level' => 3],
                    ['title' => 'HR Manager', 'level' => 4],
                    ['title' => 'HR Specialist', 'level' => 5],
                    ['title' => 'Recruiter', 'level' => 5],
                ];
                break;
            case 'Finance':
                $positions = [
                    ['title' => 'Finance Manager', 'level' => 4],
                    ['title' => 'Senior Accountant', 'level' => 5],
                    ['title' => 'Accountant', 'level' => 6],
                ];
                break;
        }

        foreach ($positions as $positionData) {
            // Get the organization_id from the department
            $organizationId = $department->organization_id;

            // Create position with both organization_id and department_id
            DB::table('positions')->insert([
                'organization_id' => $organizationId,
                'department_id' => $department->id,
                'title' => $positionData['title'],
                'level' => $positionData['level'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Create demo employees for the organization
     *
     * @param \App\Models\Organization $organization
     * @return void
     */
    private function createDemoEmployees($organization)
    {
        // Sample employee names
        $employees = [
            ['first_name' => 'John', 'last_name' => 'Smith', 'email' => 'john.smith@demo.com', 'department' => 'Executive', 'position' => 'CEO'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah.johnson@demo.com', 'department' => 'Executive', 'position' => 'COO'],
            ['first_name' => 'Michael', 'last_name' => 'Williams', 'email' => 'michael.williams@demo.com', 'department' => 'Executive', 'position' => 'CTO'],
            ['first_name' => 'Emily', 'last_name' => 'Brown', 'email' => 'emily.brown@demo.com', 'department' => 'Executive', 'position' => 'CFO'],

            ['first_name' => 'David', 'last_name' => 'Miller', 'email' => 'david.miller@demo.com', 'department' => 'Sales', 'position' => 'VP of Sales'],
            ['first_name' => 'Jessica', 'last_name' => 'Davis', 'email' => 'jessica.davis@demo.com', 'department' => 'Sales', 'position' => 'Sales Manager'],
            ['first_name' => 'Robert', 'last_name' => 'Wilson', 'email' => 'robert.wilson@demo.com', 'department' => 'Sales', 'position' => 'Senior Sales Representative'],
            ['first_name' => 'Lisa', 'last_name' => 'Taylor', 'email' => 'lisa.taylor@demo.com', 'department' => 'Sales', 'position' => 'Sales Representative'],

            ['first_name' => 'James', 'last_name' => 'Anderson', 'email' => 'james.anderson@demo.com', 'department' => 'Marketing', 'position' => 'Marketing Director'],
            ['first_name' => 'Jennifer', 'last_name' => 'Thomas', 'email' => 'jennifer.thomas@demo.com', 'department' => 'Marketing', 'position' => 'Marketing Manager'],

            ['first_name' => 'Daniel', 'last_name' => 'Jackson', 'email' => 'daniel.jackson@demo.com', 'department' => 'Engineering', 'position' => 'Engineering Director'],
            ['first_name' => 'Michelle', 'last_name' => 'White', 'email' => 'michelle.white@demo.com', 'department' => 'Engineering', 'position' => 'Engineering Manager'],
            ['first_name' => 'Christopher', 'last_name' => 'Harris', 'email' => 'christopher.harris@demo.com', 'department' => 'Engineering', 'position' => 'Senior Software Engineer'],
            ['first_name' => 'Amanda', 'last_name' => 'Martin', 'email' => 'amanda.martin@demo.com', 'department' => 'Engineering', 'position' => 'Software Engineer'],

            ['first_name' => 'Matthew', 'last_name' => 'Thompson', 'email' => 'matthew.thompson@demo.com', 'department' => 'Customer Support', 'position' => 'Support Manager'],
            ['first_name' => 'Elizabeth', 'last_name' => 'Garcia', 'email' => 'elizabeth.garcia@demo.com', 'department' => 'Customer Support', 'position' => 'Support Specialist'],

            ['first_name' => 'Andrew', 'last_name' => 'Martinez', 'email' => 'andrew.martinez@demo.com', 'department' => 'Human Resources', 'position' => 'HR Director'],
            ['first_name' => 'Stephanie', 'last_name' => 'Robinson', 'email' => 'stephanie.robinson@demo.com', 'department' => 'Human Resources', 'position' => 'HR Specialist'],

            ['first_name' => 'Kevin', 'last_name' => 'Clark', 'email' => 'kevin.clark@demo.com', 'department' => 'Finance', 'position' => 'Finance Manager'],
            ['first_name' => 'Laura', 'last_name' => 'Rodriguez', 'email' => 'laura.rodriguez@demo.com', 'department' => 'Finance', 'position' => 'Senior Accountant'],
        ];

        foreach ($employees as $employeeData) {
            // Find the department
            $department = $organization->departments()->where('name', $employeeData['department'])->first();

            if ($department) {
                // Find the position
                $position = $department->positions()->where('name', $employeeData['position'])->first();

                if ($position) {
                    // Create employee
                    $organization->employees()->create([
                        'first_name' => $employeeData['first_name'],
                        'last_name' => $employeeData['last_name'],
                        'email' => $employeeData['email'],
                        'department_id' => $department->id,
                        'position_id' => $position->id,
                        'hire_date' => now()->subMonths(rand(1, 36)),
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Create demo metrics for the organization
     *
     * @param \App\Models\Organization $organization
     * @param \App\Models\Scenario $scenario
     * @return void
     */
    private function createDemoMetrics($organization, $scenarioId)
    {
        $metrics = [
            [
                'name' => 'Headcount',
                'code' => 'headcount',
                'format' => 'number',
                'value' => 20,
                'goal' => 25
            ],
            [
                'name' => 'Revenue Per Employee',
                'code' => 'revenue_per_employee',
                'format' => 'currency',
                'value' => 250000,
                'goal' => 300000
            ],
            [
                'name' => 'Manager to Employee Ratio',
                'code' => 'manager_ratio',
                'format' => 'percentage',
                'value' => 22,
                'goal' => 20
            ],
            [
                'name' => 'Average Tenure',
                'code' => 'avg_tenure',
                'format' => 'number',
                'value' => 2.4,
                'goal' => 3
            ],
            [
                'name' => 'Efficiency Score',
                'code' => 'efficiency',
                'format' => 'percentage',
                'value' => 78,
                'goal' => 80
            ],
        ];

        foreach ($metrics as $metricData) {
            $metric = $organization->metrics()->create([
                'name' => $metricData['name'],
                'code' => $metricData['code'],
                'description' => 'Demo metric',
                'unit' => 'unit',
                'format' => $metricData['format'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create pivot data for scenario-specific values
            if ($metric && $scenarioId) {
                DB::table('scenario_metrics')->insert([
                    'metric_id' => $metric->id,
                    'scenario_id' => $scenarioId,
                    'value' => $metricData['value'],
                    'goal' => $metricData['goal'],
                    'benchmark' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
