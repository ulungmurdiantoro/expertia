<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Data penulis yang boleh tampil di halaman publik. Email, nomor WhatsApp, dan data
 * akun lain sengaja tidak ada di sini (lihat Kebijakan Privasi).
 *
 * @mixin User
 */
class PublicAuthorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'profile_slug' => $this->profile_slug,
            'bio' => $this->whenHas('bio'),
            'avatar_url' => $this->whenHas('avatar', fn () => $this->avatar_url),
        ];
    }
}
