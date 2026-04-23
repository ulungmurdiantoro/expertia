<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\UserNotification;
use App\Services\AuditLogger;
use App\Services\ArticleScoringService;
use Illuminate\Console\Command;

class PublishScheduledArticlesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:publish-scheduled {--limit=100 : Maksimal artikel per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publikasikan artikel terjadwal yang sudah masuk waktu tayang.';

    /**
     * Execute the console command.
     */
    public function handle(AuditLogger $auditLogger, ArticleScoringService $scoringService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 500));

        $articles = Article::query()
            ->where('editorial_status', 'approved')
            ->where('status', '!=', 'published')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->oldest('scheduled_at')
            ->limit($limit)
            ->get();

        if ($articles->isEmpty()) {
            $this->info('Tidak ada artikel terjadwal untuk dipublikasikan.');

            return self::SUCCESS;
        }

        foreach ($articles as $article) {
            $article->update([
                'status' => 'published',
                'published_at' => $article->published_at ?? now(),
            ]);

            $scoringService->scoreArticle($article);

            UserNotification::create([
                'user_id' => $article->user_id,
                'type' => 'article.scheduled.published',
                'title' => 'Artikel terjadwal sudah tayang',
                'body' => "Artikel '{$article->title}' telah dipublikasikan otomatis.",
                'data_json' => [
                    'article_id' => $article->id,
                    'article_slug' => $article->slug,
                ],
            ]);

            $auditLogger->log(
                userId: null,
                action: 'scheduler.article.published',
                subject: $article,
                meta: [
                    'scheduled_at' => optional($article->scheduled_at)?->toDateTimeString(),
                    'published_at' => optional($article->published_at)?->toDateTimeString(),
                ]
            );
        }

        $this->info("Berhasil publish {$articles->count()} artikel terjadwal.");

        return self::SUCCESS;
    }
}
