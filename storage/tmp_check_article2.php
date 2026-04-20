<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$slug = 'strategi-merancang-kurikulum-berbasis-outcome-based-education-obe-di-perguruan-tinggi';
$article = \App\Models\Article::query()->where('slug', $slug)->first(['content','excerpt']);

if (! $article) {
    echo "NOT_FOUND\n";
    exit(0);
}

echo "CONTENT_HEAD=\n";
echo mb_substr((string)$article->content, 0, 700),"\n\n";
echo "EXCERPT_HEAD=\n";
echo mb_substr((string)$article->excerpt, 0, 300),"\n";
