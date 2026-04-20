<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slug = 'strategi-merancang-kurikulum-berbasis-outcome-based-education-obe-di-perguruan-tinggi';
$article = App\Models\Article::query()->where('slug', $slug)->first(['id','title','updated_at','content']);

if (! $article) {
    echo "NOT_FOUND\n";
    exit;
}

echo "ID={$article->id}\n";
echo "UPDATED_AT={$article->updated_at}\n";
$content = (string) $article->content;
echo "HAS_HTML_TAGS=".(preg_match('/<[^>]+>/', $content) ? 'yes' : 'no')."\n";
echo "CONTENT_PREVIEW=\n";
echo mb_substr($content, 0, 1500),"\n";
