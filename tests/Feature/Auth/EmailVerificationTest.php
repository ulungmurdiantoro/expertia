<?php

namespace Tests\Feature\Auth;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_registration_sends_verification_email_in_indonesian(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        Notification::fake();

        $this->post('/register', [
            'name' => 'Pembaca Baru',
            'email' => 'pembaca@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'pembaca@example.com')->sole();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Verifikasi email akun Expertia.id' && $mail->actionText === 'Verifikasi email';
        });
    }

    public function test_unverified_reader_must_verify_before_interacting(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');
        $article = Article::factory()->published()->for($author, 'author')->create();
        $reader = User::factory()->unverified()->create();
        $reader->assignRole('user');

        $this->actingAs($reader)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->actingAs($reader)->get(route('me.bookmarks.index'))->assertRedirect(route('verification.notice'));
        $this->actingAs($reader)
            ->post(route('articles.comments.store', $article->slug), ['content' => 'Halo'])
            ->assertRedirect(route('verification.notice'));
        $this->assertSame(0, $article->comments()->count());

        // Profile tetap terbuka untuk memperbaiki alamat email yang salah ketik.
        $this->actingAs($reader)->get(route('profile.edit'))->assertOk();

        $this->actingAs($reader)
            ->get(route('articles.show', $article->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('viewer.is_authenticated', true)
                ->where('viewer.is_verified', false)
                ->where('article.can_report', false));
    }

    public function test_existing_staff_and_contributors_are_marked_verified_but_readers_are_not(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $editor = User::factory()->unverified()->create();
        $editor->assignRole('editor');
        $author = User::factory()->unverified()->create();
        $author->assignRole('author');
        $reader = User::factory()->unverified()->create();
        $reader->assignRole('user');

        (require database_path('migrations/2026_10_26_000001_mark_existing_staff_emails_verified.php'))->up();

        $this->assertTrue($editor->fresh()->hasVerifiedEmail());
        $this->assertTrue($author->fresh()->hasVerifiedEmail());
        $this->assertFalse($reader->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
