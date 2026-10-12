<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeding_creates_roles_but_no_demo_accounts(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        // Di produksi db:seed meminta konfirmasi; --force seperti saat dijalankan di server.
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        $this->assertTrue(Role::where('name', 'admin')->exists());
        $this->assertSame(0, User::where('email', 'like', '%@example.com')->count());
    }

    public function test_local_seeding_still_creates_demo_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('email', 'superadmin@example.com')->exists());
    }
}
