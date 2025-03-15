<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Metric;
use App\Models\Organization;
use App\Models\Position;
use App\Models\Scenario;
use App\Models\ActivityLog;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run()
    {
        // Create demo organization
        $organization = Organization::create([
            'name' => 'Demo Corporation',
            'description' => 'A demonstration organization',
            'primary_color' => '#4CAF50',
            'secondary_color' => '#2196F3'
        ]);

        // Create departments
        $departments = [
            ['name' => 'Executive', 'color' => '#4CAF50'],
            ['name' => 'Finance', 'color' => '#2196F3'],
            ['name' => 'HR', 'color' => '#F44336'],
            ['name' => 'Sales', 'color' => '#FFC107'],
            ['name' => 'Engineering', 'color' => '#9C27B0']
        ];

        foreach ($departments as $dept) {
            Department::create([
                'organization_id' => $organization->id,
                'name' => $dept['name'],
                'color' => $dept['color']
            ]);
        }

        // Create scenarios
        $currentScenario = Scenario::create([
            'organization_id' => $organization->id,
            'name' => 'Current State',
            'is_current' => true,
            'is_base' => true
        ]);

        $futureScenario = Scenario::create([
            'organization_id' => $organization->id,
            'name' => 'Future State 2024',
            'is_current' => false,
            'is_base' => false
        ]);

        // Create positions
        $positions = [
            [
                'title' => 'CEO',
                'department' => 'Executive',
                'grade' => 'E1',
                'cost' => 300000
            ],
            [
                'title' => 'CFO',
                'department' => 'Finance',
                'grade' => 'E2',
                'cost' => 250000
            ],
            // Add more positions...
        ];

        foreach ($positions as $pos) {
            $dept = Department::where('name', $pos['department'])->first();
            Position::create([
                'organization_id' => $organization->id,
                'department_id' => $dept->id,
                'title' => $pos['title'],
                'grade' => $pos['grade'],
                'fully_loaded_cost' => $pos['cost']
            ]);
        }

        // Create metrics
        $metrics = [
            [
                'name' => 'Headcount',
                'code' => 'headcount',
                'value' => 150,
                'goal' => 175
            ],
            [
                'name' => 'Total Cost',
                'code' => 'total_cost',
                'value' => 15000000,
                'goal' => 16000000
            ],
            // Add more metrics...
        ];

        foreach ($metrics as $metric) {
            $m = Metric::create([
                'organization_id' => $organization->id,
                'name' => $metric['name'],
                'code' => $metric['code']
            ]);

            $currentScenario->metrics()->attach($m->id, [
                'value' => $metric['value'],
                'goal' => $metric['goal']
            ]);
        }

        // Create activity logs
        $activities = [
            [
                'action' => 'create',
                'loggable_type' => 'Position',
                'description' => 'Created new position: Senior Developer'
            ],
            [
                'action' => 'update',
                'loggable_type' => 'Department',
                'description' => 'Updated Engineering department structure'
            ],
            // Add more activities...
        ];

        foreach ($activities as $activity) {
            ActivityLog::create([
                'organization_id' => $organization->id,
                'action' => $activity['action'],
                'loggable_type' => $activity['loggable_type'],
                'description' => $activity['description'],
                'created_at' => now()->subDays(rand(1, 30))
            ]);
        }
    }
}