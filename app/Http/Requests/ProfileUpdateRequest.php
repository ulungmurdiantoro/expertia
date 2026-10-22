<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public const BIO_MAX_LENGTH = 1000;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Jabatan dan bio tampil di profil publik kontributor; akun pembaca tidak punya bagian ini.
        $excludeContributorFields = Rule::excludeIf(! $this->user()->can('articles.create'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'position' => [$excludeContributorFields, 'nullable', 'string', 'max:255'],
            'bio' => [$excludeContributorFields, 'nullable', 'string', 'max:'.self::BIO_MAX_LENGTH],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ];
    }
}
