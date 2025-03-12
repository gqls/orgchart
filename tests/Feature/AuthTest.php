<?php
// tests/Feature/AuthTest.php
namespace Tests\Feature;

use App\app\app\Models\Role;
use App\app\app\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'Orgchart Admin', 'slug' => 'orgcharts-admin', 'description' => 'OrgChart administrator']);
        Role::create(['name' => 'OrgChart Consultant', 'slug' => 'orgcharts-consultant', 'description' => 'OrgChart consultant']);
        Role::create(['name' => 'Management Consultant', 'slug' => 'management-consultant', 'description' => 'Management consultant']);
        Role::create(['name' => 'End-User Client', 'slug' => 'end-user-client', 'description' => 'End-user client']);
    }

    /** @test */
    public function a_user_can_register()
    {
        $role = Role::where('slug', 'end-user-client')->first();

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $role->id
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role_id'],
                'access_token',
                'token_type'
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
    }

    /** @test */
    public function a_user_can_login()
    {
        $role = Role::where('slug', 'end-user-client')->first();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role_id', 'role'],
                'access_token',
                'token_type'
            ]);
    }

    /** @test */
    public function a_user_can_logout()
    {
        $role = Role::where('slug', 'end-user-client')->first();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
    }

    /** @test */
    public function a_user_cannot_login_with_invalid_credentials()
    {
        $role = Role::where('slug', 'end-user-client')->first();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid login details'
            ]);
    }
}