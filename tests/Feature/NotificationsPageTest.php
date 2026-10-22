<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class NotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_count_survives_on_the_notifications_page(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 25) as $i) {
            UserNotification::create(['user_id' => $user->id, 'type' => 'x', 'title' => "N{$i}", 'body' => 'Isi']);
        }
        UserNotification::query()->latest('id')->limit(20)->update(['read_at' => now()]);

        // Prop halaman "notifications" (daftar) tidak boleh menimpa jumlah belum dibaca
        // yang dipakai badge sidebar dan tombol "Tandai semua dibaca".
        $this->actingAs($user)
            ->get(route('me.notifications.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('notifications.data', 20)
                ->where('notificationSummary.unread_count', 5));
    }
}
