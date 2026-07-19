<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:2048',
                'dimensions:min_width=128,min_height=128',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Avatar é obrigatório.',
            'avatar.image' => 'O arquivo deve ser uma imagem.',
            'avatar.mimes' => 'Formatos aceitos: JPEG, PNG, WebP.',
            'avatar.mimetypes' => 'Formatos aceitos: JPEG, PNG, WebP.',
            'avatar.max' => 'A imagem deve ter no máximo 2MB.',
            'avatar.dimensions' => 'A imagem deve ter no mínimo 128x128 pixels.',
        ];
    }
}
