<?php

namespace App\Support;

use Illuminate\Support\Str;

class PageMeta
{
    /**
     * Meta tag halaman publik. Dicetak di app.blade.php (bukan oleh Vue) karena
     * WhatsApp dan LinkedIn tidak menjalankan JavaScript saat membuat pratinjau.
     *
     * @return array{title:string,description:string,url:string,image:string,type:string}
     */
    public static function make(
        string $title,
        string $description,
        ?string $url = null,
        ?string $image = null,
        string $type = 'website',
    ): array {
        return [
            'title' => $title,
            'description' => Str::limit(Str::squish(strip_tags($description)), 300),
            'url' => $url ?? url()->current(),
            'image' => url($image ?? '/assets/logo/LOGO-EXPERTIA-003.png'),
            'type' => $type,
        ];
    }
}
