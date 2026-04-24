<?php

namespace Database\Seeders;

use App\Models\ExpertiseOption;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpertiseOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
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

        foreach ($options as $index => $name) {
            ExpertiseOption::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
