<?php

// tests/Feature/OrganizationTest.php
namespace Tests\Feature;

use App\app\app\Models\Organization;
use App\app\app\Models\Role;
use App\app\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    public function setUp(): void
    {
        parent::setUp();

        // Create roles
        $role = Role::create(['name' => 'Orgcharts Admin', 'slug' => 'orgcharts-admin', 'description' => 'OrgChart administrator']);

        // Create user
        $this->user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);

        // Create token
        $this->token = $this->user->createToken('auth_token')->plainTextToken;
    }

    /** @test */
    public function a_user_can_create_an_organization()
    {
        Storage::fake('public');

        $logo = UploadedFile::fake()->image('logo.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/organizations', [
            'name' => 'Test Organization',
            'description' => 'This is a test organization',
            'logo' => $logo,
            'primary_color' => '#4caf50',
            'secondary_color' => '#2196f3'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Test Organization');

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
            'description' => 'This is a test organization'
        ]);

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $this->user->id,
            'is_admin' => 1
        ]);

        Storage::disk('public')->assertExists($response->json('logo_path'));
    }

    /** @test */
    public function a_user_can_view_their_organizations()
    {
        // Create two organizations for the user
        $org1 = Organization::factory()->create([
            'name' => 'First Organization',
            'slug' => 'first-organization'
        ]);
        $org2 = Organization::factory()->create([
            'name' => 'Second Organization',
            'slug' => 'second-organization'
        ]);

        // Attach user to organizations
        $this->user->organizations()->attach($org1->id, ['is_admin' => true]);
        $this->user->organizations()->attach($org2->id, ['is_admin' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/organizations');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.name', 'First Organization')
            ->assertJsonPath('1.name', 'Second Organization');
    }

    /** @test */
    public function a_user_can_view_a_single_organization()
    {
        // Create an organization for the user
        $org = Organization::factory()->create([
            'name' => 'Test Organization',
            'slug' => 'test-organization'
        ]);

        // Attach user to organization
        $this->user->organizations()->attach($org->id, ['is_admin' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/organizations/' . $org->id);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Test Organization')
            ->assertJsonPath('slug', 'test-organization');
    }

    /** @test */
    public function a_user_can_update_an_organization()
    {
        // Create an organization for the user
        $org = Organization::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
            'description' => 'Old description'
        ]);

        // Attach user to organization
        $this->user->organizations()->attach($org->id, ['is_admin' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson('/api/organizations/' . $org->id, [
            'name' => 'New Name',
            'description' => 'New description'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('description', 'New description')
            ->assertJsonPath('slug', 'new-name');

        $this->assertDatabaseHas('organizations', [
            'id' => $org->id,
            'name' => 'New Name',
            'slug' => 'new-name',
            'description' => 'New description'
        ]);
    }

    /** @test */
    public function a_user_can_delete_an_organization()
    {
        // Create an organization for the user
        $org = Organization::factory()->create([
            'name' => 'Test Organization',
            'slug' => 'test-organization'
        ]);

        // Attach user to organization
        $this->user->organizations()->attach($org->id, ['is_admin' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson('/api/organizations/' . $org->id);

        $response->assertStatus(204);

        $this->assertSoftDeleted('organizations', [
            'id' => $org->id
        ]);
    }

    /** @test */
    public function a_user_cannot_access_organizations_they_do_not_belong_to()
    {
        // Create an organization that the user does not belong to
        $org = Organization::factory()->create([
            'name' => 'Other Organization',
            'slug' => 'other-organization'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/organizations/' . $org->id);

        $response->assertStatus(403);
    }
}