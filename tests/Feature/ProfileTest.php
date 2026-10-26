<?php

namespace Tests\Feature;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Article;
use App\Models\ArticleReaction;
use App\Models\ArticleReview;
use App\Models\Comment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_form_with_photo_is_sent_as_post_with_method_spoofing(): void
    {
        // Bentuk request dari UpdateProfileInformationForm.vue: multipart via POST + _method,
        // karena PHP tidak membaca body multipart pada request PATCH.
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/profile', [
                '_method' => 'patch',
                'name' => 'Admin Expertia',
                'email' => $user->email,
                // PNG 1x1 asli; fake()->image() butuh ekstensi GD yang tidak selalu terpasang.
                'avatar' => UploadedFile::fake()->createWithContent(
                    'foto.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
                ),
                'remove_avatar' => '0',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Admin Expertia', $user->name);
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_contributor_can_fill_position_and_bio_shown_on_public_profile(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');

        $this->actingAs($author)
            ->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('contributorProfile.position', null)
                ->where('contributorProfile.bio', null)
                ->where('contributorProfile.public_url', route('authors.show', $author->profile_slug)));

        $this->actingAs($author)
            ->patch('/profile', [
                'name' => $author->name,
                'email' => $author->email,
                'position' => 'Dosen Ekonomi Syariah',
                'bio' => "Meneliti wakaf uang sejak 2015.\nMengajar di Fakultas Ekonomi.",
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $author->refresh();
        $this->assertSame('Dosen Ekonomi Syariah', $author->position);
        $this->assertSame("Meneliti wakaf uang sejak 2015.\nMengajar di Fakultas Ekonomi.", $author->bio);

        $this->get(route('authors.show', $author->profile_slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('author.position', 'Dosen Ekonomi Syariah')
                ->where('author.bio', "Meneliti wakaf uang sejak 2015.\nMengajar di Fakultas Ekonomi."));
    }

    public function test_contributor_can_clear_position_and_bio(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create(['position' => 'Peneliti', 'bio' => 'Bio lama.']);
        $author->assignRole('verified-expert');

        $this->actingAs($author)
            ->patch('/profile', ['name' => $author->name, 'email' => $author->email, 'position' => '', 'bio' => ''])
            ->assertSessionHasNoErrors();

        $author->refresh();
        $this->assertNull($author->position);
        $this->assertNull($author->bio);
    }

    public function test_bio_has_a_length_limit(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');

        $this->actingAs($author)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $author->name,
                'email' => $author->email,
                'bio' => str_repeat('a', ProfileUpdateRequest::BIO_MAX_LENGTH + 1),
            ])
            ->assertSessionHasErrors('bio');

        $this->assertNull($author->fresh()->bio);
    }

    public function test_reader_has_no_contributor_section_and_cannot_set_it(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $reader = User::factory()->create();
        $reader->assignRole('user');

        $this->actingAs($reader)
            ->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('contributorProfile', null));

        $this->actingAs($reader)
            ->patch('/profile', [
                'name' => $reader->name,
                'email' => $reader->email,
                'position' => 'Pakar Palsu',
                'bio' => 'Mengaku pakar.',
            ])
            ->assertSessionHasNoErrors();

        $reader->refresh();
        $this->assertNull($reader->position);
        $this->assertNull($reader->bio);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_contributor_cannot_delete_own_account_and_lose_published_articles(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');
        $article = Article::factory()->published()->for($author, 'author')->create();

        $this->actingAs($author)
            ->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canDeleteAccount', false));

        $this->actingAs($author)
            ->delete('/profile', ['password' => 'password123'])
            ->assertForbidden();

        $this->assertNotNull($author->fresh());
        $this->assertNotNull($article->fresh());
    }

    public function test_reader_who_became_staff_keeps_review_history(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        // Bekas editor yang sudah diturunkan jadi pembaca: riwayat review-nya tetap terhubung.
        $formerEditor = User::factory()->create();
        $formerEditor->assignRole('user');
        ArticleReview::create([
            'article_id' => Article::factory()->published()->create()->id,
            'editor_id' => $formerEditor->id,
            'action' => 'approve',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($formerEditor)
            ->delete('/profile', ['password' => 'password123'])
            ->assertForbidden();
    }

    public function test_reader_can_still_delete_own_account(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $reader = User::factory()->create();
        $reader->assignRole('user');

        $this->actingAs($reader)
            ->get('/profile')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canDeleteAccount', true));

        $this->actingAs($reader)
            ->delete('/profile', ['password' => 'password123'])
            ->assertRedirect('/');

        $this->assertNull($reader->fresh());
    }

    public function test_deleting_a_reader_keeps_others_replies_and_recounts_the_article(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');
        $article = Article::factory()->published()->for($author, 'author')->create();

        $reader = User::factory()->create();
        $reader->assignRole('user');
        $otherReader = User::factory()->create();

        $comment = Comment::create(['article_id' => $article->id, 'user_id' => $reader->id, 'content' => 'Pertanyaan', 'status' => 'approved']);
        $reply = Comment::create(['article_id' => $article->id, 'user_id' => $otherReader->id, 'parent_id' => $comment->id, 'content' => 'Jawaban', 'status' => 'approved']);
        $reader->bookmarks()->create(['article_id' => $article->id]);
        ArticleReaction::create(['article_id' => $article->id, 'user_id' => $reader->id, 'type' => 'like']);
        $article->update(['comment_count' => 2, 'bookmark_count' => 1, 'like_count' => 1]);

        $this->actingAs($reader)
            ->delete('/profile', ['password' => 'password123'])
            ->assertRedirect('/');

        $this->assertNull($reader->fresh());
        $this->assertNull($reply->fresh()->parent_id);

        $article->refresh();
        $this->assertSame(1, $article->comment_count);
        $this->assertSame(0, $article->bookmark_count);
        $this->assertSame(0, $article->like_count);
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
