<?php

namespace App\Console\Commands;

use App\Services\ArticleScoringService;
use Illuminate\Console\Command;

class RecalculateArticleScoresCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:recalculate-scores {--chunk=200 : Chunk size saat proses recalculation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hitung ulang score hotness dan trending untuk artikel terbit.';

    /**
     * Execute the console command.
     */
    public function handle(ArticleScoringService $scoringService): int
    {
        $chunk = max(50, min((int) $this->option('chunk'), 1000));
        $processed = $scoringService->recalculateAll($chunk);
        $this->info("Skor artikel berhasil dihitung ulang. Total diproses: {$processed}");

        return self::SUCCESS;
    }
}
