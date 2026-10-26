<?php

namespace Tests\Feature\Public;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman /authors/{slug} dan follow hanya untuk kontributor, bukan akun pembaca.
 */
class ContributorProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->reader = User::factory()->create();
        $this->reader->assignRole('user');
    }

    public function test_reader_has_no_public_profile_and_cannot_be_followed(): void
    {
        $otherReader = User::factory()->create();
        $otherReader->assignRole('user');

        $this->get(route('authors.show', $otherReader->profile_slug))->assertNotFound();
        $this->actingAs($this->reader)
            ->post(route('authors.follow', $otherReader->profile_slug))
            ->assertNotFound();

        $this->assertSame(0, $this->reader->follows()->count());
    }

    public function test_contributor_without_articles_has_profile_and_can_be_followed(): void
    {
        $author = User::factory()->create();
        $author->assignRole('author');

        $this->get(route('authors.show', $author->profile_slug))->assertOk();
        $this->actingAs($this->reader)
            ->post(route('authors.follow', $author->profile_slug))
            ->assertRedirect();

        $this->assertTrue($this->reader->follows()->whereKey($author->id)->exists());
    }

    public function test_former_contributor_keeps_profile_while_articles_are_published(): void
    {
        $former = User::factory()->create();
        $former->assignRole('user');
        Article::factory()->published()->for($former, 'author')->create();

        $this->get(route('authors.show', $former->profile_slug))->assertOk();
    }
}
