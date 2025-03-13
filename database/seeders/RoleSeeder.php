<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles = [
            [
                'name' => 'Member',
                'slug' => 'member',
                'description' => 'Default. Member with limited access',
            ],
            [
                'name' => 'Orgcharts Admin',
                'slug' => 'orgcharts-admin',
                'description' => 'OrgChart administrator with full access'
            ],
            [
                'name' => 'OrgChart Consultant',
                'slug' => 'orgcharts-consultant',
                'description' => 'OrgChart consultant with access to client organizations'
            ],
            [
                'name' => 'Management Consultant',
                'slug' => 'management-consultant',
                'description' => 'Management consultant with access to assigned organizations'
            ],
            [
                'name' => 'End-User Client',
                'slug' => 'end-user-client',
                'description' => 'End-user client with access to own organization'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
