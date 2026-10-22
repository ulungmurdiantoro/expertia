<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Rubrik di menu, footer, dan beranda (resources/js/site.js) menautkan ke
 * /articles?category=<slug>, tetapi kategorinya tidak pernah dibuat: seeder produksi
 * tidak membuat kategori dan belum ada halaman untuk mengelolanya. Akibatnya tautan
 * rubrik selalu kosong dan penulis tidak bisa memilih rubrik itu.
 *
 * Hanya membuat yang belum ada; kategori dengan slug yang sama (mis. dibuat manual)
 * dibiarkan apa adanya.
 */
return new class extends Migration
{
    /**
     * Salinan `rubrics` di resources/js/site.js saat migrasi ini dibuat.
     */
    private const RUBRICS = [
        [
            'slug' => 'riset-inovasi',
            'name' => 'Riset & Inovasi',
            'description' => 'Temuan penelitian, paten, dan inovasi teknologi yang diterjemahkan menjadi bacaan yang jelas tanpa kehilangan ketepatannya.',
        ],
        [
            'slug' => 'perspektif-pakar',
            'name' => 'Perspektif Pakar',
            'description' => 'Analisis, studi kasus, dan wawasan profesional dari mereka yang memahami persoalannya dari dekat.',
        ],
        [
            'slug' => 'profil-pakar',
            'name' => 'Profil Pakar',
            'description' => 'Perjalanan, karya, dan pemikiran akademisi, peneliti, inventor, dan praktisi di balik sebuah keahlian.',
        ],
        [
            'slug' => 'kebijakan-solusi',
            'name' => 'Kebijakan & Solusi',
            'description' => 'Membaca isu publik dan tantangan industri berdasarkan bukti, lalu menimbang jalan keluarnya.',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        $now = now();

        foreach (self::RUBRICS as $rubric) {
            if (DB::table('categories')->where('slug', $rubric['slug'])->exists()) {
                continue;
            }

            DB::table('categories')->insert([
                ...$rubric,
                'public_id' => (string) Str::uuid(),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Tidak dihapus: rubrik bisa sudah dipakai artikel.
    }
};
