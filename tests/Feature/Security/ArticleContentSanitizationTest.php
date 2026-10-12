<?php

namespace Tests\Feature\Security;

use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleContentSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scripts_and_event_handlers_are_removed_on_save(): void
    {
        $article = Article::factory()->create([
            'content' => '<p>Isi</p><script>alert(1)</script><img src="x" onerror="alert(1)"><a href="javascript:alert(1)">klik</a>',
        ]);

        $content = $article->fresh()->content;

        $this->assertStringContainsString('<p>Isi</p>', $content);
        $this->assertStringNotContainsString('<script', $content);
        $this->assertStringNotContainsString('onerror', $content);
        $this->assertStringNotContainsString('javascript:', $content);
    }

    public function test_editor_formatting_is_preserved(): void
    {
        $html = '<h2 style="text-align: center">Judul</h2><p><strong>tebal</strong> <em>miring</em></p>'
            .'<ul><li>satu</li></ul><blockquote><p>kutipan</p></blockquote>';

        $article = Article::factory()->create(['content' => $html]);

        $this->assertSame($html, $article->fresh()->content);
    }

    public function test_links_get_safe_rel_and_style_keeps_only_text_align(): void
    {
        $article = Article::factory()->create([
            'content' => '<p style="text-align:right;position:fixed">x</p><a href="https://doi.org/10.1" target="_blank">DOI</a>',
        ]);

        $content = $article->fresh()->content;

        $this->assertStringContainsString('<p style="text-align: right">x</p>', $content);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $content);
    }

    public function test_plain_text_content_is_left_untouched(): void
    {
        $article = Article::factory()->create(['content' => "Baris satu & dua\nNilai a < b"]);

        $this->assertSame("Baris satu & dua\nNilai a < b", $article->fresh()->content);
    }

    public function test_author_submitted_html_is_sanitized_through_the_form(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $author = User::factory()->create();
        $author->assignRole('author');

        $this->actingAs($author)->post(route('author.articles.store'), [
            'title' => 'Naskah Uji',
            'content' => '<p>Halo</p><img src=x onerror="fetch(\'/admin\')">',
        ])->assertRedirect();

        $this->assertStringNotContainsString('onerror', Article::firstWhere('title', 'Naskah Uji')->content);
    }
}
