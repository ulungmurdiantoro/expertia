<?php

namespace App\Console\Commands;

use App\Support\RobotsTxt;
use Illuminate\Console\Command;

class WriteRobotsTxtCommand extends Command
{
    protected $signature = 'expertia:robots';

    protected $description = 'Tulis public/robots.txt dari konfigurasi saat ini (dijalankan deploy.sh).';

    /**
     * Konfigurasi nginx standar Laravel melayani /robots.txt sebagai file statis dan
     * tidak meneruskannya ke PHP, jadi isinya ditulis sebagai file saat deploy.
     */
    public function handle(): int
    {
        file_put_contents(public_path('robots.txt'), RobotsTxt::render());

        $this->info('public/robots.txt ditulis untuk lingkungan '.app()->environment().'.');

        return self::SUCCESS;
    }
}
