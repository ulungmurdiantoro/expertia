<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Enums\EditorialStatus;
use App\Models\Article;
use App\Services\ArticleWorkflow;
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
    public function handle(ArticleWorkflow $workflow): int
    {
        $limit = max(1, min((int) $this->option('limit'), 500));

        $articles = Article::query()
            ->where('editorial_status', EditorialStatus::Approved->value)
            ->where('status', '!=', ArticleStatus::Published->value)
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
            $workflow->publishScheduled($article);
        }

        $this->info("Berhasil publish {$articles->count()} artikel terjadwal.");

        return self::SUCCESS;
    }
}
