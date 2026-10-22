<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Halaman dashboard menampilkan string waktu apa adanya, jadi harus sudah dalam jam
 * redaksi (WIB), bukan UTC seperti di database.
 */
class DashboardTimesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_dashboard_times_are_in_newsroom_time(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        Carbon::setTestNow('2026-10-22 03:00:00'); // 10.00 WIB

        $editor = User::factory()->create();
        $editor->assignRole('editor');
        Article::factory()->pendingReview()->for($editor, 'author')->create([
            'scheduled_at' => '2026-11-01 02:00:00', // 09.00 WIB
        ]);
        UserNotification::create(['user_id' => $editor->id, 'type' => 'x', 'title' => 'Halo', 'body' => 'Isi']);

        $this->actingAs($editor)
            ->get(route('author.articles.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data.0.updated_at', '2026-10-22 10:00:00')
                ->where('articles.data.0.scheduled_at', '2026-11-01 09:00:00'));

        $this->actingAs($editor)
            ->get(route('editor.reviews.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('articles.data.0.submitted_at', '2026-10-22 10:00:00')
                ->where('articles.data.0.scheduled_at', '2026-11-01 09:00:00'));

        $this->actingAs($editor)
            ->get(route('me.notifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('notifications.data.0.created_at', '2026-10-22 10:00:00'));
    }
}
