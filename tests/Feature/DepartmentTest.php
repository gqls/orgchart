<?php

// tests/Feature/DepartmentTest.php
namespace Tests\Feature;

use App\app\app\Models\Department;
use App\app\app\Models\Organization;
use App\app\app\Models\Role;
use App\app\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $organization;

    public function setUp(): void
    {
        parent::setUp();

        // Create role
        $role = Role::create(['name' => 'Orgcharts Admin', 'slug' => 'orgcharts-admin', 'description' => 'OrgChart administrator']);

        // Create user
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);

        // Create token
        $this->token = $this->user->createToken('auth_token')->plainTextToken;

        // Create organization
        $this->organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'slug' => 'test-organization'
        ]);

        // Attach user to organization
        $this->user->organizations()->attach($this->organization->id, ['is_admin' => true]);
    }

    /** @test */
    public function a_user_can_create_a_department()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/organizations/{$this->organization->id}/departments", [
            'name' => 'Test Department',
            'code' => 'TEST-DEP',
            'description' => 'This is a test department',
            'color' => '#4caf50'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Test Department')
            ->assertJsonPath('code', 'TEST-DEP');

        $this->assertDatabaseHas('departments', [
            'organization_id' => $this->organization->id,
            'name' => 'Test Department',
            'code' => 'TEST-DEP',
            'description' => 'This is a test department',
            'color' => '#4caf50'
        ]);
    }

    /** @test */
    public function a_user_can_view_departments()
    {
        // Create departments for the organization
        Department::factory()->count(3)->create([
            'organization_id' => $this->organization->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$this->organization->id}/departments");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function a_user_can_view_a_single_department()
    {
        // Create a department for the organization
        $department = Department::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Department',
            'code' => 'TEST-DEP'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$this->organization->id}/departments/{$department->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Test Department')
            ->assertJsonPath('code', 'TEST-DEP');
    }

    /** @test */
    public function a_user_can_update_a_department()
    {
        // Create a department for the organization
        $department = Department::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Old Name',
            'code' => 'OLD-CODE',
            'description' => 'Old description'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/organizations/{$this->organization->id}/departments/{$department->id}", [
            'name' => 'New Name',
            'code' => 'NEW-CODE',
            'description' => 'New description'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('code', 'NEW-CODE')
            ->assertJsonPath('description', 'New description');

        $this->assertDatabaseHas('departments', [
            'id' => $department->id,
            'organization_id' => $this->organization->id,
            'name' => 'New Name',
            'code' => 'NEW-CODE',
            'description' => 'New description'
        ]);
    }

    /** @test */
    public function a_user_can_delete_a_department()
    {
        // Create a department for the organization
        $department = Department::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Test Department'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/organizations/{$this->organization->id}/departments/{$department->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('departments', [
            'id' => $department->id
        ]);
    }

    /** @test */
    public function a_user_cannot_access_departments_from_other_organizations()
    {
        // Create another organization
        $otherOrg = Organization::factory()->create();

        // Create a department for the other organization
        $department = Department::factory()->create([
            'organization_id' => $otherOrg->id,
            'name' => 'Other Department'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$otherOrg->id}/departments");

        $response->assertStatus(403);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$this->organization->id}/departments/{$department->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Department does not belong to this organization');
    }
}