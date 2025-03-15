<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Position;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class HrDataSeeder extends Seeder
{
    public function run()
    {
        // Get the Demo Organization
        $organization = Organization::where('name', 'Demo Organisation')->first();

        if (!$organization) {
            return;
        }

        // Create departments if they don't exist
        $departments = [
            ['name' => 'Executive', 'code' => 'EXEC'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Sales', 'code' => 'SALES'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'IT', 'code' => 'IT']
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(
                ['organization_id' => $organization->id, 'code' => $dept['code']],
                ['name' => $dept['name']]
            );
        }

        // Create positions
        $positions = [
            // Executive
            ['title' => 'Chief Executive Officer', 'department' => 'EXEC', 'grade' => 'E1', 'function' => 'Leadership'],
            ['title' => 'Chief Financial Officer', 'department' => 'EXEC', 'grade' => 'E2', 'function' => 'Finance'],
            ['title' => 'Chief Operating Officer', 'department' => 'EXEC', 'grade' => 'E2', 'function' => 'Operations'],

            // Finance
            ['title' => 'Finance Director', 'department' => 'FIN', 'grade' => 'D1', 'function' => 'Finance'],
            ['title' => 'Senior Financial Analyst', 'department' => 'FIN', 'grade' => 'M2', 'function' => 'Finance'],
            ['title' => 'Financial Analyst', 'department' => 'FIN', 'grade' => 'P2', 'function' => 'Finance'],
            ['title' => 'Accountant', 'department' => 'FIN', 'grade' => 'P1', 'function' => 'Finance'],

            // HR
            ['title' => 'HR Director', 'department' => 'HR', 'grade' => 'D1', 'function' => 'Human Resources'],
            ['title' => 'HR Manager', 'department' => 'HR', 'grade' => 'M1', 'function' => 'Human Resources'],
            ['title' => 'HR Specialist', 'department' => 'HR', 'grade' => 'P2', 'function' => 'Human Resources'],

            // Sales
            ['title' => 'Sales Director', 'department' => 'SALES', 'grade' => 'D1', 'function' => 'Sales'],
            ['title' => 'Regional Sales Manager', 'department' => 'SALES', 'grade' => 'M2', 'function' => 'Sales'],
            ['title' => 'Senior Sales Representative', 'department' => 'SALES', 'grade' => 'P3', 'function' => 'Sales'],
            ['title' => 'Sales Representative', 'department' => 'SALES', 'grade' => 'P1', 'function' => 'Sales'],

            // Marketing
            ['title' => 'Marketing Director', 'department' => 'MKT', 'grade' => 'D1', 'function' => 'Marketing'],
            ['title' => 'Marketing Manager', 'department' => 'MKT', 'grade' => 'M1', 'function' => 'Marketing'],
            ['title' => 'Digital Marketing Specialist', 'department' => 'MKT', 'grade' => 'P2', 'function' => 'Marketing'],

            // Operations
            ['title' => 'Operations Director', 'department' => 'OPS', 'grade' => 'D1', 'function' => 'Operations'],
            ['title' => 'Operations Manager', 'department' => 'OPS', 'grade' => 'M1', 'function' => 'Operations'],
            ['title' => 'Operations Analyst', 'department' => 'OPS', 'grade' => 'P2', 'function' => 'Operations'],

            // IT
            ['title' => 'IT Director', 'department' => 'IT', 'grade' => 'D1', 'function' => 'Technology'],
            ['title' => 'IT Manager', 'department' => 'IT', 'grade' => 'M1', 'function' => 'Technology'],
            ['title' => 'Senior Developer', 'department' => 'IT', 'grade' => 'P3', 'function' => 'Technology'],
            ['title' => 'Developer', 'department' => 'IT', 'grade' => 'P1', 'function' => 'Technology']
        ];

        foreach ($positions as $pos) {
            $department = Department::where('organization_id', $organization->id)
                ->where('code', $pos['department'])
                ->first();

            if ($department) {
                Position::create([
                    'organization_id' => $organization->id,
                    'department_id' => $department->id,
                    'title' => $pos['title'],
                    'grade' => $pos['grade'],
                    'function' => $pos['function'],
                    'employee_id' => 'EMP' . rand(10000, 99999),
                    'fully_loaded_cost' => $this->getGradeSalary($pos['grade'])
                ]);
            }
        }

        // Create employees
        $employees = [
            ['first_name' => 'John', 'last_name' => 'Smith', 'position' => 'Chief Executive Officer'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'position' => 'Chief Financial Officer'],
            ['first_name' => 'Michael', 'last_name' => 'Brown', 'position' => 'Chief Operating Officer'],
            ['first_name' => 'Emily', 'last_name' => 'Davis', 'position' => 'HR Director'],
            ['first_name' => 'David', 'last_name' => 'Wilson', 'position' => 'Sales Director'],
            ['first_name' => 'Jennifer', 'last_name' => 'Martinez', 'position' => 'Marketing Director'],
            ['first_name' => 'Robert', 'last_name' => 'Anderson', 'position' => 'IT Director'],
            // Add more employees as needed
        ];

        foreach ($employees as $emp) {
            $position = Position::where('organization_id', $organization->id)
                ->where('title', $emp['position'])
                ->first();

            if ($position) {
                Employee::create([
                    'organization_id' => $organization->id,
                    'department_id' => $position->department_id,
                    'position_id' => $position->id,
                    'first_name' => $emp['first_name'],
                    'last_name' => $emp['last_name'],
                    'email' => strtolower($emp['first_name'] . '.' . $emp['last_name'] . '@demo.com'),
                    'hire_date' => now()->subMonths(rand(1, 36)),
                    'status' => 'active',
                    'employee_id' => $position->employee_id
                ]);
            }
        }
    }

    private function getGradeSalary($grade)
    {
        $salaries = [
            'E1' => 350000,
            'E2' => 250000,
            'D1' => 180000,
            'M2' => 140000,
            'M1' => 120000,
            'P3' => 100000,
            'P2' => 80000,
            'P1' => 60000
        ];

        return $salaries[$grade] ?? 50000;
    }
}