<?php

namespace Database\Seeders;

use App\app\app\Models\Role;
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
                'name' => 'Orgcharts Admin',
                'slug' => 'orgcharts-admin',
                'description' => 'Q5 administrator with full access'
            ],
            [
                'name' => 'Q5 Consultant',
                'slug' => 'orgcharts-consultant',
                'description' => 'Q5 consultant with access to client organizations'
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
