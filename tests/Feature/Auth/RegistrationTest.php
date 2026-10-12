<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registrasi memberi role "user"; tanpa seeder role itu belum ada.
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertTrue(User::firstWhere('email', 'test@example.com')->hasRole('user'));
    }

    public function test_public_author_registration_is_closed(): void
    {
        $this->get('/register/author')->assertRedirect('/kontributor');

        $this->post('/register/author', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/kontributor');

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@example.com']);
        $this->assertGuest();
    }

    public function test_admin_can_create_contributor_account_without_switching_session(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.contributors.store'), [
            'name' => 'Agung Yulianto',
            'email' => 'agung@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gender' => 'Pria',
            'whatsapp_number' => '081200000000',
            'institution' => 'Universitas Contoh',
            'institution_unit' => 'Fakultas Ekonomi',
            'expertise_other' => 'Wakaf produktif',
            'role' => 'verified-expert',
        ]);

        $response->assertRedirect(route('admin.contributors.create'));
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(User::firstWhere('email', 'agung@example.com')->hasRole('verified-expert'));
    }

    public function test_editor_cannot_create_contributor_accounts(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $this->actingAs($editor)->get(route('admin.contributors.create'))->assertForbidden();
    }
}
