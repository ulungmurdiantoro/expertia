<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$slug = 'strategi-merancang-kurikulum-berbasis-outcome-based-education-obe-di-perguruan-tinggi';
$article = \App\Models\Article::query()->where('slug', $slug)->first();

if (! $article) {
    echo "NOT_FOUND\n";
    exit(0);
}

$article->load('author:id,name,profile_slug');
echo 'AUTHOR_ID='.($article->user_id ?? 'null')."\n";
echo 'AUTHOR_REL='.json_encode($article->author, JSON_UNESCAPED_UNICODE)."\n";
