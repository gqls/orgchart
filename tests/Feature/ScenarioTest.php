<?php

// tests/Feature/ScenarioTest.php
namespace Tests\Feature;

use App\app\app\Models\Department;
use App\app\app\Models\Metric;
use App\app\app\Models\Organization;
use App\app\app\Models\Position;
use App\app\app\Models\Role;
use App\app\app\Models\Scenario;
use App\app\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ScenarioTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;
    protected $organization;

    public function setUp(): void
    {
        parent::setUp();

        // Create role
        $role = Role::create(['name' => 'Orgcharts Admin', 'slug' => 'orgcharts-admin', 'description' => 'Q5 administrator']);

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

        // Create department
        $department = Department::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Finance'
        ]);

        // Create metrics
        Metric::create([
            'organization_id' => $this->organization->id,
            'name' => 'Headcount',
            'code' => 'headcount',
            'description' => 'Total number of positions',
            'unit' => 'positions',
            'format' => 'number'
        ]);

        Metric::create([
            'organization_id' => $this->organization->id,
            'name' => 'Total Fully Loaded Cost',
            'code' => 'total_fully_loaded_cost',
            'description' => 'Total fully loaded cost',
            'unit' => 'USD',
            'format' => 'currency'
        ]);
    }

    /** @test */
    public function a_user_can_create_a_scenario()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/organizations/{$this->organization->id}/scenarios", [
            'name' => 'Base Scenario',
            'description' => 'This is the base scenario',
            'is_base' => true
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Base Scenario')
            ->assertJsonPath('is_base', true)
            ->assertJsonPath('is_current', true); // First scenario should be current

        $this->assertDatabaseHas('scenarios', [
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Base Scenario',
            'description' => 'This is the base scenario',
            'is_base' => 1,
            'is_current' => 1
        ]);
    }

    /** @test */
    public function a_user_can_view_scenarios()
    {
        // Create scenarios for the organization
        Scenario::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$this->organization->id}/scenarios");

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    /** @test */
    public function a_user_can_view_a_single_scenario()
    {
        // Create a scenario for the organization
        $scenario = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Test Scenario'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$this->organization->id}/scenarios/{$scenario->id}");

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Test Scenario');
    }

    /** @test */
    public function a_user_can_update_a_scenario()
    {
        // Create a scenario for the organization
        $scenario = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Old Name',
            'description' => 'Old description',
            'is_current' => false,
            'is_base' => false
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/organizations/{$this->organization->id}/scenarios/{$scenario->id}", [
            'name' => 'New Name',
            'description' => 'New description',
            'is_current' => true,
            'is_base' => true
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('description', 'New description')
            ->assertJsonPath('is_current', true)
            ->assertJsonPath('is_base', true);

        $this->assertDatabaseHas('scenarios', [
            'id' => $scenario->id,
            'organization_id' => $this->organization->id,
            'name' => 'New Name',
            'description' => 'New description',
            'is_current' => 1,
            'is_base' => 1
        ]);
    }

    /** @test */
    public function only_one_scenario_can_be_current()
    {
        // Create two scenarios
        $scenario1 = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Scenario 1',
            'is_current' => true
        ]);

        $scenario2 = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Scenario 2',
            'is_current' => false
        ]);

        // Set scenario 2 as current
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/organizations/{$this->organization->id}/scenarios/{$scenario2->id}", [
            'is_current' => true
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_current', true);

        // Verify scenario 1 is no longer current
        $this->assertDatabaseHas('scenarios', [
            'id' => $scenario1->id,
            'is_current' => 0
        ]);

        $this->assertDatabaseHas('scenarios', [
            'id' => $scenario2->id,
            'is_current' => 1
        ]);
    }

    /** @test */
    public function a_user_can_delete_a_scenario()
    {
        // Create two scenarios
        $scenario1 = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Scenario 1',
            'is_current' => false
        ]);

        $scenario2 = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Scenario 2',
            'is_current' => true
        ]);

        // Try to delete current scenario (should fail)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/organizations/{$this->organization->id}/scenarios/{$scenario2->id}");

        $response->assertStatus(422);

        // Delete non-current scenario
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/organizations/{$this->organization->id}/scenarios/{$scenario1->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('scenarios', [
            'id' => $scenario1->id
        ]);

        $this->assertDatabaseHas('scenarios', [
            'id' => $scenario2->id
        ]);
    }

    /** @test */
    public function a_user_can_compare_scenarios()
    {
        // Create department
        $department = Department::factory()->create([
            'organization_id' => $this->organization->id,
            'name' => 'Finance'
        ]);

        // Create two scenarios
        $scenario1 = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Scenario 1',
            'is_current' => true
        ]);

        $scenario2 = Scenario::factory()->create([
            'organization_id' => $this->organization->id,
            'user_id' => $this->user->id,
            'name' => 'Scenario 2',
            'is_current' => false
        ]);

        // Create positions for both scenarios
        $position1 = Position::factory()->create([
            'organization_id' => $this->organization->id,
            'department_id' => $department->id,
            'title' => 'Finance Manager',
            'fully_loaded_cost' => 100000
        ]);

        $position2 = Position::factory()->create([
            'organization_id' => $this->organization->id,
            'department_id' => $department->id,
            'title' => 'Accountant',
            'fully_loaded_cost' => 70000
        ]);

        // Attach positions to scenarios
        $scenario1->positions()->attach($position1->id, ['status' => 'unchanged']);
        $scenario1->positions()->attach($position2->id, ['status' => 'unchanged']);
        $scenario2->positions()->attach($position1->id, ['status' => 'unchanged']);

        // Add metrics to scenarios
        $headcount = Metric::where('code', 'headcount')->first();
        $totalCost = Metric::where('code', 'total_fully_loaded_cost')->first();

        $scenario1->metrics()->attach($headcount->id, ['value' => 2, 'goal' => 2]);
        $scenario1->metrics()->attach($totalCost->id, ['value' => 170000, 'goal' => 150000]);

        $scenario2->metrics()->attach($headcount->id, ['value' => 1, 'goal' => 2]);
        $scenario2->metrics()->attach($totalCost->id, ['value' => 100000, 'goal' => 150000]);

        // Compare scenarios
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/organizations/{$this->organization->id}/compare-scenarios?scenario1_id={$scenario1->id}&scenario2_id={$scenario2->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'scenario1',
                'scenario2',
                'comparison' => [
                    '*' => ['metric', 'code', 'scenario1_value', 'scenario2_value', 'difference', 'difference_percentage']
                ]
            ]);

        // Check specific comparison values
        $comparison = $response->json('comparison');

        $headcountComparison = collect($comparison)->firstWhere('code', 'headcount');
        $costComparison = collect($comparison)->firstWhere('code', 'total_fully_loaded_cost');

        $this->assertEquals(2, $headcountComparison['scenario1_value']);
        $this->assertEquals(1, $headcountComparison['scenario2_value']);
        $this->assertEquals(1, $headcountComparison['difference']);
        $this->assertEquals(100, $headcountComparison['difference_percentage']);

        $this->assertEquals(170000, $costComparison['scenario1_value']);
        $this->assertEquals(100000, $costComparison['scenario2_value']);
        $this->assertEquals(70000, $costComparison['difference']);
        $this->assertEquals(70, $costComparison['difference_percentage']);
    }
}