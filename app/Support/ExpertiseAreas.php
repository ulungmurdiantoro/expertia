<?php

namespace App\Support;

use App\Models\ExpertiseOption;
use Illuminate\Support\Facades\Schema;

/**
 * Bidang keahlian kontributor. Disimpan di users.expertise_areas sebagai daftar
 * topik pilihan, ditambah satu isian bebas berawalan "Other: ".
 */
class ExpertiseAreas
{
    private const OTHER_PREFIX = 'Other: ';

    private const DEFAULT_OPTIONS = [
        'SPMI',
        'AMI',
        'Tata Kelola Perguruan Tinggi',
        'OBE',
        'Akreditasi Nasional',
        'Akreditasi internasional',
        'Pemeringkatan PT Nasional dan Internasional (Qs Rank, THE Rank dll)',
        'Reformasi Birokrasi dan Zona Integritas',
        'Rencana Strategis dan Proses Bisnis PT',
        'SMAP ISO 37001',
        'SMOP ISO 21001',
        'SMM ISO 9001',
        'SML ISO 14001',
        'SMKI ISO 27001',
    ];

    /**
     * Pilihan aktif dari tabel expertise_options, atau daftar bawaan bila tabel kosong.
     *
     * @return list<string>
     */
    public static function options(): array
    {
        if (! Schema::hasTable('expertise_options')) {
            return self::DEFAULT_OPTIONS;
        }

        $options = ExpertiseOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name')
            ->filter(fn ($name) => is_string($name) && $name !== '')
            ->values()
            ->all();

        return count($options) > 0 ? $options : self::DEFAULT_OPTIONS;
    }

    /**
     * Gabungkan isian form (topik terpilih + isian bebas) menjadi nilai kolom.
     *
     * @param  array<int, mixed>  $topics
     * @return list<string>|null
     */
    public static function fromInput(array $topics, ?string $other): ?array
    {
        $areas = collect($topics)
            ->filter(fn ($value) => is_string($value) && $value !== '')
            ->values();

        $other = trim((string) $other);

        if ($other !== '') {
            $areas->push(self::OTHER_PREFIX.$other);
        }

        return $areas->isEmpty() ? null : $areas->all();
    }

    /**
     * Kebalikan fromInput(): pecah nilai kolom untuk mengisi form.
     *
     * @param  array<int, string>|null  $areas
     * @return array{topics: list<string>, other: string}
     */
    public static function toInput(?array $areas): array
    {
        $topics = [];
        $other = '';

        foreach ($areas ?? [] as $area) {
            if (str_starts_with((string) $area, self::OTHER_PREFIX)) {
                $other = substr((string) $area, strlen(self::OTHER_PREFIX));
            } else {
                $topics[] = (string) $area;
            }
        }

        return ['topics' => $topics, 'other' => $other];
    }
}
