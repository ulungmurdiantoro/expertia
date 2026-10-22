<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OptimizeStoredImagesCommand extends Command
{
    protected $signature = 'images:optimize
        {--min-kb=200 : Hanya gambar yang lebih besar dari ukuran ini}
        {--delete-originals : Hapus file asli setelah versi optimasi tersimpan}';

    protected $description = 'Perkecil thumbnail artikel dan avatar yang sudah diunggah (WebP, lebar maksimum).';

    public function handle(ImageOptimizer $optimizer): int
    {
        if (! ImageOptimizer::isAvailable()) {
            $this->error('Ekstensi GD tidak terpasang. Di Debian: sudo apt install php8.3-gd, lalu jalankan ulang.');

            return self::FAILURE;
        }

        $minBytes = max(0, (int) $this->option('min-kb')) * 1024;
        $disk = Storage::disk('public');
        $saved = 0;

        // Lewat query builder agar updated_at artikel tidak berubah.
        $jobs = [
            ['articles', 'thumbnail', 'articles/thumbnails', ImageOptimizer::ARTICLE_THUMBNAIL_WIDTH],
            [(new User)->getTable(), 'avatar', 'avatars', ImageOptimizer::AVATAR_WIDTH],
        ];

        foreach ($jobs as [$table, $column, $directory, $maxWidth]) {
            $rows = DB::table($table)->whereNotNull($column)->where($column, 'not like', 'http%')->get(['id', $column]);

            foreach ($rows as $row) {
                $path = $row->{$column};
                if (! $disk->exists($path) || $disk->size($path) <= $minBytes) {
                    continue;
                }

                $before = $disk->size($path);
                $newPath = $optimizer->optimizeStored($path, $directory, $maxWidth);
                if ($newPath === null) {
                    continue;
                }

                DB::table($table)->where('id', $row->id)->update([$column => $newPath]);
                $after = $disk->size($newPath);
                $saved += $before - $after;

                if ($this->option('delete-originals')) {
                    $disk->delete($path);
                }

                $this->line(sprintf('  %s: %s KB -> %s KB (%s)', $path, round($before / 1024), round($after / 1024), $newPath));
            }
        }

        $this->info('Selesai. Hemat '.round($saved / 1024 / 1024, 1).' MB.'
            .($this->option('delete-originals') ? '' : ' File asli tidak dihapus; tambahkan --delete-originals untuk menghapusnya.'));

        return self::SUCCESS;
    }
}
