<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleReview;
use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\RecommendationSeed;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentDummySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', [
                'superadmin@example.com',
                'admin@example.com',
                'editor@example.com',
                'moderator@example.com',
                'verified.expert@example.com',
                'author@example.com',
                'subscriber@example.com',
                'user@example.com',
            ])
            ->get()
            ->keyBy('email');

        $author = $users->get('author@example.com');
        $expert = $users->get('verified.expert@example.com');
        $editor = $users->get('editor@example.com');
        $moderator = $users->get('moderator@example.com');
        $subscriber = $users->get('subscriber@example.com');
        $regularUser = $users->get('user@example.com');

        if (!$author || !$expert || !$editor || !$moderator || !$subscriber || !$regularUser) {
            return;
        }

        $categories = collect([
            ['name' => 'Teknologi', 'slug' => 'teknologi', 'description' => 'Tren teknologi, AI, dan software'],
            ['name' => 'Sains', 'slug' => 'sains', 'description' => 'Riset dan penemuan ilmiah'],
            ['name' => 'Opini', 'slug' => 'opini', 'description' => 'Sudut pandang penulis dan pakar'],
            ['name' => 'Edukasi', 'slug' => 'edukasi', 'description' => 'Materi belajar dan tutorial'],
        ])->mapWithKeys(function (array $item) {
            $category = Category::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'is_active' => true,
                ]
            );

            return [$item['slug'] => $category];
        });

        $tags = collect([
            ['name' => 'AI', 'slug' => 'ai'],
            ['name' => 'Machine Learning', 'slug' => 'machine-learning'],
            ['name' => 'Data', 'slug' => 'data'],
            ['name' => 'Kebijakan Publik', 'slug' => 'kebijakan-publik'],
            ['name' => 'Produktivitas', 'slug' => 'produktivitas'],
        ])->mapWithKeys(function (array $item) {
            $tag = Tag::updateOrCreate(
                ['slug' => $item['slug']],
                ['name' => $item['name']]
            );

            return [$item['slug'] => $tag];
        });

        $articleA = $this->upsertArticle(
            user: $author,
            category: $categories['teknologi'],
            title: 'Membangun Pipeline Konten Berbasis AI untuk Media',
            status: 'published',
            editorialStatus: 'approved',
            excerpt: 'Strategi praktis membangun workflow editorial modern dengan dukungan AI.',
            content: '<p>Contoh konten dummy untuk pengujian alur publikasi artikel.</p>'
        );
        $articleA->tags()->sync([$tags['ai']->id, $tags['machine-learning']->id]);

        $articleB = $this->upsertArticle(
            user: $expert,
            category: $categories['sains'],
            title: 'Etika dan Validitas Riset dalam Era Generative AI',
            status: 'published',
            editorialStatus: 'approved',
            excerpt: 'Kerangka etika publikasi untuk penelitian berbasis model generatif.',
            content: '<p>Konten dummy dari verified expert untuk pengujian profile pakar.</p>'
        );
        $articleB->tags()->sync([$tags['ai']->id, $tags['kebijakan-publik']->id]);

        $articleC = $this->upsertArticle(
            user: $author,
            category: $categories['opini'],
            title: 'Mengapa Komunitas Menentukan Kualitas Platform Pengetahuan',
            status: 'in_review',
            editorialStatus: 'pending_review',
            excerpt: 'Opini tentang pentingnya moderasi dan feedback loop komunitas.',
            content: '<p>Artikel ini disiapkan untuk simulasi review editor.</p>'
        );
        $articleC->tags()->sync([$tags['kebijakan-publik']->id]);

        $articleD = $this->upsertArticle(
            user: $expert,
            category: $categories['edukasi'],
            title: 'Checklist Menulis Artikel Teknis yang Mudah Dibaca',
            status: 'draft',
            editorialStatus: 'draft',
            excerpt: 'Checklist sederhana agar artikel teknis tetap clear dan engaging.',
            content: '<p>Draft untuk simulasi artikel yang belum dikirim ke editor.</p>'
        );
        $articleD->tags()->sync([$tags['produktivitas']->id, $tags['data']->id]);

        Follow::firstOrCreate([
            'follower_id' => $subscriber->id,
            'author_id' => $author->id,
        ]);
        Follow::firstOrCreate([
            'follower_id' => $regularUser->id,
            'author_id' => $expert->id,
        ]);

        Bookmark::firstOrCreate([
            'user_id' => $subscriber->id,
            'article_id' => $articleA->id,
        ]);
        Bookmark::firstOrCreate([
            'user_id' => $regularUser->id,
            'article_id' => $articleB->id,
        ]);

        $commentA = Comment::firstOrCreate(
            [
                'article_id' => $articleA->id,
                'user_id' => $subscriber->id,
                'content' => 'Artikelnya sangat membantu, terutama bagian workflow.',
            ],
            [
                'status' => 'approved',
                'moderated_by' => $moderator->id,
                'moderated_at' => now()->subDay(),
            ]
        );

        Comment::firstOrCreate(
            [
                'article_id' => $articleA->id,
                'user_id' => $author->id,
                'parent_id' => $commentA->id,
                'content' => 'Terima kasih, nanti saya tambahkan contoh implementasi juga.',
            ],
            [
                'status' => 'approved',
                'moderated_by' => $moderator->id,
                'moderated_at' => now()->subHours(20),
            ]
        );

        $commentB = Comment::firstOrCreate(
            [
                'article_id' => $articleB->id,
                'user_id' => $regularUser->id,
                'content' => 'Mohon referensi paper untuk klaim pada paragraf kedua.',
            ],
            [
                'status' => 'pending',
            ]
        );

        ArticleReview::firstOrCreate(
            [
                'article_id' => $articleC->id,
                'editor_id' => $editor->id,
                'action' => 'queued',
            ],
            [
                'note' => 'Masuk antrian review dummy seeder.',
                'reviewed_at' => now()->subHours(3),
            ]
        );

        Report::firstOrCreate(
            [
                'reporter_id' => $regularUser->id,
                'reportable_type' => Article::class,
                'reportable_id' => $articleB->id,
                'reason' => 'Klaim butuh sumber tambahan',
            ],
            [
                'note' => 'Mohon ditinjau ulang bagian metodologi.',
                'status' => 'pending',
            ]
        );

        Report::firstOrCreate(
            [
                'reporter_id' => $subscriber->id,
                'reportable_type' => Comment::class,
                'reportable_id' => $commentB->id,
                'reason' => 'Komentar berpotensi memicu debat tidak sehat',
            ],
            [
                'note' => 'Bahasanya terlalu provokatif.',
                'status' => 'pending',
            ]
        );

        Report::firstOrCreate(
            [
                'reporter_id' => $author->id,
                'reportable_type' => Comment::class,
                'reportable_id' => $commentA->id,
                'reason' => 'Test resolved moderation report',
            ],
            [
                'note' => 'Laporan dummy untuk status resolved.',
                'status' => 'resolved',
                'handled_by' => $moderator->id,
                'handled_at' => now()->subHours(10),
                'resolution_note' => 'Sudah dicek, tidak ada pelanggaran.',
            ]
        );

        $this->refreshArticleCounters($articleA);
        $this->refreshArticleCounters($articleB);

        $this->seedRecommendationData($subscriber, $regularUser, $author, $expert, $articleA, $articleB);
    }

    private function upsertArticle(
        User $user,
        Category $category,
        string $title,
        string $status,
        string $editorialStatus,
        string $excerpt,
        string $content
    ): Article {
        $slug = $this->resolveArticleSlug($title);

        return Article::updateOrCreate(
            ['slug' => $slug],
            [
                'user_id' => $user->id,
                'category_id' => $category->id,
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $content,
                'status' => $status,
                'editorial_status' => $editorialStatus,
                'visibility' => 'public',
                'submitted_at' => $editorialStatus === 'pending_review' ? now()->subHours(4) : null,
                'reviewed_at' => $editorialStatus === 'approved' ? now()->subDay() : null,
                'published_at' => $status === 'published' ? now()->subDays(2) : null,
                'view_count' => random_int(100, 1200),
                'like_count' => random_int(10, 200),
                'share_count' => random_int(0, 40),
                'score_hotness' => random_int(120, 900) / 10,
                'score_trending' => random_int(50, 700) / 10,
            ]
        );
    }

    private function resolveArticleSlug(string $title): string
    {
        $base = Str::slug($title);

        return $base !== '' ? $base : Str::lower(Str::random(12));
    }

    private function refreshArticleCounters(Article $article): void
    {
        $approvedComments = Comment::query()
            ->where('article_id', $article->id)
            ->where('status', 'approved')
            ->count();

        $bookmarkCount = Bookmark::query()
            ->where('article_id', $article->id)
            ->count();

        $article->update([
            'comment_count' => $approvedComments,
            'bookmark_count' => $bookmarkCount,
        ]);
    }

    private function seedRecommendationData(
        User $subscriber,
        User $regularUser,
        User $author,
        User $expert,
        Article $articleA,
        Article $articleB
    ): void {
        RecommendationSeed::updateOrCreate(
            [
                'user_id' => $subscriber->id,
                'article_id' => $articleA->id,
                'source' => 'follow_author',
            ],
            [
                'score' => 90,
                'reason' => 'Karena Anda mengikuti author ini',
                'meta_json' => ['author_id' => $author->id],
                'generated_at' => now(),
            ]
        );

        RecommendationSeed::updateOrCreate(
            [
                'user_id' => $regularUser->id,
                'article_id' => $articleB->id,
                'source' => 'interest_tag',
            ],
            [
                'score' => 85,
                'reason' => 'Relevan dengan minat konten AI',
                'meta_json' => ['author_id' => $expert->id, 'tag' => 'ai'],
                'generated_at' => now(),
            ]
        );

        RecommendationSeed::updateOrCreate(
            [
                'user_id' => null,
                'article_id' => $articleA->id,
                'source' => 'global_trending_seed',
            ],
            [
                'score' => 75,
                'reason' => 'Seed global untuk warm-start trending',
                'meta_json' => ['cohort' => 'global'],
                'generated_at' => now(),
            ]
        );
    }
}
