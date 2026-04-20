<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$total = \App\Models\Article::count();
echo "TOTAL=".$total."\n";

$slug = 'strategi-merancang-kurikulum-berbasis-outcome-based-education-obe-di-perguruan-tinggi';
$article = \App\Models\Article::query()->where('slug', $slug)->first();

if (! $article) {
    echo "NOT_FOUND\n";
    exit(0);
}

echo "FOUND_ID={$article->id}\n";
echo "STATUS={$article->status}\n";
echo "PUBLISHED_AT=".($article->published_at ?? 'null')."\n";
echo "EXCERPT_LEN=".strlen((string) $article->excerpt)."\n";
echo "CONTENT_LEN=".strlen((string) $article->content)."\n";
